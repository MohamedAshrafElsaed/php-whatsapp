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
     * AUTOMATIC: Get or assign bridge instance for user
     * No manual configuration needed!
     */
    public function getDedicatedInstanceForUser(int $userId): ?array
    {
        $instances = $this->getAvailableInstances();

        // Strategy 1: Check if user already has a session (stick to same bridge)
        $existingSession = WaSession::where('user_id', $userId)
            ->whereNotNull('bridge_instance_url')
            ->whereNotNull('bridge_instance_port')
            ->first();

        if ($existingSession) {
            $assignedBridge = [
                'url' => $existingSession->bridge_instance_url,
                'port' => $existingSession->bridge_instance_port,
                'max_sessions' => config('services.bridge.max_devices_per_user', 3),
            ];

            Log::info('Reusing existing bridge for user', [
                'user_id' => $userId,
                'bridge' => $assignedBridge,
            ]);

            return $assignedBridge;
        }

        // Strategy 2: Find least loaded bridge (automatic load balancing)
        $leastLoadedBridge = null;
        $lowestLoad = PHP_INT_MAX;

        foreach ($instances as $instance) {
            // Count active users on this bridge (not sessions, users!)
            $activeUsers = WaSession::where('bridge_instance_url', $instance['url'])
                ->where('bridge_instance_port', $instance['port'])
                ->whereIn('status', ['connected', 'pending'])
                ->distinct('user_id')
                ->count('user_id');

            // Calculate user capacity per bridge
            $userCapacity = floor($instance['max_sessions'] / config('services.bridge.max_devices_per_user', 3));

            if ($activeUsers < $userCapacity && $activeUsers < $lowestLoad) {
                $lowestLoad = $activeUsers;
                $leastLoadedBridge = $instance;
            }
        }

        if ($leastLoadedBridge) {
            Log::info('Auto-assigned user to least loaded bridge', [
                'user_id' => $userId,
                'bridge' => $leastLoadedBridge,
                'current_users' => $lowestLoad,
            ]);

            return $leastLoadedBridge;
        }

        Log::error('All bridge instances at capacity', [
            'user_id' => $userId,
            'total_instances' => count($instances),
        ]);

        return null;
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
    public function createClientForNewSession(string $deviceId): ?BridgeClient
    {
        $instance = $this->getDedicatedInstanceForUser(auth()->id());

        if (!$instance) {
            return null;
        }

        $bridgeUrl = rtrim($instance['url'], '/') . ':' . $instance['port'];

        return new BridgeClient($bridgeUrl, $deviceId);
    }

    /**
     * Check health of all bridge instances
     */
    public function checkAllInstancesHealth(): array
    {
        $instances = $this->getAvailableInstances();
        $health = [];

        foreach ($instances as $instance) {
            $url = rtrim($instance['url'], '/') . ':' . $instance['port'];
            $isHealthy = $this->checkInstanceHealth($url);

            // Count unique users (not sessions)
            $activeUsers = WaSession::where('bridge_instance_url', $instance['url'])
                ->where('bridge_instance_port', $instance['port'])
                ->whereIn('status', ['connected', 'pending'])
                ->distinct('user_id')
                ->count('user_id');

            $activeDevices = WaSession::where('bridge_instance_url', $instance['url'])
                ->where('bridge_instance_port', $instance['port'])
                ->whereIn('status', ['connected', 'pending'])
                ->count();

            $userCapacity = floor($instance['max_sessions'] / config('services.bridge.max_devices_per_user', 3));

            $health[] = [
                'url' => $url,
                'healthy' => $isHealthy,
                'active_users' => $activeUsers,
                'active_devices' => $activeDevices,
                'max_users' => $userCapacity,
                'max_devices' => $instance['max_sessions'],
                'user_capacity' => round(($activeUsers / $userCapacity) * 100, 2) . '%',
                'device_capacity' => round(($activeDevices / $instance['max_sessions']) * 100, 2) . '%',
                'available_user_slots' => $userCapacity - $activeUsers,
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
                $response = \Http::timeout(5)->get("{$url}/app/devices");
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
        return config('services.bridge.max_devices_per_user', 3);
    }

    /**
     * Check if user can add more devices
     */
    public function canAddDevice(int $userId): bool
    {
        $currentCount = WaSession::where('user_id', $userId)->count();
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
     * Clear all sessions for a user's bridge instance
     * Only clears if user is the ONLY user on that bridge
     */
    public function clearUserBridgeSessions(int $userId): void
    {
        $instance = $this->getDedicatedInstanceForUser($userId);

        if (!$instance) {
            return;
        }

        // Count how many OTHER users are on this bridge
        $otherUsers = WaSession::where('bridge_instance_url', $instance['url'])
            ->where('bridge_instance_port', $instance['port'])
            ->where('user_id', '!=', $userId)
            ->whereIn('status', ['connected', 'pending'])
            ->distinct('user_id')
            ->count('user_id');

        // Only clear if this user is alone on the bridge
        if ($otherUsers === 0) {
            $bridgeUrl = rtrim($instance['url'], '/') . ':' . $instance['port'];
            $bridge = new BridgeClient($bridgeUrl, 'temp');

            try {
                $bridge->logout();
                Log::info('Cleared bridge sessions (user was alone on bridge)', [
                    'user_id' => $userId,
                    'bridge_url' => $bridgeUrl,
                ]);
            } catch (\Exception $e) {
                Log::warning('Failed to clear bridge sessions', [
                    'user_id' => $userId,
                    'error' => $e->getMessage(),
                ]);
            }
        } else {
            Log::info('Skipped bridge clear (other users present)', [
                'user_id' => $userId,
                'other_users' => $otherUsers,
            ]);
        }
    }
}
