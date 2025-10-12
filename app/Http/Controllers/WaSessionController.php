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

        return Inertia::render('WhatsApp/Connect', [
            'session' => $session,
        ]);
    }

    /**
     * Create new session and get QR code
     */
    public function store(Request $request): RedirectResponse
    {
        try {
            $user = $request->user();

            // Call Node.js bridge to create session
            $response = $this->bridge->createSession($user->id);

            // Create or update session
            $session = WaSession::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'status' => 'pending',
                    'meta_json' => [
                        'qr_base64' => $response['qr'] ?? null,
                    ],
                    'expires_at' => now()->addMinutes(5),
                ]
            );

            AuditLog::log('qr_generated', 'WaSession', $session->id);

            return redirect()->route('wa.connect')
                ->with('success', 'QR code generated. Please scan it with your WhatsApp.');
        } catch (\Exception $e) {
            return redirect()->route('wa.connect')
                ->with('error', 'Failed to generate QR code: ' . $e->getMessage());
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

            // Update local session
            $session = WaSession::where('user_id', $user->id)->first();

            if ($session && isset($response['status'])) {
                $session->update([
                    'status' => $response['status'],
                    'meta_json' => array_merge(
                        $session->meta_json ?? [],
                        $response['meta'] ?? []
                    ),
                    'last_seen_at' => $response['status'] === 'connected' ? now() : $session->last_seen_at,
                ]);

                if ($response['status'] === 'connected') {
                    AuditLog::log('connected', 'WaSession', $session->id);
                }
            }

            return response()->json([
                'status' => $response['status'] ?? 'pending',
                'session' => $session,
            ]);
        } catch (\Exception $e) {
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

            $session = WaSession::where('user_id', $user->id)->first();
            if ($session) {
                $session->update([
                    'status' => 'pending',
                    'meta_json' => [
                        'qr_base64' => $response['qr'] ?? null,
                    ],
                    'expires_at' => now()->addMinutes(5),
                ]);

                AuditLog::log('qr_refreshed', 'WaSession', $session->id);
            }

            return redirect()->route('wa.connect')
                ->with('success', 'QR code refreshed successfully.');
        } catch (\Exception $e) {
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

            $this->bridge->disconnect($user->id);

            $session = WaSession::where('user_id', $user->id)->first();
            if ($session) {
                $session->update([
                    'status' => 'disconnected',
                    'meta_json' => null,
                ]);

                AuditLog::log('disconnected', 'WaSession', $session->id);
            }

            return redirect()->route('wa.connect')
                ->with('success', 'WhatsApp disconnected successfully.');
        } catch (\Exception $e) {
            return redirect()->route('wa.connect')
                ->with('error', 'Failed to disconnect: ' . $e->getMessage());
        }
    }
}
