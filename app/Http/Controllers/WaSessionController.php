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
                    Log::info('🔍 Checking session', [
                        'device_id' => $session->device_id,
                        'current_status' => $session->status,
                        'bridge_url' => $session->getBridgeUrl(),
                    ]);

                    $bridge = $this->bridgeManager->getClientForSession($session);

                    // Check devices first (safe to call anytime)
                    $devicesResponse = $bridge->getDevices();

                    Log::info('📡 Bridge response', [
                        'device_id' => $session->device_id,
                        'success' => $devicesResponse['success'] ?? false,
                        'raw_response' => json_encode($devicesResponse),
                    ]);

                    // Check for devices in the correct nested structure
                    $hasDevices = false;
                    $deviceInfo = null;

                    if (
                        isset($devicesResponse['success']) &&
                        $devicesResponse['success'] === true &&
                        isset($devicesResponse['data']['results']) &&
                        is_array($devicesResponse['data']['results']) &&
                        count($devicesResponse['data']['results']) > 0
                    ) {
                        $hasDevices = true;
                        $deviceInfo = $devicesResponse['data']['results'][0];

                        Log::info('✅ DEVICES FOUND!', [
                            'device_id' => $session->device_id,
                            'device_info' => $deviceInfo,
                        ]);
                    } else {
                        Log::info('❌ NO DEVICES FOUND', [
                            'device_id' => $session->device_id,
                            'response_structure' => [
                                'has_success_key' => isset($devicesResponse['success']),
                                'success_value' => $devicesResponse['success'] ?? null,
                                'has_data_key' => isset($devicesResponse['data']),
                                'has_results_key' => isset($devicesResponse['data']['results']),
                                'results_is_array' => isset($devicesResponse['data']['results']) && is_array($devicesResponse['data']['results']),
                                'results_count' => isset($devicesResponse['data']['results']) ? count($devicesResponse['data']['results']) : 0,
                            ],
                        ]);
                    }

                    Log::info('🔄 Current session status', [
                        'device_id' => $session->device_id,
                        'status' => $session->status,
                        'has_devices' => $hasDevices,
                        'will_update' => $hasDevices && in_array($session->status, ['pending', 'disconnected', 'expired']),
                    ]);

                    // Update status based on device presence
                    if ($hasDevices && in_array($session->status, ['pending', 'disconnected', 'expired'])) {
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

                        Log::info('💾 UPDATING SESSION TO CONNECTED', [
                            'device_id' => $session->device_id,
                            'old_status' => $session->status,
                            'new_status' => 'connected',
                            'update_data' => $updateData,
                        ]);

                        $session->update($updateData);

                        // Dispatch contact sync
                        SyncWhatsAppContacts::dispatch($session->fresh());

                        Log::info('✅ SESSION UPDATED SUCCESSFULLY', [
                            'device_id' => $session->device_id,
                            'new_status' => $session->fresh()->status,
                        ]);

                    } elseif ($session->status === 'connected' && $hasDevices) {
                        // Already connected and still has devices - just update heartbeat
                        $session->update([
                            'last_heartbeat_at' => now(),
                        ]);

                        Log::info('💓 Heartbeat updated', [
                            'device_id' => $session->device_id,
                        ]);

                    } elseif ($session->status === 'connected' && !$hasDevices) {
                        // Was connected but no devices found = disconnected
                        $session->update([
                            'status' => 'disconnected',
                            'last_heartbeat_at' => now(),
                        ]);

                        Log::info('🔌 Session disconnected (no devices)', [
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

                            Log::info('⏰ Session expired', [
                                'device_id' => $session->device_id,
                            ]);
                        }
                    }

                    $statusData[] = $session->fresh();

                } catch (\Exception $e) {
                    Log::error("❌ Status check failed for device {$session->device_id}", [
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
            Log::error('❌ Status check error', [
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

            // ✅ FIXED: Use bridge_url directly from instance
//            $bridge = new \App\Services\BridgeClient('http://localhost:3001', $deviceId);
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
                'bridge_url' => $instance['bridge_url'],  // ✅ Log the correct URL
                'port' => $instance['port'],
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

    // In WaSessionController.php - Add these new methods

    /**
     * Reconnect an expired/disconnected device
     */
    public function reconnect(Request $request, string $deviceId): JsonResponse
    {
        try {
            $validated = $request->validate([
                'method' => 'required|in:qr,pairing',
                'phone' => 'required_if:method,pairing|string|min:10|max:20',
            ]);

            $user = $request->user();

            // Find existing session
            $session = WaSession::where('user_id', $user->id)
                ->where('device_id', $deviceId)
                ->firstOrFail();

            // Check if session can be reconnected
            if (!$session->canReconnect()) {
                return response()->json([
                    'success' => false,
                    'error' => 'This device cannot be reconnected. Current status: ' . $session->status,
                ], 400);
            }

            // Clean up old bridge session first
            try {
                $bridge = $this->bridgeManager->getClientForSession($session);
                $bridge->logout();

                Log::info('Cleaned up old bridge session before reconnection', [
                    'device_id' => $deviceId,
                    'old_status' => $session->status,
                ]);
            } catch (Exception $e) {
                Log::warning('Failed to cleanup old session, continuing anyway', [
                    'device_id' => $deviceId,
                    'error' => $e->getMessage(),
                ]);
            }

            // Prepare session for reconnection
            $session->prepareForReconnection();

            // Get bridge client for this session (reuse same instance)
            $bridge = $this->bridgeManager->getClientForSession($session);

            // Generate new QR or pairing based on method
            if ($validated['method'] === 'qr') {
                $response = $bridge->getQrCode();

                if (!$response['success'] || !$response['qr_link']) {
                    return response()->json([
                        'success' => false,
                        'error' => 'Failed to generate QR code',
                    ], 500);
                }

                $session->update([
                    'meta_json' => array_merge($session->meta_json ?? [], [
                        'qr_link' => $response['qr_link'],
                        'qr_duration' => $response['qr_duration'],
                        'method' => 'qr',
                    ]),
                    'expires_at' => now()->addSeconds($response['qr_duration'] ?? 30),
                ]);

                AuditLog::log('session_reconnect_qr', 'WaSession', $session->id);

                return response()->json([
                    'success' => true,
                    'method' => 'qr',
                    'qr_code' => $response['qr_link'],
                    'expires_in' => $response['qr_duration'] ?? 30,
                    'session' => $session->fresh(),
                ]);

            } else {
                // Pairing method
                $response = $bridge->getPairingCode($validated['phone']);

                $session->update([
                    'meta_json' => array_merge($session->meta_json ?? [], [
                        'pairing_code' => $response['code'] ?? null,
                        'pairing_phone' => $validated['phone'],
                        'method' => 'pairing',
                    ]),
                    'expires_at' => now()->addMinutes(5),
                ]);

                AuditLog::log('session_reconnect_pairing', 'WaSession', $session->id);

                return response()->json([
                    'success' => true,
                    'method' => 'pairing',
                    'pairing_code' => $response['code'] ?? null,
                    'session' => $session->fresh(),
                ]);
            }

        } catch (Exception $e) {
            Log::error('Failed to reconnect device', [
                'device_id' => $deviceId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
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
            $instance = $this->bridgeManager->getDedicatedInstanceForUser($user->id);

            if (!$instance) {
                return response()->json([
                    'success' => false,
                    'error' => 'No available WhatsApp bridge servers',
                ], 503);
            }

            // ✅ FIXED: Use bridge_url directly
            $bridge = new \App\Services\BridgeClient($instance['bridge_url'], $deviceId);

            // Get pairing code
            $response = $bridge->getPairingCode($validated['phone']);

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

    // In WaSessionController.php - Update the destroy method

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

            // Mark as disconnected instead of deleting (allows reconnection)
            $session->update([
                'status' => 'disconnected',
                'last_seen_at' => now(),
            ]);

            AuditLog::log('device_disconnected', 'WaSession', $session->id);

            return redirect()->route('wa.connect')
                ->with('success', 'Device disconnected successfully. You can reconnect it later.');
        } catch (Exception $e) {
            Log::error('Disconnect error', [
                'device_id' => $deviceId,
                'error' => $e->getMessage(),
            ]);

            return redirect()->route('wa.connect')
                ->with('error', 'Failed to disconnect device.');
        }
    }

    // In WaSessionController.php - Add this new method

    /**
     * Permanently delete a device session
     */
    public function forceDelete(Request $request, string $deviceId): RedirectResponse
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
                Log::warning('Bridge disconnect failed during deletion', [
                    'device_id' => $deviceId,
                    'error' => $e->getMessage(),
                ]);
            }

            // Permanently delete
            $session->delete();

            AuditLog::log('device_permanently_deleted', 'WaSession', $session->id);

            return redirect()->route('wa.connect')
                ->with('success', 'Device removed permanently.');
        } catch (Exception $e) {
            Log::error('Force delete error', [
                'device_id' => $deviceId,
                'error' => $e->getMessage(),
            ]);

            return redirect()->route('wa.connect')
                ->with('error', 'Failed to delete device.');
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

            // ✅ FIXED: Allow refresh for both pending AND expired sessions
            $session = WaSession::where('user_id', $user->id)
                ->where('device_id', $deviceId)
                ->whereIn('status', ['pending', 'expired'])
                ->firstOrFail();

            $bridge = $this->bridgeManager->getClientForSession($session);
            $response = $bridge->getQrCode();

            if (!$response['success'] || !$response['qr_link']) {
                return response()->json([
                    'success' => false,
                    'error' => 'Failed to generate QR code',
                ], 500);
            }

            // ✅ FIXED: Update status back to pending when refreshing
            $session->update([
                'status' => 'pending',
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
