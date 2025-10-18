<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\WaSession;
use App\Services\BridgeClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    /**
     * Handle WhatsApp webhook events
     */
    public function handle(Request $request)
    {
        Log::info('CallBack', $request->all());

        // Verify webhook signature
        if (!$this->verifySignature($request)) {
            Log::warning('Invalid webhook signature', ['ip' => $request->ip()]);
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $payload = $request->all();
        Log::info('Webhook received', $payload);

        // Handle different event types
        if (isset($payload['message'])) {
            $this->handleIncomingMessage($payload);
        } elseif (isset($payload['event'])) {
            $this->handleEvent($payload);
        } elseif (isset($payload['action'])) {
            $this->handleAction($payload);
        }

        return response()->json(['success' => true]);
    }

    /**
     * Verify HMAC signature
     */
    private function verifySignature(Request $request): bool
    {
        $signature = $request->header('X-Hub-Signature-256');
        if (!$signature) {
            return false;
        }

        $payload = $request->getContent();
        $secret = config('services.bridge.webhook_secret');

        $expectedSignature = 'sha256=' . hash_hmac('sha256', $payload, $secret);

        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Handle incoming messages with per-user settings
     */
    private function handleIncomingMessage(array $payload)
    {
        // Since go-whatsapp-web-multidevice is single instance,
        // we need to find which user owns this WhatsApp connection
        $session = WaSession::where('status', 'connected')->first();

        if (!$session) {
            Log::info('No connected session found for incoming message');
            return;
        }

        $user = User::find($session->user_id);
        if (!$user) {
            return;
        }

        // Get sender phone
        $senderPhone = $payload['sender_id'] ?? null;
        if (!$senderPhone) {
            return;
        }

        // Auto-reply (per-user setting)
        if ($user->wa_auto_reply_enabled && $user->wa_auto_reply_message) {
            $this->sendAutoReply($user, $senderPhone, $payload);
        }

        Log::info('Incoming message processed', [
            'user_id' => $user->id,
            'from' => $senderPhone,
            'message' => $payload['message']['text'] ?? 'media',
        ]);
    }

    /**
     * Send auto-reply based on user settings
     */
    private function sendAutoReply(User $user, string $senderPhone, array $payload)
    {
        try {
            $bridge = app(BridgeClient::class);

            // Don't auto-reply to media or empty messages
            if (empty($payload['message']['text'])) {
                return;
            }

            // Don't auto-reply to our own messages
            if ($payload['from_me'] ?? false) {
                return;
            }

            // Send auto-reply
            $bridge->sendMessage(
                $senderPhone,
                $user->wa_auto_reply_message
            );

            Log::info('Auto-reply sent', [
                'user_id' => $user->id,
                'to' => $senderPhone,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send auto-reply', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle event-based webhooks
     */
    private function handleEvent(array $payload)
    {
        $event = $payload['event'];

        switch ($event) {
            case 'message.ack':
                $this->handleMessageAck($payload['payload']);
                break;

            case 'group.participants':
                $this->handleGroupParticipants($payload['payload']);
                break;

            default:
                Log::info('Unknown event type', ['event' => $event]);
        }
    }

    /**
     * Handle message acknowledgment (delivered/read)
     */
    private function handleMessageAck(array $data)
    {
        // Update message status in database
        foreach ($data['ids'] as $messageId) {
            DB::table('messages')
                ->where('whatsapp_message_id', $messageId)
                ->update([
                    'status' => $data['receipt_type'], // delivered or read
                    'updated_at' => now(),
                ]);
        }

        Log::info('Message acknowledged', [
            'type' => $data['receipt_type'],
            'count' => count($data['ids']),
        ]);
    }

    /**
     * Handle group participant changes
     */
    private function handleGroupParticipants(array $data)
    {
        Log::info('Group participants changed', [
            'group' => $data['chat_id'],
            'type' => $data['type'],
            'users' => count($data['jids']),
        ]);
    }

    /**
     * Handle actions (revoke, edit, etc.)
     */
    private function handleAction(array $payload)
    {
        $action = $payload['action'];

        switch ($action) {
            case 'message_revoked':
                Log::info('Message revoked', [
                    'message_id' => $payload['revoked_message_id'],
                ]);
                break;

            case 'message_edited':
                Log::info('Message edited', [
                    'message_id' => $payload['message']['id'],
                    'new_text' => $payload['edited_text'],
                ]);
                break;
        }
    }
}
