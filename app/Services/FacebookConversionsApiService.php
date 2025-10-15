<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class FacebookConversionsApiService
{
    private string $pixelId;
    private string $accessToken;
    private string $apiVersion;
    private ?string $testEventCode;
    private bool $enabled;

    public function __construct()
    {
        $this->pixelId = config('facebook.pixel_id');
        $this->accessToken = config('facebook.access_token');
        $this->apiVersion = config('facebook.api_version');
        $this->testEventCode = config('facebook.test_event_code');
        $this->enabled = config('facebook.enabled', true);
    }

    /**
     * Send event to Facebook Conversions API
     */
    public function sendEvent(string $eventName, array $userData = [], array $customData = [], array $options = []): bool
    {
        if (!$this->enabled) {
            Log::info('Facebook Conversions API is disabled');
            return false;
        }

        try {
            $eventTime = $options['event_time'] ?? time();
            $eventId = $options['event_id'] ?? $this->generateEventId();
            $eventSourceUrl = $options['event_source_url'] ?? request()->fullUrl();
            $actionSource = $options['action_source'] ?? 'website';

            // Build event data
            $eventData = [
                'event_name' => $eventName,
                'event_time' => $eventTime,
                'event_id' => $eventId,
                'action_source' => $actionSource,
                'event_source_url' => $eventSourceUrl,
                'user_data' => $this->prepareUserData($userData),
            ];

            // Add custom data if provided
            if (!empty($customData)) {
                $eventData['custom_data'] = $customData;
            }

            // Add opt_out if provided
            if (isset($options['opt_out'])) {
                $eventData['opt_out'] = $options['opt_out'];
            }

            // Prepare request payload
            $payload = [
                'data' => [$eventData],
            ];

            // Add test event code if in testing mode
            if ($this->testEventCode) {
                $payload['test_event_code'] = $this->testEventCode;
            }

            // Send to Facebook
            $url = "https://graph.facebook.com/{$this->apiVersion}/{$this->pixelId}/events";

            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->post($url, array_merge($payload, [
                'access_token' => $this->accessToken,
            ]));

            if ($response->successful()) {
                Log::info('Facebook Conversions API event sent successfully', [
                    'event_name' => $eventName,
                    'event_id' => $eventId,
                    'response' => $response->json(),
                ]);
                return true;
            } else {
                Log::error('Facebook Conversions API error', [
                    'event_name' => $eventName,
                    'status' => $response->status(),
                    'error' => $response->json(),
                ]);
                return false;
            }
        } catch (\Exception $e) {
            Log::error('Facebook Conversions API exception', [
                'event_name' => $eventName,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return false;
        }
    }

    /**
     * Prepare and hash user data according to Facebook requirements
     */
    private function prepareUserData(array $userData): array
    {
        $prepared = [];

        // Email - must be lowercase and hashed
        if (!empty($userData['email'])) {
            $prepared['em'] = $this->hashData(strtolower(trim($userData['email'])));
        }

        // Phone - must be in E.164 format without + and hashed
        if (!empty($userData['phone'])) {
            $phone = $this->normalizePhone($userData['phone']);
            $prepared['ph'] = $this->hashData($phone);
        }

        // First name - must be lowercase and hashed
        if (!empty($userData['first_name'])) {
            $prepared['fn'] = $this->hashData(strtolower(trim($userData['first_name'])));
        }

        // Last name - must be lowercase and hashed
        if (!empty($userData['last_name'])) {
            $prepared['ln'] = $this->hashData(strtolower(trim($userData['last_name'])));
        }

        // City - must be lowercase, no spaces, and hashed
        if (!empty($userData['city'])) {
            $city = strtolower(str_replace(' ', '', trim($userData['city'])));
            $prepared['ct'] = $this->hashData($city);
        }

        // State - must be lowercase, 2-letter code, and hashed
        if (!empty($userData['state'])) {
            $prepared['st'] = $this->hashData(strtolower(trim($userData['state'])));
        }

        // Zip/Postal code - remove spaces and dashes, lowercase, and hash
        if (!empty($userData['zip'])) {
            $zip = strtolower(str_replace([' ', '-'], '', trim($userData['zip'])));
            $prepared['zp'] = $this->hashData($zip);
        }

        // Country - must be lowercase, 2-letter ISO code, and hashed
        if (!empty($userData['country'])) {
            $prepared['country'] = $this->hashData(strtolower(trim($userData['country'])));
        }

        // Gender - must be single character (m/f) and hashed
        if (!empty($userData['gender'])) {
            $gender = strtolower(substr(trim($userData['gender']), 0, 1));
            if (in_array($gender, ['m', 'f'])) {
                $prepared['ge'] = $this->hashData($gender);
            }
        }

        // Date of birth - must be YYYYMMDD format and hashed
        if (!empty($userData['date_of_birth'])) {
            $prepared['db'] = $this->hashData($userData['date_of_birth']);
        }

        // External ID - hash for privacy
        if (!empty($userData['external_id'])) {
            $prepared['external_id'] = $this->hashData($userData['external_id']);
        }

        // Client IP address - NOT hashed
        if (!empty($userData['client_ip_address'])) {
            $prepared['client_ip_address'] = $userData['client_ip_address'];
        } else {
            $prepared['client_ip_address'] = request()->ip();
        }

        // Client user agent - NOT hashed
        if (!empty($userData['client_user_agent'])) {
            $prepared['client_user_agent'] = $userData['client_user_agent'];
        } else {
            $prepared['client_user_agent'] = request()->userAgent();
        }

        // Facebook browser ID (_fbp cookie) - NOT hashed
        if (!empty($userData['fbp'])) {
            $prepared['fbp'] = $userData['fbp'];
        } elseif (request()->cookie('_fbp')) {
            $prepared['fbp'] = request()->cookie('_fbp');
        }

        // Facebook click ID (_fbc cookie) - NOT hashed
        if (!empty($userData['fbc'])) {
            $prepared['fbc'] = $userData['fbc'];
        } elseif (request()->cookie('_fbc')) {
            $prepared['fbc'] = request()->cookie('_fbc');
        }

        // Subscription ID
        if (!empty($userData['subscription_id'])) {
            $prepared['subscription_id'] = $userData['subscription_id'];
        }

        // Facebook Login ID
        if (!empty($userData['fb_login_id'])) {
            $prepared['fb_login_id'] = $userData['fb_login_id'];
        }

        // Lead ID
        if (!empty($userData['lead_id'])) {
            $prepared['lead_id'] = $userData['lead_id'];
        }

        return $prepared;
    }

    /**
     * Normalize phone number to E.164 format without + sign
     */
    private function normalizePhone(string $phone): string
    {
        // Remove all non-numeric characters except +
        $phone = preg_replace('/[^0-9+]/', '', $phone);

        // Remove + sign if present
        $phone = ltrim($phone, '+');

        return $phone;
    }

    /**
     * Hash data using SHA256
     */
    private function hashData(string $data): string
    {
        return hash('sha256', $data);
    }

    /**
     * Generate unique event ID for deduplication
     */
    private function generateEventId(): string
    {
        return Str::uuid()->toString();
    }

    /**
     * Track CompleteRegistration event
     */
    public function trackRegistration(array $userData, array $customData = []): bool
    {
        return $this->sendEvent('CompleteRegistration', $userData, $customData);
    }

    /**
     * Track Lead event (login, form submission, etc.)
     */
    public function trackLead(array $userData, array $customData = []): bool
    {
        return $this->sendEvent('Lead', $userData, $customData);
    }

    /**
     * Track Subscribe event (newsletter, contact import, etc.)
     */
    public function trackSubscribe(array $userData, array $customData = []): bool
    {
        return $this->sendEvent('Subscribe', $userData, $customData);
    }

    /**
     * Track Contact event (message sent, form filled, etc.)
     */
    public function trackContact(array $userData, array $customData = []): bool
    {
        return $this->sendEvent('Contact', $userData, $customData);
    }

    /**
     * Track StartTrial event (WhatsApp connection, feature activation, etc.)
     */
    public function trackStartTrial(array $userData, array $customData = []): bool
    {
        return $this->sendEvent('StartTrial', $userData, $customData);
    }

    /**
     * Track Custom event
     */
    public function trackCustomEvent(string $eventName, array $userData, array $customData = []): bool
    {
        return $this->sendEvent($eventName, $userData, $customData);
    }

    /**
     * Build user data from authenticated user
     */
    public function buildUserDataFromAuth(): array
    {
        $user = auth()->user();

        if (!$user) {
            return [];
        }

        return [
            'email' => $user->email,
            'phone' => $user->full_phone,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'external_id' => (string) $user->id,
        ];
    }

    /**
     * Build user data from user model
     */
    public function buildUserDataFromUser($user): array
    {
        return [
            'email' => $user->email,
            'phone' => $user->full_phone,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'external_id' => (string) $user->id,
        ];
    }
}
