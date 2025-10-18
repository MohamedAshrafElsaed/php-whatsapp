<?php

namespace App\Jobs;

use App\Models\WaSession;
use App\Services\BridgeClient;
use App\Services\ContactSyncService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncWhatsAppContacts implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public WaSession $waSession
    ) {}

    public function handle(): void
    {
        try {
            $bridgeClient = new BridgeClient(
                $this->waSession->bridge_url,
                $this->waSession->device_id
            );

            $syncService = new ContactSyncService($bridgeClient);
            $result = $syncService->syncContacts($this->waSession);

            if ($result['success']) {
                Log::info('WhatsApp contacts synced successfully', [
                    'wa_session_id' => $this->waSession->id,
                    'result' => $result,
                ]);
            } else {
                Log::error('Failed to sync WhatsApp contacts', [
                    'wa_session_id' => $this->waSession->id,
                    'error' => $result['error'] ?? 'Unknown error',
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Contact sync job failed', [
                'wa_session_id' => $this->waSession->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
