<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\WaSession;
use App\Services\BridgeClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class WaSessionController extends Controller
{
    public function __construct(private BridgeClient $bridge)
    {
    }

    /**
     * Show WhatsApp connection page
     */
    public function index(Request $request): Response
    {
        $session = $request->user()->waSession;

        return Inertia::render('whatsapp/Connect', [
            'session' => $session,
        ]);
    }

    /**
     * Create new session with QR code (default method)
     */
    public function store(Request $request): RedirectResponse
    {
        try {
            $user = $request->user();
            $session = WaSession::where('user_id', $user->id)->first();
            $credentials = null;

            if ($session && $session->auth_credentials) {
                $credentials = json_decode($session->auth_credentials, true);
            }

            // Call Node.js bridge to create session
            $response = $this->bridge->createSession($user->id, $credentials);

            // Wait for QR code to be generated
            $qrResponse = null;
            $maxAttempts = 20;
            $attempt = 0;

            while ($attempt < $maxAttempts && !$qrResponse) {
                usleep(500000);

                try {
                    $qrResponse = $this->bridge->getQr($user->id);
                    break;
                } catch (\Exception $e) {
                    $attempt++;

                    if ($attempt >= $maxAttempts) {
                        throw new \Exception('QR code generation timeout. Please try again.');
                    }
                }
            }

            // Create or update session with QR code
            $session = WaSession::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'status' => 'pending',
                    'meta_json' => [
                        'qr_base64' => $qrResponse['qr_image'] ?? null,
                        'method' => 'qr',
                    ],
                    'expires_at' => now()->addMinutes(5),
                ]
            );

            AuditLog::log('qr_generated', 'WaSession', $session->id);

            return redirect()->route('wa.connect')
                ->with('success', 'QR code generated. Please scan it with your WhatsApp.');

        } catch (\Exception $e) {
            \Log::error('Failed to generate QR code', [
                'error' => $e->getMessage(),
                'user_id' => $request->user()->id,
            ]);

            return redirect()->route('wa.connect')
                ->with('error', 'Failed to generate QR code: ' . $e->getMessage());
        }
    }

    /**
     * Create new session with Pairing Code (alternative method)
     */
    public function storePairing(Request $request): RedirectResponse
    {
        try {
            $validated = $request->validate([
                'phone' => 'required|string|min:10|max:20',
            ]);

            $user = $request->user();

            // Call Node.js bridge to create session with pairing code
            $response = $this->bridge->createSessionWithPairing($user->id, $validated['phone']);

            // Wait for pairing code to be generated
            $pairingCode = null;
            $maxAttempts = 20;
            $attempt = 0;

            // Check if code is already in response
            if (isset($response['pairing_code'])) {
                $pairingCode = $response['pairing_code'];
            } else {
                // Poll status endpoint for pairing code
                while ($attempt < $maxAttempts && !$pairingCode) {
                    usleep(500000); // 500ms delay

                    try {
                        $statusResponse = $this->bridge->getStatus($user->id);
                        if (isset($statusResponse['pairing_code']) && $statusResponse['pairing_code']) {
                            $pairingCode = $statusResponse['pairing_code'];
                            break;
                        }
                    } catch (\Exception $e) {
                        $attempt++;

                        if ($attempt >= $maxAttempts) {
                            throw new \Exception('Pairing code generation timeout. Please try again.');
                        }
                    }

                    $attempt++;
                }
            }

            if (!$pairingCode) {
                throw new \Exception('Failed to generate pairing code');
            }

            // Create or update session with pairing info
            $session = WaSession::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'status' => 'pending',
                    'meta_json' => [
                        'pairing_code' => $pairingCode,
                        'pairing_phone' => $validated['phone'],
                        'method' => 'pairing',
                    ],
                    'expires_at' => now()->addMinutes(5),
                ]
            );

            AuditLog::log('pairing_code_generated', 'WaSession', $session->id);

            return redirect()->route('wa.connect')
                ->with('success', 'Pairing code generated: ' . $pairingCode . ' - Enter this code on your phone.');

        } catch (\Exception $e) {
            \Log::error('Failed to generate pairing code', [
                'error' => $e->getMessage(),
                'user_id' => $request->user()->id,
            ]);

            return redirect()->route('wa.connect')
                ->with('error', 'Failed to generate pairing code: ' . $e->getMessage());
        }
    }

    /**
     * Get session status (for polling)
     */
    public function status(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            // Get status from Node.js bridge
            $response = $this->bridge->getStatus($user->id);

            // Get local session
            $session = WaSession::where('user_id', $user->id)->first();

            if ($session) {
                $updateData = [];

                // Determine status based on bridge response
                if (isset($response['ready']) && $response['ready']) {
                    $updateData['status'] = 'connected';
                    $updateData['last_seen_at'] = now();
                    $updateData['last_heartbeat_at'] = now();

                    // Store owner info in meta_json
                    if (isset($response['owner'])) {
                        $updateData['meta_json'] = array_merge(
                            $session->meta_json ?? [],
                            [
                                'phone' => $response['owner']['number'] ?? null,
                                'name' => $response['owner']['pushName'] ?? null,
                            ]
                        );
                    }

                    if ($session->status !== 'connected') {
                        AuditLog::log('connected', 'WaSession', $session->id);
                    }
                } elseif (isset($response['qr_available']) && $response['qr_available']) {
                    // QR is available, fetch it and update session
                    try {
                        $qrResponse = $this->bridge->getQr($user->id);

                        $updateData['status'] = 'pending';
                        $updateData['meta_json'] = [
                            'qr_base64' => $qrResponse['qr_image'] ?? null,
                            'method' => 'qr',
                        ];
                        $updateData['expires_at'] = now()->addMinutes(5);
                    } catch (\Exception $e) {
                        \Log::warning('Failed to fetch QR during polling', [
                            'user_id' => $user->id,
                            'error' => $e->getMessage(),
                        ]);
                    }
                } elseif (isset($response['pairing_code']) && $response['pairing_code']) {
                    // Pairing code is available
                    $updateData['status'] = 'pending';
                    $updateData['meta_json'] = array_merge(
                        $session->meta_json ?? [],
                        [
                            'pairing_code' => $response['pairing_code'],
                            'method' => 'pairing',
                        ]
                    );
                    $updateData['expires_at'] = now()->addMinutes(5);
                } elseif (!isset($response['exists']) || !$response['exists']) {
                    // No session exists on bridge
                    $updateData['status'] = 'disconnected';
                    $updateData['meta_json'] = null;
                }

                // Apply updates if any
                if (!empty($updateData)) {
                    $session->update($updateData);
                }
            }

            return response()->json([
                'status' => $session?->status ?? 'disconnected',
                'session' => $session,
            ]);

        } catch (\Exception $e) {
            \Log::error('Status polling error', [
                'error' => $e->getMessage(),
                'user_id' => $request->user()->id,
            ]);

            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Refresh QR code
     */
    public function refresh(Request $request): RedirectResponse
    {
        try {
            $user = $request->user();

            $response = $this->bridge->refreshQr($user->id);

            // Wait for new QR code
            $qrResponse = null;
            $maxAttempts = 10;
            $attempt = 0;

            while ($attempt < $maxAttempts && !$qrResponse) {
                usleep(500000);

                try {
                    $qrResponse = $this->bridge->getQr($user->id);
                    break;
                } catch (\Exception $e) {
                    $attempt++;

                    if ($attempt >= $maxAttempts) {
                        throw new \Exception('QR code generation timeout. Please try again.');
                    }
                }
            }

            $session = WaSession::where('user_id', $user->id)->first();
            if ($session) {
                $session->update([
                    'status' => 'pending',
                    'meta_json' => [
                        'qr_base64' => $qrResponse['qr_image'] ?? null,
                        'method' => 'qr',
                    ],
                    'expires_at' => now()->addMinutes(5),
                ]);

                AuditLog::log('qr_refreshed', 'WaSession', $session->id);
            }

            return redirect()->route('wa.connect')
                ->with('success', 'QR code refreshed successfully.');

        } catch (\Exception $e) {
            \Log::error('Failed to refresh QR code', [
                'error' => $e->getMessage(),
                'user_id' => $request->user()->id,
            ]);

            return redirect()->route('wa.connect')
                ->with('error', 'Failed to refresh QR code: ' . $e->getMessage());
        }
    }

    /**
     * Disconnect session
     */
    public function destroy(Request $request): RedirectResponse
    {
        try {
            $user = $request->user();
            $session = WaSession::where('user_id', $user->id)->first();

            if (!$session) {
                return redirect()->route('wa.connect')
                    ->with('error', 'No active session found.');
            }

            // Call bridge to disconnect
            try {
                $this->bridge->disconnect($user->id);
            } catch (\Exception $e) {
                \Log::warning('Bridge disconnect failed, updating local session anyway', [
                    'error' => $e->getMessage(),
                    'user_id' => $user->id,
                ]);
            }

            // Update local session and clear credentials
            $session->update([
                'status' => 'disconnected',
                'meta_json' => null,
                'auth_credentials' => null,
                'last_seen_at' => null,
                'last_heartbeat_at' => null,
            ]);

            AuditLog::log('disconnected', 'WaSession', $session->id);

            return redirect()->route('wa.connect')
                ->with('success', 'WhatsApp disconnected successfully.');

        } catch (\Exception $e) {
            \Log::error('Disconnect error', [
                'error' => $e->getMessage(),
                'user_id' => $request->user()->id,
            ]);

            return redirect()->route('wa.connect')
                ->with('error', 'Failed to disconnect: ' . $e->getMessage());
        }
    }

    /**
     * Store credentials from Node.js (callback endpoint)
     */
    public function storeCredentials(Request $request): JsonResponse
    {
        try {
            $token = $request->header('X-BRIDGE-TOKEN');
            if ($token !== config('services.bridge.token')) {
                return response()->json([
                    'success' => false,
                    'error' => 'Unauthorized'
                ], 403);
            }

            $validated = $request->validate([
                'user_id' => 'required|integer|exists:users,id',
                'credentials' => 'nullable|array',
            ]);

            $session = WaSession::where('user_id', $validated['user_id'])->first();

            if (!$session) {
                return response()->json([
                    'success' => false,
                    'error' => 'Session not found'
                ], 404);
            }

            $session->update([
                'auth_credentials' => $validated['credentials'] ? json_encode($validated['credentials']) : null,
                'last_heartbeat_at' => now(),
            ]);

            \Log::info('Credentials stored', [
                'user_id' => $validated['user_id'],
                'session_id' => $session->id,
                'cleared' => is_null($validated['credentials']),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Credentials stored successfully'
            ]);

        } catch (\Exception $e) {
            \Log::error('Store credentials error', [
                'error' => $e->getMessage(),
                'request' => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Load credentials for Node.js (callback endpoint)
     */
    public function loadCredentials(Request $request): JsonResponse
    {
        try {
            $token = $request->header('X-BRIDGE-TOKEN');
            if ($token !== config('services.bridge.token')) {
                return response()->json([
                    'success' => false,
                    'error' => 'Unauthorized'
                ], 403);
            }

            $validated = $request->validate([
                'user_id' => 'required|integer|exists:users,id',
            ]);

            $session = WaSession::where('user_id', $validated['user_id'])->first();

            if (!$session || !$session->auth_credentials) {
                return response()->json([
                    'success' => false,
                    'error' => 'No credentials found'
                ], 404);
            }

            $credentials = json_decode($session->auth_credentials, true);

            return response()->json([
                'success' => true,
                'credentials' => $credentials
            ]);

        } catch (\Exception $e) {
            \Log::error('Load credentials error', [
                'error' => $e->getMessage(),
                'request' => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
