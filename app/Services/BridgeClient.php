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
     * Create or refresh WhatsApp session (queues connection request)
     */
    public function createSession(int $userId, ?array $credentials = null): array
    {
        try {
            $payload = [
                'user_id' => $userId,
            ];

            // Include credentials if provided (for database-backed auth)
            if ($credentials !== null) {
                $payload['credentials'] = $credentials;
            }

            $response = Http::withHeaders([
                'X-BRIDGE-TOKEN' => $this->token,
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ])->post("{$this->baseUrl}/session/connect", $payload);

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
     * Create session with pairing code
     */
    public function createSessionWithPairing(int $userId, string $phone): array
    {
        try {
            $response = Http::withHeaders([
                'X-BRIDGE-TOKEN' => $this->token,
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ])->post("{$this->baseUrl}/session/connect", [
                'user_id' => $userId,
                'pairing_phone' => $phone,
            ]);

            if ($response->failed()) {
                throw new \Exception('Failed to create pairing session: ' . $response->body());
            }

            return $response->json();
        } catch (\Exception $e) {
            Log::error('BridgeClient createSessionWithPairing error', [
                'error' => $e->getMessage(),
                'user_id' => $userId,
                'phone' => $phone,
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
            ])->get("{$this->baseUrl}/session/status/{$userId}");

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
     * Get QR code for session
     */
    public function getQr(int $userId): array
    {
        try {
            $response = Http::withHeaders([
                'X-BRIDGE-TOKEN' => $this->token,
                'Accept' => 'application/json',
            ])->get("{$this->baseUrl}/session/qr/{$userId}");

            if ($response->failed()) {
                throw new \Exception('Failed to get QR: ' . $response->body());
            }

            return $response->json();
        } catch (\Exception $e) {
            Log::error('BridgeClient getQr error', [
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
                'Content-Type' => 'application/json',
            ])->post("{$this->baseUrl}/session/disconnect", [
                'user_id' => $userId,
            ]);

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
     * Send message via WhatsApp
     */
    public function sendMessage(int $userId, string $phone, string $message): array
    {
        try {
            $response = Http::withHeaders([
                'X-BRIDGE-TOKEN' => $this->token,
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ])->post("{$this->baseUrl}/session/send", [
                'user_id' => $userId,
                'phone' => $phone,
                'message' => $message,
                'default_country' => 'EG',
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

    /**
     * Refresh QR code (creates new session with force_new flag)
     */
    public function refreshQr(int $userId): array
    {
        try {
            $response = Http::withHeaders([
                'X-BRIDGE-TOKEN' => $this->token,
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ])->post("{$this->baseUrl}/session/connect", [
                'user_id' => $userId,
                'force_new' => true
            ]);

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
}
