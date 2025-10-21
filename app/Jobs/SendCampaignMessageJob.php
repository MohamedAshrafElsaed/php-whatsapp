<?php

namespace App\Jobs;

use App\Models\Campaign;
use App\Models\Message;
use App\Services\BridgeManager;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class SendCampaignMessageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1; // Don't retry to avoid duplicate sends
    public int $timeout = 120; // Increased for media uploads

    public int $messageId;

    /**
     * Create a new job instance.
     */
    public function __construct(int $messageId)
    {
        $this->messageId = $messageId;
    }

    /**
     * Execute the job.
     */
    public function handle(BridgeManager $bridgeManager): void
    {
        $message = Message::find($this->messageId);

        if (!$message) {
            Log::error('Message not found', ['message_id' => $this->messageId]);
            return;
        }

        $campaign = $message->campaign;

        if (!$campaign) {
            Log::error('Campaign not found for message', ['message_id' => $this->messageId]);
            $message->update([
                'status' => 'failed',
                'error_code' => 'CAMPAIGN_NOT_FOUND',
                'error_message' => 'Campaign not found',
            ]);
            return;
        }

        // Check campaign status - allow running campaigns only
        if ($campaign->status !== 'running') {
            Log::info('Campaign not running, skipping message', [
                'message_id' => $this->messageId,
                'campaign_id' => $campaign->id,
                'campaign_status' => $campaign->status,
            ]);
            return;
        }

        $waSession = $campaign->waSession;

        if (!$waSession || !$waSession->isConnected()) {
            Log::error('WhatsApp session not connected', [
                'message_id' => $this->messageId,
                'campaign_id' => $campaign->id,
                'wa_session_id' => $campaign->wa_session_id,
            ]);
            $message->update([
                'status' => 'failed',
                'error_code' => 'SESSION_DISCONNECTED',
                'error_message' => 'WhatsApp session is not connected',
            ]);
            $campaign->increment('failed_count');
            $this->checkCampaignCompletion($campaign);
            return;
        }

        try {
            $bridge = $bridgeManager->getClientForSession($waSession);

            Log::info('Sending campaign message', [
                'message_id' => $this->messageId,
                'campaign_id' => $campaign->id,
                'message_type' => $campaign->message_type,
                'phone' => $message->phone_e164,
            ]);

            // Send based on message type
            switch ($campaign->message_type) {
                case 'text':
                    $this->sendTextMessage($bridge, $message, $campaign);
                    break;

                case 'image':
                case 'video':
                case 'audio':
                case 'file':
                    $this->sendMediaMessage($bridge, $message, $campaign);
                    break;

                case 'link':
                    $this->sendLinkMessage($bridge, $message, $campaign);
                    break;

                case 'location':
                    $this->sendLocationMessage($bridge, $message, $campaign);
                    break;

                case 'contact':
                    $this->sendContactMessage($bridge, $message, $campaign);
                    break;

                case 'poll':
                    $this->sendPollMessage($bridge, $message, $campaign);
                    break;

                default:
                    throw new \Exception('Unsupported message type: ' . $campaign->message_type);
            }

            // Mark as sent
            $message->update([
                'status' => 'sent',
                'sent_at' => now(),
                'error_code' => null,
                'error_message' => null,
            ]);

            $campaign->increment('sent_count');

            Log::info('Campaign message sent successfully', [
                'message_id' => $this->messageId,
                'campaign_id' => $campaign->id,
                'message_type' => $campaign->message_type,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to send campaign message', [
                'message_id' => $this->messageId,
                'campaign_id' => $campaign->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $message->update([
                'status' => 'failed',
                'error_code' => 'SEND_ERROR',
                'error_message' => substr($e->getMessage(), 0, 500),
            ]);

            $campaign->increment('failed_count');
        }

        // Check if campaign is complete
        $this->checkCampaignCompletion($campaign);
    }

    /**
     * Send text message
     */
    private function sendTextMessage($bridge, Message $message, Campaign $campaign): void
    {
        if (!$message->body_rendered) {
            throw new \Exception('Message body is empty');
        }

        $bridge->sendMessage(
            $message->phone_e164,
            $message->body_rendered
        );
    }

    /**
     * Send media message (image, video, audio, file)
     */
    private function sendMediaMessage($bridge, Message $message, Campaign $campaign): void
    {
        if (!$campaign->media_path) {
            throw new \Exception('Media path is not set');
        }

        if (!Storage::disk('public')->exists($campaign->media_path)) {
            throw new \Exception('Media file not found: ' . $campaign->media_path);
        }

        $fileContents = Storage::disk('public')->get($campaign->media_path);

        if (!$fileContents) {
            throw new \Exception('Failed to read media file');
        }

        $fileName = $campaign->media_filename ?? 'file';

        // Use per-recipient rendered caption if available (stored in body_rendered)
        // Otherwise fall back to campaign caption
        $caption = '';
        if (in_array($campaign->message_type, ['image', 'video', 'file'])) {
            $caption = $message->body_rendered ?? $campaign->caption ?? '';
        }

        switch ($campaign->message_type) {
            case 'image':
                $bridge->sendImage($message->phone_e164, $fileContents, $fileName, $caption);
                break;
            case 'video':
                $bridge->sendVideo($message->phone_e164, $fileContents, $fileName, $caption);
                break;
            case 'audio':
                $bridge->sendAudio($message->phone_e164, $fileContents, $fileName);
                break;
            case 'file':
                $bridge->sendFile($message->phone_e164, $fileContents, $fileName, $caption);
                break;
        }
    }

    /**
     * Send link message
     */
    private function sendLinkMessage($bridge, Message $message, Campaign $campaign): void
    {
        if (!$campaign->link_url) {
            throw new \Exception('Link URL is required');
        }

        // Use per-recipient rendered caption if available
        $caption = $message->body_rendered ?? $campaign->caption ?? '';

        $bridge->sendLink(
            $message->phone_e164,
            $campaign->link_url,
            $caption
        );
    }

    /**
     * Send location message
     */
    private function sendLocationMessage($bridge, Message $message, Campaign $campaign): void
    {
        if (!$campaign->latitude || !$campaign->longitude) {
            throw new \Exception('Latitude and longitude are required');
        }

        $bridge->sendLocation(
            $message->phone_e164,
            $campaign->latitude,
            $campaign->longitude
        );
    }

    /**
     * Send contact message
     */
    private function sendContactMessage($bridge, Message $message, Campaign $campaign): void
    {
        if (!$campaign->contact_name || !$campaign->contact_phone) {
            throw new \Exception('Contact name and phone are required');
        }

        $bridge->sendContact(
            $message->phone_e164,
            $campaign->contact_name,
            $campaign->contact_phone
        );
    }

    /**
     * Send poll message
     */
    private function sendPollMessage($bridge, Message $message, Campaign $campaign): void
    {
        if (!$campaign->poll_question || !$campaign->poll_options) {
            throw new \Exception('Poll question and options are required');
        }

        if (!is_array($campaign->poll_options) || count($campaign->poll_options) < 2) {
            throw new \Exception('Poll must have at least 2 options');
        }

        $bridge->sendPoll(
            $message->phone_e164,
            $campaign->poll_question,
            $campaign->poll_options,
            $campaign->poll_max_answer ?? 1
        );
    }

    /**
     * Check if campaign is complete
     */
    private function checkCampaignCompletion(Campaign $campaign): void
    {
        $campaign = $campaign->fresh();

        if (!$campaign) {
            Log::error('Campaign not found when checking completion');
            return;
        }

        $totalProcessed = $campaign->sent_count + $campaign->failed_count;

        if ($totalProcessed >= $campaign->total_recipients) {
            $campaign->update([
                'status' => 'finished',
                'finished_at' => now(),
            ]);

            Log::info('Campaign finished', [
                'campaign_id' => $campaign->id,
                'total_recipients' => $campaign->total_recipients,
                'sent' => $campaign->sent_count,
                'failed' => $campaign->failed_count,
            ]);
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('SendCampaignMessage job failed completely', [
            'message_id' => $this->messageId,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);

        try {
            $message = Message::find($this->messageId);

            if (!$message) {
                Log::error('Cannot update failed message - message not found', [
                    'message_id' => $this->messageId,
                ]);
                return;
            }

            $message->update([
                'status' => 'failed',
                'error_code' => 'JOB_FAILED',
                'error_message' => substr($exception->getMessage(), 0, 500),
            ]);

            $campaign = $message->campaign;

            if ($campaign) {
                $campaign->increment('failed_count');
                $this->checkCampaignCompletion($campaign);
            } else {
                Log::warning('Campaign not found when handling job failure', [
                    'message_id' => $this->messageId,
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to handle job failure', [
                'message_id' => $this->messageId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Get the tags that should be assigned to the job.
     */
    public function tags(): array
    {
        return [
            'campaign-message',
            'message:' . $this->messageId,
        ];
    }
}
