<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BridgeClient
{
    private string $baseUrl;
    private string $token;
    private string $deviceId;

    public function __construct(?string $baseUrl = null, ?string $deviceId = null)
    {
        $this->baseUrl = $baseUrl ?? rtrim(config('services.bridge.url'), '/');
        $this->token = config('services.bridge.token');
        $this->deviceId = $deviceId ?? 'default';
    }

    /**
     * APP ENDPOINTS
     */

    /**
     * Get all connected devices
     * GET /app/devices
     */
    public function getDevices(): array
    {
        try {
            $response = Http::timeout(10)->get("{$this->baseUrl}/app/devices");

            if ($response->successful()) {
                $data = $response->json();

                Log::info('BridgeClient getDevices response', [
                    'base_url' => $this->baseUrl,
                    'response' => $data,
                ]);

                return [
                    'success' => true,
                    'data' => $data,
                ];
            }

            Log::warning('BridgeClient getDevices failed', [
                'base_url' => $this->baseUrl,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return ['success' => false, 'data' => null];
        } catch (\Exception $e) {
            Log::error('BridgeClient getDevices error', [
                'base_url' => $this->baseUrl,
                'error' => $e->getMessage(),
            ]);
            return ['success' => false, 'data' => null];
        }
    }

    /**
     * Generate QR code for login
     * GET /app/login
     */
    public function getQrCode(): array
    {
        try {
            $response = Http::timeout(30)->get("{$this->baseUrl}/app/login");

            if ($response->failed()) {
                throw new \Exception('Failed to get QR code: ' . $response->body());
            }

            $data = $response->json();

            return [
                'success' => isset($data['code']) && $data['code'] === 'SUCCESS',
                'qr_link' => $data['results']['qr_link'] ?? null,
                'qr_duration' => $data['results']['qr_duration'] ?? 30,
            ];
        } catch (\Exception $e) {
            Log::error('BridgeClient getQrCode error', [
                'base_url' => $this->baseUrl,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Generate pairing code
     * GET /app/login-with-code?phone={phone}
     */
    public function getPairingCode(string $phone): array
    {
        try {
            $response = Http::timeout(30)->get("{$this->baseUrl}/app/login-with-code", [
                'phone' => $phone,
            ]);

            if ($response->failed()) {
                throw new \Exception('Failed to get pairing code: ' . $response->body());
            }

            $data = $response->json();

            return [
                'success' => isset($data['code']) && $data['code'] === 'SUCCESS',
                'code' => $data['results']['code'] ?? null,
            ];
        } catch (\Exception $e) {
            Log::error('BridgeClient getPairingCode error', [
                'base_url' => $this->baseUrl,
                'phone' => $phone,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Logout
     * GET /app/logout
     */
    public function logout(): array
    {
        try {
            $response = Http::timeout(10)->get("{$this->baseUrl}/app/logout");

            if ($response->failed()) {
                throw new \Exception('Failed to logout: ' . $response->body());
            }

            return $response->json();
        } catch (\Exception $e) {
            Log::error('BridgeClient logout error', [
                'base_url' => $this->baseUrl,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Reconnect
     * GET /app/reconnect
     */
    public function reconnect(): array
    {
        try {
            $response = Http::timeout(30)->get("{$this->baseUrl}/app/reconnect");

            if ($response->failed()) {
                throw new \Exception('Failed to reconnect: ' . $response->body());
            }

            return $response->json();
        } catch (\Exception $e) {
            Log::error('BridgeClient reconnect error', [
                'base_url' => $this->baseUrl,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * USER ENDPOINTS
     */

    /**
     * Get user info
     * GET /user/info
     */
    public function getUserInfo(?string $phone = null): array
    {
        try {
            $url = "{$this->baseUrl}/user/info";
            if ($phone) {
                $url .= "?phone={$phone}";
            }

            $response = Http::timeout(10)->get($url);

            if ($response->failed()) {
                throw new \Exception('Failed to get user info: ' . $response->body());
            }

            $data = $response->json();

            return [
                'success' => isset($data['code']) && $data['code'] === 'SUCCESS',
                'data' => $data['results'] ?? null,
            ];
        } catch (\Exception $e) {
            Log::error('BridgeClient getUserInfo error', [
                'error' => $e->getMessage(),
            ]);
            return ['success' => false, 'data' => null];
        }
    }

    /**
     * Update push name
     * POST /user/pushname
     */
    public function updatePushname(string $pushName): array
    {
        try {
            $response = Http::timeout(10)->post("{$this->baseUrl}/user/pushname", [
                'push_name' => $pushName,
            ]);

            if ($response->failed()) {
                throw new \Exception('Failed to update pushname: ' . $response->body());
            }

            return $response->json();
        } catch (\Exception $e) {
            Log::error('BridgeClient updatePushname error', [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Get user's privacy settings
     * GET /user/my/privacy
     */
    public function getPrivacy(): array
    {
        try {
            $response = Http::timeout(10)->get("{$this->baseUrl}/user/my/privacy");

            if ($response->failed()) {
                throw new \Exception('Failed to get privacy: ' . $response->body());
            }

            return $response->json();
        } catch (\Exception $e) {
            Log::error('BridgeClient getPrivacy error', [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Check if phone has WhatsApp
     * GET /user/check?phone={phone}
     */
    public function checkPhone(string $phone): array
    {
        try {
            $response = Http::timeout(10)->get("{$this->baseUrl}/user/check", [
                'phone' => $phone,
            ]);

            if ($response->failed()) {
                throw new \Exception('Failed to check phone: ' . $response->body());
            }

            return $response->json();
        } catch (\Exception $e) {
            Log::error('BridgeClient checkPhone error', [
                'phone' => $phone,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Get my contacts
     * GET /user/my/contacts
     */
    public function getMyContacts(): array
    {
        try {
            $response = Http::timeout(10)->get("{$this->baseUrl}/user/my/contacts");

            if ($response->failed()) {
                throw new \Exception('Failed to get contacts: ' . $response->body());
            }

            return $response->json();
        } catch (\Exception $e) {
            Log::error('BridgeClient getMyContacts error', [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Get business profile
     * GET /user/business-profile?phone={phone}
     */
    public function getBusinessProfile(string $phone): array
    {
        try {
            $response = Http::timeout(10)->get("{$this->baseUrl}/user/business-profile", [
                'phone' => $phone,
            ]);

            if ($response->failed()) {
                throw new \Exception('Failed to get business profile: ' . $response->body());
            }

            return $response->json();
        } catch (\Exception $e) {
            Log::error('BridgeClient getBusinessProfile error', [
                'phone' => $phone,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * CHAT MANAGEMENT ENDPOINTS
     */

    /**
     * Get chats
     * GET /chats?offset={offset}&limit={limit}&search={search}
     */
    public function getChats(int $offset = 0, int $limit = 10, string $search = ''): array
    {
        try {
            $response = Http::timeout(10)->get("{$this->baseUrl}/chats", [
                'offset' => $offset,
                'limit' => $limit,
                'search' => $search,
                'has_media' => false,
            ]);

            if ($response->failed()) {
                throw new \Exception('Failed to get chats: ' . $response->body());
            }

            return $response->json();
        } catch (\Exception $e) {
            Log::error('BridgeClient getChats error', [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Get chat messages
     * GET /chat/{chatId}/messages?offset={offset}&limit={limit}
     */
    public function getChatMessages(string $chatId, int $offset = 0, int $limit = 20): array
    {
        try {
            $response = Http::timeout(10)->get("{$this->baseUrl}/chat/{$chatId}/messages", [
                'offset' => $offset,
                'limit' => $limit,
                'search' => '',
            ]);

            if ($response->failed()) {
                throw new \Exception('Failed to get chat messages: ' . $response->body());
            }

            return $response->json();
        } catch (\Exception $e) {
            Log::error('BridgeClient getChatMessages error', [
                'chat_id' => $chatId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Pin chat
     * POST /chat/{chatId}/pin
     */
    public function pinChat(string $chatId, bool $pinned): array
    {
        try {
            $response = Http::timeout(10)->post("{$this->baseUrl}/chat/{$chatId}/pin", [
                'pinned' => $pinned,
            ]);

            if ($response->failed()) {
                throw new \Exception('Failed to pin chat: ' . $response->body());
            }

            return $response->json();
        } catch (\Exception $e) {
            Log::error('BridgeClient pinChat error', [
                'chat_id' => $chatId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * SEND MESSAGE ENDPOINTS
     */

    /**
     * Send text message
     * POST /send/message
     */
    public function sendMessage(string $phone, string $message, bool $isForwarded = false): array
    {
        try {
            $response = Http::timeout(30)->post("{$this->baseUrl}/send/message", [
                'phone' => $phone,
                'message' => $message,
                'is_forwarded' => $isForwarded,
            ]);

            if ($response->failed()) {
                throw new \Exception('Failed to send message: ' . $response->body());
            }

            return $response->json();
        } catch (\Exception $e) {
            Log::error('BridgeClient sendMessage error', [
                'phone' => $phone,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Send image
     * POST /send/image
     */
    public function sendImage(string $phone, $imageContents, string $fileName, string $caption = '', array $options = []): array
    {
        try {
            $response = Http::timeout(90)
                ->attach('image', $imageContents, $fileName)
                ->post("{$this->baseUrl}/send/image", [
                    'phone' => $phone,
                    'caption' => $caption,
                    'view_once' => $options['view_once'] ?? false,
                    'compress' => $options['compress'] ?? true,
                    'is_forwarded' => $options['is_forwarded'] ?? false,
                ]);

            if ($response->failed()) {
                throw new \Exception('Failed to send image: ' . $response->body());
            }

            return $response->json();
        } catch (\Exception $e) {
            Log::error('BridgeClient sendImage error', [
                'phone' => $phone,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Send video
     * POST /send/video
     */
    public function sendVideo(string $phone, $videoContents, string $fileName, string $caption = '', array $options = []): array
    {
        try {
            $response = Http::timeout(120)
                ->attach('video', $videoContents, $fileName)
                ->post("{$this->baseUrl}/send/video", [
                    'phone' => $phone,
                    'caption' => $caption,
                    'view_once' => $options['view_once'] ?? false,
                    'compress' => $options['compress'] ?? true,
                    'is_forwarded' => $options['is_forwarded'] ?? false,
                ]);

            if ($response->failed()) {
                throw new \Exception('Failed to send video: ' . $response->body());
            }

            return $response->json();
        } catch (\Exception $e) {
            Log::error('BridgeClient sendVideo error', [
                'phone' => $phone,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Send audio
     * POST /send/audio
     */
    public function sendAudio(string $phone, $audioContents, string $fileName, bool $isForwarded = false): array
    {
        try {
            $response = Http::timeout(90)
                ->attach('audio', $audioContents, $fileName)
                ->post("{$this->baseUrl}/send/audio", [
                    'phone' => $phone,
                    'is_forwarded' => $isForwarded,
                ]);

            if ($response->failed()) {
                throw new \Exception('Failed to send audio: ' . $response->body());
            }

            return $response->json();
        } catch (\Exception $e) {
            Log::error('BridgeClient sendAudio error', [
                'phone' => $phone,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }


    /**
     * Send file
     * POST /send/file
     */
    public function sendFile(string $phone, $fileContents, string $fileName, string $caption = '', bool $isForwarded = false): array
    {
        try {
            $response = Http::timeout(90)
                ->attach('file', $fileContents, $fileName)
                ->post("{$this->baseUrl}/send/file", [
                    'phone' => $phone,
                    'caption' => $caption,
                    'is_forwarded' => $isForwarded,
                ]);

            if ($response->failed()) {
                throw new \Exception('Failed to send file: ' . $response->body());
            }

            return $response->json();
        } catch (\Exception $e) {
            Log::error('BridgeClient sendFile error', [
                'phone' => $phone,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Send link
     * POST /send/link
     */
    public function sendLink(string $phone, string $link, string $caption = '', bool $isForwarded = false): array
    {
        try {
            $response = Http::timeout(30)->post("{$this->baseUrl}/send/link", [
                'phone' => $phone,
                'link' => $link,
                'caption' => $caption,
                'is_forwarded' => $isForwarded,
            ]);

            if ($response->failed()) {
                throw new \Exception('Failed to send link: ' . $response->body());
            }

            return $response->json();
        } catch (\Exception $e) {
            Log::error('BridgeClient sendLink error', [
                'phone' => $phone,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Send location
     * POST /send/location
     */
    public function sendLocation(string $phone, string $latitude, string $longitude, bool $isForwarded = false): array
    {
        try {
            $response = Http::timeout(30)->post("{$this->baseUrl}/send/location", [
                'phone' => $phone,
                'latitude' => $latitude,
                'longitude' => $longitude,
                'is_forwarded' => $isForwarded,
            ]);

            if ($response->failed()) {
                throw new \Exception('Failed to send location: ' . $response->body());
            }

            return $response->json();
        } catch (\Exception $e) {
            Log::error('BridgeClient sendLocation error', [
                'phone' => $phone,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Send contact
     * POST /send/contact
     */
    public function sendContact(string $phone, string $contactName, string $contactPhone, bool $isForwarded = false): array
    {
        try {
            $response = Http::timeout(30)->post("{$this->baseUrl}/send/contact", [
                'phone' => $phone,
                'contact_name' => $contactName,
                'contact_phone' => $contactPhone,
                'is_forwarded' => $isForwarded,
            ]);

            if ($response->failed()) {
                throw new \Exception('Failed to send contact: ' . $response->body());
            }

            return $response->json();
        } catch (\Exception $e) {
            Log::error('BridgeClient sendContact error', [
                'phone' => $phone,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Send poll
     * POST /send/poll
     */
    public function sendPoll(string $phone, string $question, array $options, int $maxAnswer = 1): array
    {
        try {
            $response = Http::timeout(30)->post("{$this->baseUrl}/send/poll", [
                'phone' => $phone,
                'question' => $question,
                'options' => $options,
                'max_answer' => $maxAnswer,
            ]);

            if ($response->failed()) {
                throw new \Exception('Failed to send poll: ' . $response->body());
            }

            return $response->json();
        } catch (\Exception $e) {
            Log::error('BridgeClient sendPoll error', [
                'phone' => $phone,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Send presence (available/unavailable)
     * POST /send/presence
     */
    public function sendPresence(string $type): array
    {
        try {
            $response = Http::timeout(10)->post("{$this->baseUrl}/send/presence", [
                'type' => $type,
            ]);

            if ($response->failed()) {
                throw new \Exception('Failed to send presence: ' . $response->body());
            }

            return $response->json();
        } catch (\Exception $e) {
            Log::error('BridgeClient sendPresence error', [
                'type' => $type,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Send chat presence (typing indicator)
     * POST /send/chat-presence
     */
    public function sendChatPresence(string $phone, string $action): array
    {
        try {
            $response = Http::timeout(10)->post("{$this->baseUrl}/send/chat-presence", [
                'phone' => $phone,
                'action' => $action, // 'start' or 'stop'
            ]);

            if ($response->failed()) {
                throw new \Exception('Failed to send chat presence: ' . $response->body());
            }

            return $response->json();
        } catch (\Exception $e) {
            Log::error('BridgeClient sendChatPresence error', [
                'phone' => $phone,
                'action' => $action,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * HELPER METHODS
     */

    public function getDeviceId(): string
    {
        return $this->deviceId;
    }

    public function getBaseUrl(): string
    {
        return $this->baseUrl;
    }
}
