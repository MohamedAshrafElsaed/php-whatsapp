<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BridgeClient
{
    private string $baseUrl;
    private string $token;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('services.bridge.url'), '/');
        $this->token = config('services.bridge.token');
    }

    /**
     * Create or refresh WhatsApp session and get QR code
     */
    public function createSession(int $userId): array
    {
        try {
            $response = Http::withHeaders([
                'X-BRIDGE-TOKEN' => $this->token,
                'Accept' => 'application/json',
            ])->post("{$this->baseUrl}/api/sessions", [
                'user_id' => $userId,
            ]);

            if ($response->failed()) {
                throw new \Exception('Failed to create session: ' . $response->body());
            }

            return $response->json();
        } catch (\Exception $e) {
            Log::error('BridgeClient createSession error', [
                'error' => $e->getMessage(),
                'user_id' => $userId,
            ]);
            throw $e;
        }
    }

    /**
     * Get current session status
     */
    public function getStatus(int $userId): array
    {
        try {
            $response = Http::withHeaders([
                'X-BRIDGE-TOKEN' => $this->token,
                'Accept' => 'application/json',
            ])->get("{$this->baseUrl}/api/sessions/{$userId}/status");

            if ($response->failed()) {
                throw new \Exception('Failed to get status: ' . $response->body());
            }

            return $response->json();
        } catch (\Exception $e) {
            Log::error('BridgeClient getStatus error', [
                'error' => $e->getMessage(),
                'user_id' => $userId,
            ]);
            throw $e;
        }
    }

    /**
     * Refresh QR code
     */
    public function refreshQr(int $userId): array
    {
        try {
            $response = Http::withHeaders([
                'X-BRIDGE-TOKEN' => $this->token,
                'Accept' => 'application/json',
            ])->post("{$this->baseUrl}/api/sessions/{$userId}/refresh");

            if ($response->failed()) {
                throw new \Exception('Failed to refresh QR: ' . $response->body());
            }

            return $response->json();
        } catch (\Exception $e) {
            Log::error('BridgeClient refreshQr error', [
                'error' => $e->getMessage(),
                'user_id' => $userId,
            ]);
            throw $e;
        }
    }

    /**
     * Disconnect session
     */
    public function disconnect(int $userId): array
    {
        try {
            $response = Http::withHeaders([
                'X-BRIDGE-TOKEN' => $this->token,
                'Accept' => 'application/json',
            ])->delete("{$this->baseUrl}/api/sessions/{$userId}");

            if ($response->failed()) {
                throw new \Exception('Failed to disconnect: ' . $response->body());
            }

            return $response->json();
        } catch (\Exception $e) {
            Log::error('BridgeClient disconnect error', [
                'error' => $e->getMessage(),
                'user_id' => $userId,
            ]);
            throw $e;
        }
    }

    /**
     * Send bulk messages
     */
    public function sendMessage(int $userId, string $phone, string $message): array
    {
        try {
            $response = Http::withHeaders([
                'X-BRIDGE-TOKEN' => $this->token,
                'Accept' => 'application/json',
            ])->post("{$this->baseUrl}/api/messages/send", [
                'user_id' => $userId,
                'phone' => $phone,
                'message' => $message,
            ]);

            if ($response->failed()) {
                throw new \Exception('Failed to send message: ' . $response->body());
            }

            return $response->json();
        } catch (\Exception $e) {
            Log::error('BridgeClient sendMessage error', [
                'error' => $e->getMessage(),
                'user_id' => $userId,
                'phone' => $phone,
            ]);
            throw $e;
        }
    }
}
