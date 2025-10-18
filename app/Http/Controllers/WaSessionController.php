<?php

namespace App\Http\Controllers;

use App\Jobs\SyncWhatsAppContacts;
use App\Models\AuditLog;
use App\Models\WaSession;
use App\Services\BridgeManager;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Log;

class WaSessionController extends Controller
{
    public function __construct(
        private readonly BridgeManager $bridgeManager
    )
    {
    }

    /**
     * Show WhatsApp connection page with all user devices
     */
    public function index(Request $request): Response
    {
        $sessions = WaSession::where('user_id', $request->user()->id)
            ->orderBy('is_primary', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        $maxDevices = $this->bridgeManager->getDeviceLimit($request->user()->id);

        return Inertia::render('whatsapp/Connect', [
            'sessions' => $sessions,
            'maxDevices' => $maxDevices,
        ]);
    }

    /**
     * Get status for all user's devices
     */
    public function status(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $sessions = WaSession::where('user_id', $user->id)->get();

            Log::info('Status check started', [
                'user_id' => $user->id,
                'session_count' => $sessions->count(),
            ]);

            $statusData = [];

            foreach ($sessions as $session) {
                try {
                    $bridge = $this->bridgeManager->getClientForSession($session);

                    Log::info('Checking device status', [
                        'device_id' => $session->device_id,
                        'current_status' => $session->status,
                        'bridge_url' => $session->getBridgeUrl(),
                    ]);

                    // Check devices first (safe to call anytime)
                    $devicesResponse = $bridge->getDevices();
                    Log::info('Devices response received', [
                        'device_id' => $session->device_id,
                        'success' => $devicesResponse['success'],
                        'full_response' => $devicesResponse,
                    ]);

                    // FIXED: Check for devices in the correct nested structure
                    $hasDevices = false;
                    $deviceInfo = null;

                    if (
                        $devicesResponse['success'] &&
                        isset($devicesResponse['data']['results']) &&
                        is_array($devicesResponse['data']['results']) &&
                        count($devicesResponse['data']['results']) > 0
                    ) {
                        $hasDevices = true;
                        $deviceInfo = $devicesResponse['data']['results'][0];
                    }

                    Log::info('Device check result', [
                        'device_id' => $session->device_id,
                        'has_devices' => $hasDevices,
                        'device_info' => $deviceInfo,
                        'current_status' => $session->status,
                    ]);

                    // Important: Only mark as connected if we were PENDING and now have devices
                    if ($hasDevices && $session->status === 'pending') {
                        // Devices found = logged in
                        $updateData = [
                            'status' => 'connected',
                            'last_seen_at' => now(),
                            'last_heartbeat_at' => now(),
                        ];

                        if ($deviceInfo) {
                            $updateData['meta_json'] = array_merge($session->meta_json ?? [], [
                                'phone' => $deviceInfo['device'] ?? null,
                                'name' => $deviceInfo['name'] ?? null,
                                'device' => $deviceInfo['device'] ?? null,
                                'platform' => 'WhatsApp',
                            ]);
                        }

                        $session->update($updateData);

                        SyncWhatsAppContacts::dispatch($session->fresh());


                        Log::info('Session updated to connected', [
                            'device_id' => $session->device_id,
                            'meta' => $updateData['meta_json'] ?? null,
                        ]);
                    } elseif ($session->status === 'connected') {
                        // Already connected - just update heartbeat
                        $session->update([
                            'last_heartbeat_at' => now(),
                        ]);

                        Log::info('Session heartbeat updated', [
                            'device_id' => $session->device_id,
                        ]);
                    } elseif ($session->status === 'pending') {
                        // Still pending
                        if ($session->expires_at && $session->expires_at->isPast()) {
                            // Mark as expired if QR/pairing code expired
                            $session->update([
                                'status' => 'expired',
                                'last_heartbeat_at' => now(),
                            ]);

                            Log::info('Session marked as expired', [
                                'device_id' => $session->device_id,
                            ]);
                        } else {
                            Log::info('Session still pending', [
                                'device_id' => $session->device_id,
                                'expires_at' => $session->expires_at,
                            ]);
                        }
                    }

                    $statusData[] = $session->fresh();
                } catch (\Exception $e) {
                    Log::error("Status check failed for device {$session->device_id}", [
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);

                    // Keep the session as-is on error
                    $statusData[] = $session;
                }
            }

            return response()->json([
                'status' => 'success',
                'sessions' => $statusData,
            ]);
        } catch (Exception $e) {
            Log::error('Status check error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => $request->user()->id,
            ]);

            return response()->json([
                'status' => 'error',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Generate QR code for new device
     */
    public function generateQr(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'device_label' => 'nullable|string|max:255',
            ]);

            $user = $request->user();

            // Check device limit
            if (!$this->bridgeManager->canAddDevice($user->id)) {
                return response()->json([
                    'success' => false,
                    'error' => 'Maximum device limit reached (' . $this->bridgeManager->getDeviceLimit($user->id) . ' devices)',
                ], 400);
            }

            // CRITICAL: Clear user's dedicated bridge instance BEFORE generating QR
            // This prevents false positives from old sessions
            $this->bridgeManager->clearUserBridgeSessions($user->id);

            // Wait for cleanup
//            sleep(1);

            // Generate unique device ID
            $deviceId = $this->bridgeManager->generateDeviceId($user->id);

            // Get user's dedicated bridge instance
            $instance = $this->bridgeManager->getDedicatedInstanceForUser($user->id);

            if (!$instance) {
                return response()->json([
                    'success' => false,
                    'error' => 'No available WhatsApp bridge servers. All bridges are in use.',
                ], 503);
            }

            // Create bridge client
            $bridge = new \App\Services\BridgeClient($instance['bridge_url'], $deviceId);
            // Get QR code
            $response = $bridge->getQrCode();

            if (!$response['success'] || !$response['qr_link']) {
                return response()->json([
                    'success' => false,
                    'error' => 'Failed to generate QR code',
                ], 500);
            }

            $deviceCount = $this->bridgeManager->getDeviceCount($user->id);

            // Create session
            $session = WaSession::create([
                'user_id' => $user->id,
                'device_id' => $deviceId,
                'device_label' => $validated['device_label'] ?? 'Device ' . ($deviceCount + 1),
                'bridge_instance_url' => $instance['url'],
                'bridge_instance_port' => $instance['port'],
                'is_primary' => $deviceCount === 0,
                'status' => 'pending',
                'meta_json' => [
                    'qr_link' => $response['qr_link'],
                    'qr_duration' => $response['qr_duration'],
                    'method' => 'qr',
                ],
                'expires_at' => now()->addSeconds($response['qr_duration'] ?? 30),
            ]);

            AuditLog::log('qr_generated', 'WaSession', $session->id);

            Log::info('QR generated successfully', [
                'user_id' => $user->id,
                'device_id' => $deviceId,
                'bridge' => $instance['bridge_url'],
            ]);

            return response()->json([
                'success' => true,
                'qr_code' => $response['qr_link'],
                'device_id' => $deviceId,
                'session' => $session,
                'expires_in' => $response['qr_duration'] ?? 30,
            ]);
        } catch (Exception $e) {
            Log::error('Failed to generate QR code', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => $request->user()->id,
            ]);

            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Generate pairing code for new device
     */
    public function generatePairing(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'phone' => 'required|string|min:10|max:20',
                'device_label' => 'nullable|string|max:255',
            ]);

            $user = $request->user();

            // Check device limit
            if (!$this->bridgeManager->canAddDevice($user->id)) {
                return response()->json([
                    'success' => false,
                    'error' => 'Maximum device limit reached (' . $this->bridgeManager->getDeviceLimit($user->id) . ' devices)',
                ], 400);
            }

            // Generate unique device ID
            $deviceId = $this->bridgeManager->generateDeviceId($user->id);

            // Get available bridge instance
            $bridge = $this->bridgeManager->createClientForNewSession($deviceId);

            if (!$bridge) {
                return response()->json([
                    'success' => false,
                    'error' => 'No available WhatsApp bridge servers',
                ], 503);
            }

            // Get pairing code
            $response = $bridge->getPairingCode($validated['phone']);

            // Get instance assignment
            $instance = $this->bridgeManager->getLeastLoadedInstance();

            $deviceCount = $this->bridgeManager->getDeviceCount($user->id);

            // Create session
            $session = WaSession::create([
                'user_id' => $user->id,
                'device_id' => $deviceId,
                'device_label' => $validated['device_label'] ?? 'Device ' . ($deviceCount + 1),
                'bridge_instance_url' => $instance['url'],
                'bridge_instance_port' => $instance['port'],
                'is_primary' => $deviceCount === 0,
                'status' => 'pending',
                'meta_json' => [
                    'pairing_code' => $response['code'] ?? null,
                    'pairing_phone' => $validated['phone'],
                    'method' => 'pairing',
                ],
                'expires_at' => now()->addMinutes(5),
            ]);

            AuditLog::log('pairing_code_generated', 'WaSession', $session->id);

            return response()->json([
                'success' => true,
                'pairing_code' => $response['code'] ?? null,
                'device_id' => $deviceId,
                'session' => $session,
            ]);
        } catch (Exception $e) {
            Log::error('Failed to generate pairing code', [
                'error' => $e->getMessage(),
                'user_id' => $request->user()->id,
            ]);

            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Disconnect specific device
     */
    public function destroy(Request $request, string $deviceId): RedirectResponse
    {
        try {
            $user = $request->user();
            $session = WaSession::where('user_id', $user->id)
                ->where('device_id', $deviceId)
                ->firstOrFail();

            // Call bridge to disconnect
            try {
                $bridge = $this->bridgeManager->getClientForSession($session);
                $bridge->logout();
            } catch (Exception $e) {
                Log::warning('Bridge disconnect failed', [
                    'device_id' => $deviceId,
                    'error' => $e->getMessage(),
                ]);
            }

            // Delete session
            $session->delete();

            AuditLog::log('device_disconnected', 'WaSession', $session->id);

            return redirect()->route('wa.connect')
                ->with('success', 'Device disconnected successfully.');
        } catch (Exception $e) {
            Log::error('Disconnect error', [
                'device_id' => $deviceId,
                'error' => $e->getMessage(),
            ]);

            return redirect()->route('wa.connect')
                ->with('error', 'Failed to disconnect device.');
        }
    }

    /**
     * Set device as primary
     */
    public function setPrimary(Request $request, string $deviceId): JsonResponse
    {
        try {
            $user = $request->user();

            // Remove primary flag from all devices
            WaSession::where('user_id', $user->id)
                ->update(['is_primary' => false]);

            // Set new primary
            $session = WaSession::where('user_id', $user->id)
                ->where('device_id', $deviceId)
                ->firstOrFail();

            $session->update(['is_primary' => true]);

            AuditLog::log('primary_device_changed', 'WaSession', $session->id);

            return response()->json([
                'success' => true,
                'message' => 'Primary device updated',
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Refresh expired QR code
     */
    public function refreshQr(Request $request, string $deviceId): JsonResponse
    {
        try {
            $user = $request->user();
            $session = WaSession::where('user_id', $user->id)
                ->where('device_id', $deviceId)
                ->where('status', 'pending')
                ->firstOrFail();

            $bridge = $this->bridgeManager->getClientForSession($session);
            $response = $bridge->getQrCode();

            if (!$response['success'] || !$response['qr_link']) {
                return response()->json([
                    'success' => false,
                    'error' => 'Failed to generate QR code',
                ], 500);
            }

            // Update session with new QR
            $session->update([
                'meta_json' => array_merge($session->meta_json ?? [], [
                    'qr_link' => $response['qr_link'],
                    'qr_duration' => $response['qr_duration'],
                ]),
                'expires_at' => now()->addSeconds($response['qr_duration'] ?? 30),
            ]);

            AuditLog::log('qr_refreshed', 'WaSession', $session->id);

            return response()->json([
                'success' => true,
                'qr_code' => $response['qr_link'],
                'expires_in' => $response['qr_duration'] ?? 30,
            ]);
        } catch (Exception $e) {
            Log::error('Failed to refresh QR code', [
                'error' => $e->getMessage(),
                'device_id' => $deviceId,
            ]);

            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Manually sync contacts for a connected device
     */
    public function syncContacts(Request $request, string $deviceId): JsonResponse
    {
        try {
            $user = $request->user();
            $session = WaSession::where('user_id', $user->id)
                ->where('device_id', $deviceId)
                ->where('status', 'connected')
                ->firstOrFail();

            SyncWhatsAppContacts::dispatch($session);

            AuditLog::log('contact_sync_requested', 'WaSession', $session->id);

            return response()->json([
                'success' => true,
                'message' => 'Contact sync started',
            ]);
        } catch (Exception $e) {
            Log::error('Failed to sync contacts', [
                'device_id' => $deviceId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
