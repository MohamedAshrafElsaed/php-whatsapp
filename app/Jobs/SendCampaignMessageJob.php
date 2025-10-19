<?php

namespace App\Jobs;

use App\Models\Campaign;
use App\Models\Message;
use App\Models\Recipient;
use App\Services\BridgeManager;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendCampaignMessageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1; // Don't retry to avoid duplicate sends
    public int $timeout = 30;

    // Store only IDs to avoid serialization issues
    public int $campaignId;
    public int $recipientId;

    /**
     * Create a new job instance.
     */
    public function __construct(int $campaignId, int $recipientId)
    {
        $this->campaignId = $campaignId;
        $this->recipientId = $recipientId;
    }

    /**
     * Execute the job.
     */
    public function handle(BridgeManager $bridgeManager): void
    {
        try {
            // Load fresh models from database
            $campaign = Campaign::find($this->campaignId);
            $recipient = Recipient::find($this->recipientId);

            if (!$campaign) {
                Log::warning('Campaign not found', ['campaign_id' => $this->campaignId]);
                return;
            }

            if (!$recipient) {
                Log::warning('Recipient not found', ['recipient_id' => $this->recipientId]);
                return;
            }

            // Check if campaign is still running
            if (!$campaign->isRunning()) {
                Log::info('Campaign not running, skipping message', [
                    'campaign_id' => $campaign->id,
                    'status' => $campaign->status,
                    'recipient_id' => $recipient->id,
                ]);
                return;
            }

            // Check if recipient is valid
            if (!$recipient->is_valid) {
                $this->createFailedMessage($campaign, $recipient, 'INVALID_RECIPIENT', 'Recipient phone number is invalid');
                return;
            }

            // Check if WhatsApp session exists and is connected
            if (!$campaign->wa_session_id) {
                Log::error('Campaign has no WhatsApp session assigned', [
                    'campaign_id' => $campaign->id,
                ]);
                $campaign->update(['status' => 'paused']);
                $this->createFailedMessage($campaign, $recipient, 'NO_SESSION', 'No WhatsApp device assigned to campaign');
                return;
            }

            $waSession = $campaign->waSession;
            if (!$waSession) {
                Log::error('WhatsApp session not found', [
                    'campaign_id' => $campaign->id,
                    'wa_session_id' => $campaign->wa_session_id,
                ]);
                $campaign->update(['status' => 'paused']);
                $this->createFailedMessage($campaign, $recipient, 'SESSION_NOT_FOUND', 'WhatsApp device not found');
                return;
            }

            if (!$waSession->isConnected()) {
                Log::warning('WhatsApp session not connected, pausing campaign', [
                    'campaign_id' => $campaign->id,
                    'session_status' => $waSession->status,
                ]);
                $campaign->update(['status' => 'paused']);
                $this->createFailedMessage($campaign, $recipient, 'SESSION_DISCONNECTED', 'WhatsApp device disconnected');
                return;
            }

            // Render message body with variable replacement
            $body = $this->renderMessageBody($campaign->message_template, $recipient);

            // Create message record
            $message = Message::create([
                'campaign_id' => $campaign->id,
                'recipient_id' => $recipient->id,
                'user_id' => $campaign->user_id,
                'wa_session_id' => $waSession->id,
                'phone_e164' => $recipient->phone_e164,
                'body_template' => $campaign->message_template,
                'body_rendered' => $body,
                'status' => 'queued',
            ]);

            // Get bridge client for the campaign's session
            $bridge = $bridgeManager->getClientForSession($waSession);

            Log::info('Sending campaign message', [
                'campaign_id' => $campaign->id,
                'message_id' => $message->id,
                'recipient_id' => $recipient->id,
                'phone' => $recipient->phone_e164,
                'device' => $waSession->device_label,
                'bridge_url' => $waSession->getBridgeUrl(),
            ]);

            // Send message via WhatsApp bridge
            $response = $bridge->sendMessage(
                $recipient->phone_e164,
                $body
            );

            // Mark as sent
            $message->update([
                'status' => 'sent',
                'sent_at' => now(),
            ]);

            // Update campaign counters
            $campaign->increment('sent_count');

            Log::info('Campaign message sent successfully', [
                'campaign_id' => $campaign->id,
                'message_id' => $message->id,
                'recipient_id' => $recipient->id,
                'phone' => $recipient->phone_e164,
            ]);

            // Check if campaign is finished
            $this->checkCampaignCompletion($campaign);

        } catch (\Exception $e) {
            Log::error('Failed to send campaign message', [
                'campaign_id' => $this->campaignId,
                'recipient_id' => $this->recipientId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Try to create failed message record
            try {
                $campaign = Campaign::find($this->campaignId);
                $recipient = Recipient::find($this->recipientId);

                if ($campaign && $recipient) {
                    $this->createFailedMessage($campaign, $recipient, 'SEND_ERROR', $e->getMessage());
                }
            } catch (\Exception $innerException) {
                Log::error('Failed to create failed message record', [
                    'error' => $innerException->getMessage(),
                ]);
            }

            throw $e; // Re-throw to mark job as failed
        }
    }

    /**
     * Render message body by replacing variables
     */
    private function renderMessageBody(string $template, Recipient $recipient): string
    {
        $body = $template;

        // Replace basic fields
        $replacements = [
            '{{first_name}}' => $recipient->first_name ?? '',
            '{{last_name}}' => $recipient->last_name ?? '',
            '{{email}}' => $recipient->email ?? '',
            '{{phone}}' => $recipient->phone_e164 ?? '',
            '{{phone_raw}}' => $recipient->phone_raw ?? '',
        ];

        foreach ($replacements as $placeholder => $value) {
            $body = str_replace($placeholder, $value, $body);
        }

        // Replace extra_json fields
        if ($recipient->extra_json && is_array($recipient->extra_json)) {
            foreach ($recipient->extra_json as $key => $value) {
                $placeholder = '{{' . $key . '}}';
                $body = str_replace($placeholder, (string)$value, $body);
            }
        }

        // Clean up any unreplaced variables
        $body = preg_replace('/\{\{[a-zA-Z0-9_]+\}\}/', '', $body);

        return trim($body);
    }

    /**
     * Create a failed message record
     */
    private function createFailedMessage(Campaign $campaign, Recipient $recipient, string $errorCode, string $errorMessage): void
    {
        try {
            $body = $this->renderMessageBody($campaign->message_template, $recipient);

            Message::create([
                'campaign_id' => $campaign->id,
                'recipient_id' => $recipient->id,
                'user_id' => $campaign->user_id,
                'wa_session_id' => $campaign->wa_session_id,
                'phone_e164' => $recipient->phone_e164,
                'body_template' => $campaign->message_template,
                'body_rendered' => $body,
                'status' => 'failed',
                'error_code' => $errorCode,
                'error_message' => substr($errorMessage, 0, 500),
            ]);

            $campaign->increment('failed_count');
        } catch (\Exception $e) {
            Log::error('Failed to create failed message record', [
                'campaign_id' => $campaign->id,
                'recipient_id' => $recipient->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Check if all messages in campaign are processed
     */
    private function checkCampaignCompletion(Campaign $campaign): void
    {
        $campaign = $campaign->fresh();

        // Calculate total processed (sent + failed)
        $totalProcessed = $campaign->sent_count + $campaign->failed_count;

        // If all recipients processed, mark campaign as finished
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
            'campaign_id' => $this->campaignId,
            'recipient_id' => $this->recipientId,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);

        try {
            $campaign = Campaign::find($this->campaignId);
            $recipient = Recipient::find($this->recipientId);

            if ($campaign && $recipient) {
                $this->createFailedMessage($campaign, $recipient, 'JOB_FAILED', $exception->getMessage());
            }
        } catch (\Exception $e) {
            Log::error('Failed to handle job failure', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Get the tags that should be assigned to the job.
     */
    public function tags(): array
    {
        return [
            'campaign:' . $this->campaignId,
            'recipient:' . $this->recipientId,
        ];
    }
}
