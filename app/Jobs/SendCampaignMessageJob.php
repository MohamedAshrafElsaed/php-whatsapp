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

    public int $tries = 1; // Don't retry failed messages to avoid duplicate sends
    public int $timeout = 30;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Campaign $campaign,
        public Recipient $recipient
    ) {}

    /**
     * Execute the job.
     */
    public function handle(BridgeManager $bridgeManager): void
    {
        try {
            // Check if campaign is still running
            $campaign = $this->campaign->fresh();

            if (!$campaign || !$campaign->isRunning()) {
                Log::info('Campaign not running, skipping message', [
                    'campaign_id' => $this->campaign->id,
                    'recipient_id' => $this->recipient->id,
                ]);
                return;
            }

            // Check if recipient is valid
            if (!$this->recipient->is_valid) {
                $this->createFailedMessage('INVALID_RECIPIENT', 'Recipient is invalid');
                return;
            }

            // Check if WhatsApp session is still connected
            $waSession = $campaign->waSession;
            if (!$waSession || !$waSession->isConnected()) {
                Log::warning('WhatsApp session not connected, pausing campaign', [
                    'campaign_id' => $campaign->id,
                ]);

                $campaign->update(['status' => 'paused']);
                $this->createFailedMessage('SESSION_DISCONNECTED', 'WhatsApp device disconnected');
                return;
            }

            // Render message body with variable replacement
            $body = $this->renderMessageBody($campaign->message_template, $this->recipient);

            // Create message record
            $message = Message::create([
                'campaign_id' => $campaign->id,
                'recipient_id' => $this->recipient->id,
                'user_id' => $campaign->user_id,
                'wa_session_id' => $waSession->id,
                'phone_e164' => $this->recipient->phone_e164,
                'body_template' => $campaign->message_template,
                'body_rendered' => $body,
                'status' => 'queued',
            ]);

            // Get bridge client for the campaign's session
            $bridge = $bridgeManager->getClientForSession($waSession);

            // Send message via WhatsApp bridge
            $response = $bridge->sendMessage(
                $this->recipient->phone_e164,
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
                'recipient_id' => $this->recipient->id,
                'phone' => $this->recipient->phone_e164,
                'device' => $waSession->device_label,
            ]);

            // Check if campaign is finished
            $this->checkCampaignCompletion($campaign);

        } catch (\Exception $e) {
            Log::error('Failed to send campaign message', [
                'campaign_id' => $this->campaign->id,
                'recipient_id' => $this->recipient->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $this->createFailedMessage('SEND_ERROR', $e->getMessage());
            $this->campaign->increment('failed_count');
        }
    }

    /**
     * Render message body by replacing variables
     */
    private function renderMessageBody(string $template, Recipient $recipient): string
    {
        $body = $template;

        // Replace basic fields
        $body = str_replace('{{first_name}}', $recipient->first_name ?? '', $body);
        $body = str_replace('{{last_name}}', $recipient->last_name ?? '', $body);
        $body = str_replace('{{email}}', $recipient->email ?? '', $body);
        $body = str_replace('{{phone}}', $recipient->phone_e164 ?? '', $body);

        // Replace extra_json fields
        if ($recipient->extra_json && is_array($recipient->extra_json)) {
            foreach ($recipient->extra_json as $key => $value) {
                $body = str_replace("{{{$key}}}", (string)$value, $body);
            }
        }

        // Clean up any unreplaced variables (replace with empty string)
        $body = preg_replace('/\{\{\w+\}\}/', '', $body);

        return trim($body);
    }

    /**
     * Create a failed message record
     */
    private function createFailedMessage(string $errorCode, string $errorMessage): void
    {
        $body = $this->renderMessageBody(
            $this->campaign->message_template,
            $this->recipient
        );

        Message::create([
            'campaign_id' => $this->campaign->id,
            'recipient_id' => $this->recipient->id,
            'user_id' => $this->campaign->user_id,
            'wa_session_id' => $this->campaign->wa_session_id,
            'phone_e164' => $this->recipient->phone_e164,
            'body_template' => $this->campaign->message_template,
            'body_rendered' => $body,
            'status' => 'failed',
            'error_code' => $errorCode,
            'error_message' => substr($errorMessage, 0, 500),
        ]);

        $this->campaign->increment('failed_count');
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
            'campaign_id' => $this->campaign->id,
            'recipient_id' => $this->recipient->id,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);

        try {
            $this->createFailedMessage('JOB_FAILED', $exception->getMessage());
        } catch (\Exception $e) {
            Log::error('Failed to create failed message record', [
                'error' => $e->getMessage(),
            ]);
        }
    }
}
