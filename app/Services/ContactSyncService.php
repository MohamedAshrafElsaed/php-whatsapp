<?php

namespace App\Services;

use App\Models\Contact;
use App\Models\WaSession;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ContactSyncService
{
    private BridgeClient $bridgeClient;

    public function __construct(BridgeClient $bridgeClient)
    {
        $this->bridgeClient = $bridgeClient;
    }

    /**
     * Sync contacts from WhatsApp to database
     */
    public function syncContacts(WaSession $waSession): array
    {
        try {
            Log::info('Starting contact sync', [
                'wa_session_id' => $waSession->id,
                'user_id' => $waSession->user_id,
            ]);

            // Get contacts from WhatsApp bridge
            $response = $this->bridgeClient->getMyContacts();

            if (!isset($response['code']) || $response['code'] !== 'SUCCESS') {
                throw new \Exception('Failed to fetch contacts from WhatsApp');
            }

            $contactsData = $response['results']['data'] ?? [];

            if (empty($contactsData)) {
                Log::warning('No contacts found', [
                    'wa_session_id' => $waSession->id,
                ]);

                return [
                    'success' => true,
                    'synced' => 0,
                    'failed' => 0,
                    'total' => 0,
                ];
            }

            $synced = 0;
            $failed = 0;
            $total = count($contactsData);

            DB::beginTransaction();

            try {
                foreach ($contactsData as $contactData) {
                    try {
                        $this->syncSingleContact($waSession, $contactData);
                        $synced++;
                    } catch (\Exception $e) {
                        $failed++;
                        Log::warning('Failed to sync contact', [
                            'jid' => $contactData['jid'] ?? 'unknown',
                            'error' => $e->getMessage(),
                        ]);
                    }
                }

                DB::commit();

                Log::info('Contact sync completed', [
                    'wa_session_id' => $waSession->id,
                    'total' => $total,
                    'synced' => $synced,
                    'failed' => $failed,
                ]);

                return [
                    'success' => true,
                    'synced' => $synced,
                    'failed' => $failed,
                    'total' => $total,
                ];

            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }

        } catch (\Exception $e) {
            Log::error('Contact sync failed', [
                'wa_session_id' => $waSession->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Sync a single contact
     */
    private function syncSingleContact(WaSession $waSession, array $contactData): void
    {
        $jid = $contactData['jid'] ?? null;
        $fullName = $contactData['name'] ?? null;

        if (!$jid) {
            throw new \Exception('Contact JID is missing');
        }

        // Parse JID to extract phone information
        $phoneData = Contact::parseJid($jid);

        // Parse name into first and last name
        $nameData = Contact::parseName($fullName);

        // Create or update contact
        Contact::updateOrCreate(
            [
                'user_id' => $waSession->user_id,
                'wa_session_id' => $waSession->id,
                'jid' => $jid,
            ],
            [
                'phone_raw' => $phoneData['phone_raw'],
                'phone_e164' => $phoneData['phone_e164'],
                'first_name' => $nameData['first_name'],
                'last_name' => $nameData['last_name'],
                'full_name' => $fullName,
                'is_valid' => $phoneData['is_valid'],
            ]
        );
    }

    /**
     * Clear all contacts for a session
     */
    public function clearContacts(WaSession $waSession): void
    {
        Contact::where('user_id', $waSession->user_id)
            ->where('wa_session_id', $waSession->id)
            ->delete();

        Log::info('Contacts cleared', [
            'wa_session_id' => $waSession->id,
            'user_id' => $waSession->user_id,
        ]);
    }
}
