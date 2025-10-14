<?php

namespace App\Jobs;

use App\Models\Campaign;
use App\Models\Message;
use App\Services\BridgeClient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendCampaignMessage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;
    public int $timeout = 60; // Increased timeout

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $messageId // Store ID instead of model
    )
    {
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            // Load message fresh from database
            $message = Message::find($this->messageId);

            if (!$message) {
                Log::error('Message not found', ['message_id' => $this->messageId]);
                return;
            }

            // Check if campaign is still running
            $campaign = $message->campaign;

            if (!$campaign || !$campaign->isRunning()) {
                Log::info('Campaign not running, skipping message', [
                    'campaign_id' => $campaign?->id,
                    'message_id' => $message->id,
                ]);
                return;
            }

            // Get recipient
            $recipient = $message->recipient;

            if (!$recipient || !$recipient->is_valid) {
                $this->markAsFailed($message, 'INVALID_RECIPIENT', 'Recipient is invalid or not found');
                return;
            }

            // Render message body with variable replacement
            $body = $this->renderMessageBody($campaign->message_template, $recipient);

            // Update message with rendered body
            $message->update(['body_rendered' => $body]);

            // Create BridgeClient instance here (fresh config)
            $bridgeClient = app(BridgeClient::class);

            Log::info('Attempting to send message', [
                'campaign_id' => $campaign->id,
                'message_id' => $message->id,
                'phone' => $recipient->phone_e164,
                'user_id' => $campaign->user_id,
            ]);

            // Send message via WhatsApp bridge
            $response = $bridgeClient->sendMessage(
                $campaign->user_id,
                $recipient->phone_e164,
                $body
            );

            // Mark as sent
            $message->update([
                'status' => 'sent',
                'sent_at' => now(),
            ]);

            Log::info('Message sent successfully', [
                'campaign_id' => $campaign->id,
                'message_id' => $message->id,
                'phone' => $recipient->phone_e164,
                'response' => $response,
            ]);

            // Check if campaign is finished
            $this->checkCampaignCompletion($campaign);

        } catch (\Exception $e) {
            Log::error('Failed to send message', [
                'message_id' => $this->messageId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $message = Message::find($this->messageId);
            if ($message) {
                $this->markAsFailed($message, 'SEND_ERROR', $e->getMessage());
            }
        }
    }

    /**
     * Mark message as failed
     */
    private function markAsFailed(Message $message, string $errorCode, string $errorMessage): void
    {
        $message->update([
            'status' => 'failed',
            'error_code' => $errorCode,
            'error_message' => substr($errorMessage, 0, 500),
        ]);
    }

    /**
     * Render message body by replacing variables
     */
    private function renderMessageBody(string $template, $recipient): string
    {
        $body = $template;

        // Replace basic fields
        $body = str_replace('{{first_name}}', $recipient->first_name ?? '', $body);
        $body = str_replace('{{last_name}}', $recipient->last_name ?? '', $body);
        $body = str_replace('{{email}}', $recipient->email ?? '', $body);
        $body = str_replace('{{phone}}', $recipient->phone_raw ?? '', $body);

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
     * Check if all messages in campaign are processed
     */
    private function checkCampaignCompletion(Campaign $campaign): void
    {
        // Count remaining queued messages
        $queuedCount = $campaign->messages()->where('status', 'queued')->count();

        // If no more queued messages, mark campaign as finished
        if ($queuedCount === 0) {
            $campaign->update([
                'status' => 'finished',
                'finished_at' => now(),
            ]);

            Log::info('Campaign finished', [
                'campaign_id' => $campaign->id,
                'total_sent' => $campaign->messages()->where('status', 'sent')->count(),
                'total_failed' => $campaign->messages()->where('status', 'failed')->count(),
            ]);
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('SendCampaignMessage job failed', [
            'message_id' => $this->messageId,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);

        $message = Message::find($this->messageId);
        if ($message) {
            $this->markAsFailed($message, 'JOB_FAILED', $exception->getMessage());
        }
    }
}
