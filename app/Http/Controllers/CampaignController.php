<?php

namespace App\Http\Controllers;

use App\Jobs\SendCampaignMessage;
use App\Models\AuditLog;
use App\Models\Campaign;
use App\Models\Import;
use App\Models\Message;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class CampaignController extends Controller
{
    /**
     * Display list of campaigns
     */
    public function index(Request $request): Response
    {
        $campaigns = $request->user()
            ->campaigns()
            ->with('import')
            ->withCount([
                'messages as total_messages',
                'messages as sent_count' => fn($q) => $q->where('status', 'sent'),
                'messages as failed_count' => fn($q) => $q->where('status', 'failed'),
                'messages as queued_count' => fn($q) => $q->where('status', 'queued'),
            ])
            ->latest()
            ->paginate(20);

        return Inertia::render('campaigns/Index', [
            'campaigns' => $campaigns,
        ]);
    }

    /**
     * Show campaign creation form
     */
    public function create(Request $request): Response
    {
        // Get user's imports that are ready for use
        $imports = $request->user()
            ->imports()
            ->where('status', 'ready')
            ->where('valid_rows', '>', 0)
            ->latest()
            ->get()
            ->map(fn($import) => [
                'id' => $import->id,
                'filename' => $import->filename,
                'valid_rows' => $import->valid_rows,
                'created_at' => $import->created_at->format('M d, Y'),
            ]);

        // Get preview recipient and available variables if import_id provided
        $importId = $request->query('import_id');
        $previewRecipient = null;
        $availableVariables = ['first_name', 'last_name', 'email'];

        if ($importId) {
            $import = Import::where('user_id', $request->user()->id)
                ->where('id', $importId)
                ->first();

            if ($import) {
                $previewRecipient = $import->recipients()
                    ->where('is_valid', true)
                    ->first();

                if ($previewRecipient && $previewRecipient->extra_json) {
                    $availableVariables = array_merge(
                        $availableVariables,
                        array_keys($previewRecipient->extra_json)
                    );
                }
            }
        }

        return Inertia::render('campaigns/Create', [
            'imports' => $imports,
            'previewRecipient' => $previewRecipient,
            'availableVariables' => $availableVariables,
        ]);
    }

    /**
     * Store new campaign
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'import_id' => 'required|exists:imports,id',
            'message_template' => 'required|string|min:10',
            'messages_per_minute' => 'nullable|integer|min:5|max:30',
            'delay_seconds' => 'nullable|integer|min:2|max:10',
            'start_immediately' => 'boolean',
        ]);

        $import = Import::where('user_id', $request->user()->id)
            ->where('id', $request->import_id)
            ->where('status', 'ready')
            ->firstOrFail();

        // Extract variables from message template
        preg_match_all('/\{\{(\w+)\}\}/', $request->message_template, $matches);
        $variables = array_unique($matches[1]);

        // Conservative rate limiting to avoid WhatsApp blocks
        $throttling = [
            'messages_per_minute' => $request->input('messages_per_minute', 15),
            'delay_seconds' => $request->input('delay_seconds', 4),
        ];

        $campaign = Campaign::create([
            'user_id' => $request->user()->id,
            'import_id' => $import->id,
            'name' => $request->name,
            'message_template' => $request->message_template,
            'variables_json' => $variables,
            'status' => 'draft',
            'throttling_cfg_json' => $throttling,
        ]);

        AuditLog::log('created', 'Campaign', $campaign->id, [
            'name' => $campaign->name,
            'import_id' => $import->id,
        ]);

        // Start immediately if requested
        if ($request->boolean('start_immediately')) {
            return $this->startCampaign($campaign);
        }

        return redirect()->route('campaigns.show', $campaign)
            ->with('success', 'Campaign created successfully.');
    }

    /**
     * Show campaign details
     */
    public function show(Request $request, Campaign $campaign): Response
    {
        $this->authorize('view', $campaign);

        $campaign->load('import');

        // Get statistics
        $stats = [
            'total' => $campaign->messages()->count(),
            'sent' => $campaign->messages()->where('status', 'sent')->count(),
            'failed' => $campaign->messages()->where('status', 'failed')->count(),
            'queued' => $campaign->messages()->where('status', 'queued')->count(),
        ];

        // Get first 50 messages with recipient info
        $messages = $campaign->messages()
            ->with('recipient')
            ->latest()
            ->limit(50)
            ->get()
            ->map(fn($message) => [
                'id' => $message->id,
                'recipient_name' => $message->recipient->full_name ?? 'N/A',
                'phone' => $message->phone_e164,
                'status' => $message->status,
                'sent_at' => $message->sent_at?->format('M d, Y H:i:s'),
                'error_message' => $message->error_message,
            ]);

        return Inertia::render('campaigns/Show', [
            'campaign' => [
                'id' => $campaign->id,
                'name' => $campaign->name,
                'status' => $campaign->status,
                'message_template' => $campaign->message_template,
                'throttling' => $campaign->throttling_cfg_json,
                'started_at' => $campaign->started_at?->format('M d, Y H:i:s'),
                'finished_at' => $campaign->finished_at?->format('M d, Y H:i:s'),
                'created_at' => $campaign->created_at->format('M d, Y H:i:s'),
                'import' => [
                    'id' => $campaign->import->id,
                    'filename' => $campaign->import->filename,
                    'valid_rows' => $campaign->import->valid_rows,
                ],
            ],
            'stats' => $stats,
            'messages' => $messages,
        ]);
    }

    /**
     * Start campaign sending
     */
    public function start(Request $request, Campaign $campaign): RedirectResponse
    {
        $this->authorize('update', $campaign);

        if (!$campaign->canStart()) {
            return back()->with('error', 'Campaign cannot be started from current status.');
        }

        return $this->startCampaign($campaign);
    }

    /**
     * Pause campaign
     */
    public function pause(Request $request, Campaign $campaign): RedirectResponse
    {
        $this->authorize('update', $campaign);

        if (!$campaign->isRunning()) {
            return back()->with('error', 'Only running campaigns can be paused.');
        }

        $campaign->update(['status' => 'paused']);

        AuditLog::log('paused', 'Campaign', $campaign->id);

        return back()->with('success', 'Campaign paused successfully.');
    }

    /**
     * Cancel campaign
     */
    public function cancel(Request $request, Campaign $campaign): RedirectResponse
    {
        $this->authorize('update', $campaign);

        if (!in_array($campaign->status, ['draft', 'running', 'paused'])) {
            return back()->with('error', 'Campaign cannot be canceled from current status.');
        }

        $campaign->update([
            'status' => 'canceled',
            'finished_at' => now(),
        ]);

        AuditLog::log('canceled', 'Campaign', $campaign->id);

        return back()->with('success', 'Campaign canceled successfully.');
    }

    /**
     * Delete campaign
     */
    public function destroy(Request $request, Campaign $campaign): RedirectResponse
    {
        $this->authorize('delete', $campaign);

        if ($campaign->isRunning()) {
            return back()->with('error', 'Cannot delete a running campaign. Please pause or cancel it first.');
        }

        AuditLog::log('deleted', 'Campaign', $campaign->id);

        $campaign->delete();

        return redirect()->route('campaigns.index')
            ->with('success', 'Campaign deleted successfully.');
    }

    /**
     * Internal method to start campaign
     */
    private function startCampaign(Campaign $campaign): RedirectResponse
    {
        try {
            DB::beginTransaction();

            // Update campaign status
            $campaign->update([
                'status' => 'running',
                'started_at' => now(),
            ]);

            // Get all valid recipients from the import
            $recipients = $campaign->import
                ->recipients()
                ->where('is_valid', true)
                ->get();

            if ($recipients->isEmpty()) {
                throw new \Exception('No valid recipients found in the selected import.');
            }

            // Create message records for all recipients
            $throttling = $campaign->throttling_cfg_json;
            $delaySeconds = $throttling['delay_seconds'] ?? 4;
            $messagesCreated = 0;

            foreach ($recipients as $index => $recipient) {
                $message = Message::create([
                    'campaign_id' => $campaign->id,
                    'recipient_id' => $recipient->id,
                    'user_id' => $campaign->user_id,
                    'phone_e164' => $recipient->phone_e164,
                    'body_rendered' => '', // Will be rendered in job
                    'status' => 'queued',
                ]);

                // Dispatch job with progressive delay to respect WhatsApp rate limits
                // This prevents all jobs from hitting the API at once
                $jobDelay = $index * $delaySeconds;
                SendCampaignMessage::dispatch($message->id)->delay(now()->addSeconds($jobDelay));

                $messagesCreated++;
            }

            DB::commit();

            AuditLog::log('started', 'Campaign', $campaign->id, [
                'messages_created' => $messagesCreated,
            ]);

            return redirect()->route('campaigns.show', $campaign)
                ->with('success', "Campaign started! {$messagesCreated} messages queued for sending.");

        } catch (\Exception $e) {
            DB::rollBack();

            $campaign->update(['status' => 'draft']);

            return back()->with('error', 'Failed to start campaign: ' . $e->getMessage());
        }
    }
}
