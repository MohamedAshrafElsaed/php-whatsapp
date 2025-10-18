<?php

namespace App\Services;

use App\Models\WaSession;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class BridgeManager
{
    /**
     * Get available bridge instances from config
     * This supports both env-based and config-based setup
     */
    private function getAvailableInstances(): array
    {
        // Try config first (supports Laravel config caching)
        $configInstances = config('services.bridge.instances', []);

        if (!empty($configInstances)) {
            return $configInstances;
        }

        // Fallback: Single bridge for all users (shared mode)
        return [
            [
                'url' => config('services.bridge.url', 'http://localhost'),
                'port' => config('services.bridge.port', 3001),
                'max_sessions' => config('services.bridge.max_sessions_per_instance', 50),
            ],
        ];
    }

    /**
     * Get available port for a new session
     * Returns the first available port in the range
     */
    public function getAvailablePort(int $userId): ?array
    {
        $instances = $this->getAvailableInstances();

        foreach ($instances as $instance) {
            $portRangeStart = $instance['port_range_start'];
            $portRangeEnd = $instance['port_range_end'];

            // Get all used ports for this instance
            $usedPorts = WaSession::where('bridge_instance_url', $instance['url'])
                ->whereBetween('bridge_instance_port', [$portRangeStart, $portRangeEnd])
                ->whereIn('status', ['pending', 'connected'])
                ->pluck('bridge_instance_port')
                ->toArray();

            Log::info('Checking available ports', [
                'url' => $instance['url'],
                'port_range' => "{$portRangeStart}-{$portRangeEnd}",
                'used_ports_count' => count($usedPorts),
            ]);

            // Find first available port in range
            for ($port = $portRangeStart; $port <= $portRangeEnd; $port++) {
                if (!in_array($port, $usedPorts)) {
                    Log::info('Available port found', [
                        'url' => $instance['url'],
                        'port' => $port,
                        'user_id' => $userId,
                    ]);

                    return [
                        'url' => $instance['url'],
                        'port' => $port,
                        'bridge_url' => rtrim($instance['url'], '/') . '/port/' . $port,  // ✅ FIXED: Use /port/{port}/ path
                    ];
                }
            }
        }

        Log::warning('No available ports in any instance', [
            'user_id' => $userId,
            'total_instances' => count($instances),
        ]);

        return null;
    }

    /**
     * AUTOMATIC: Get or assign bridge instance for user
     * No manual configuration needed!
     */
    public function getDedicatedInstanceForUser(int $userId): ?array
    {
        $instances = $this->getAvailableInstances();

        // Strategy 1: Check if user already has a session (stick to same port)
        $existingSession = WaSession::where('user_id', $userId)
            ->whereNotNull('bridge_instance_url')
            ->whereNotNull('bridge_instance_port')
            ->whereIn('status', ['pending', 'connected'])
            ->orderBy('created_at', 'desc')
            ->first();

        if ($existingSession && $existingSession->bridge_instance_port) {
            $assignedBridge = [
                'url' => $existingSession->bridge_instance_url,
                'port' => $existingSession->bridge_instance_port,
                'bridge_url' => rtrim($existingSession->bridge_instance_url, '/') . '/port/' . $existingSession->bridge_instance_port,  // ✅ FIXED
            ];

            Log::info('Reusing existing port for user', [
                'user_id' => $userId,
                'port' => $existingSession->bridge_instance_port,
                'bridge_url' => $assignedBridge['bridge_url'],
            ]);

            return $assignedBridge;
        }

        // Strategy 2: Get new available port
        return $this->getAvailablePort($userId);
    }

    /**
     * Get the least loaded bridge instance
     */
    public function getLeastLoadedInstance(): ?array
    {
        return $this->getDedicatedInstanceForUser(auth()->id());
    }

    /**
     * Get bridge client for specific session
     */
    public function getClientForSession(WaSession $session): BridgeClient
    {
        $bridgeUrl = $session->getBridgeUrl();
        $deviceId = $session->device_id;

        return new BridgeClient($bridgeUrl, $deviceId);
    }

    /**
     * Create new bridge client for new session
     */
    public function createClientForNewSession(string $deviceId, int $userId): ?BridgeClient
    {
        $instance = $this->getDedicatedInstanceForUser($userId);

        if (!$instance) {
            return null;
        }

        return new BridgeClient($instance['bridge_url'], $deviceId);
    }

    /**
     * Check health of all bridge instances
     */
    public function checkAllInstancesHealth(): array
    {
        $instances = $this->getAvailableInstances();
        $health = [];

        foreach ($instances as $instance) {
            $portRangeStart = $instance['port_range_start'] ?? $instance['port'];
            $portRangeEnd = $instance['port_range_end'] ?? $instance['port'];

            // Count unique users (not sessions)
            $activeUsers = WaSession::where('bridge_instance_url', $instance['url'])
                ->whereBetween('bridge_instance_port', [$portRangeStart, $portRangeEnd])
                ->whereIn('status', ['connected', 'pending'])
                ->distinct('user_id')
                ->count('user_id');

            $activeDevices = WaSession::where('bridge_instance_url', $instance['url'])
                ->whereBetween('bridge_instance_port', [$portRangeStart, $portRangeEnd])
                ->whereIn('status', ['connected', 'pending'])
                ->count();

            $maxSessions = $instance['max_sessions'];
            $availablePorts = $maxSessions - $activeDevices;
            $capacityPercent = $maxSessions > 0 ? round(($activeDevices / $maxSessions) * 100, 2) : 0;

            $health[] = [
                'url' => $instance['url'],
                'port_range' => $portRangeStart . '-' . $portRangeEnd,
                'healthy' => true,
                'active_users' => $activeUsers,
                'active_devices' => $activeDevices,
                'max_sessions' => $maxSessions,
                'available_ports' => $availablePorts,
                'capacity_percent' => $capacityPercent,
            ];
        }

        return $health;
    }

    /**
     * Check if single bridge instance is healthy
     */
    private function checkInstanceHealth(string $url): bool
    {
        $cacheKey = "bridge_health_" . md5($url);

        return Cache::remember($cacheKey, 60, function () use ($url) {
            try {
                $response = \Http::timeout(5)->get("{$url}/health");
                return $response->successful();
            } catch (\Exception $e) {
                Log::warning("Bridge health check failed for {$url}", [
                    'error' => $e->getMessage(),
                ]);
                return false;
            }
        });
    }

    /**
     * Generate unique device ID for user
     */
    public function generateDeviceId(int $userId): string
    {
        return 'device_' . $userId . '_' . time() . '_' . bin2hex(random_bytes(4));
    }

    /**
     * Get bridge instance assignment for session
     */
    public function getInstanceAssignment(WaSession $session): ?array
    {
        if ($session->bridge_instance_url && $session->bridge_instance_port) {
            return [
                'url' => $session->bridge_instance_url,
                'port' => $session->bridge_instance_port,
            ];
        }

        return $this->getDedicatedInstanceForUser($session->user_id);
    }

    /**
     * Get user's device limit
     */
    public function getDeviceLimit(int $userId): int
    {
        return config('services.bridge.max_devices_per_user', 1);
    }

    /**
     * Check if user can add more devices
     */
    public function canAddDevice(int $userId): bool
    {
        $currentCount = WaSession::where('user_id', $userId)
            ->whereIn('status', ['pending', 'connected'])
            ->count();
        return $currentCount < $this->getDeviceLimit($userId);
    }

    /**
     * Get user's device count
     */
    public function getDeviceCount(int $userId): int
    {
        return WaSession::where('user_id', $userId)->count();
    }

    /**
     * Clear user's bridge sessions (logout from specific port)
     */
    public function clearUserBridgeSessions(int $userId): void
    {
        $sessions = WaSession::where('user_id', $userId)
            ->whereIn('status', ['pending', 'connected'])
            ->get();

        foreach ($sessions as $session) {
            try {
                $bridge = $this->getClientForSession($session);
                $bridge->logout();

                Log::info('Cleared session for user', [
                    'user_id' => $userId,
                    'port' => $session->bridge_instance_port,
                ]);
            } catch (\Exception $e) {
                Log::warning('Failed to clear session', [
                    'user_id' => $userId,
                    'port' => $session->bridge_instance_port,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
