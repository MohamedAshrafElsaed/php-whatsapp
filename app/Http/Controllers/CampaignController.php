<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Models\Import;
use App\Models\Recipient;
use App\Models\WaSession;
use App\Jobs\SendCampaignMessageJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class CampaignController extends Controller
{
    /**
     * Display campaigns list
     */
    public function index(Request $request)
    {
        $campaigns = Campaign::with(['waSession', 'import'])
            ->where('user_id', $request->user()->id)
            ->orderBy('created_at', 'desc')
            ->paginate(20)
            ->through(fn($campaign) => [
                'id' => $campaign->id,
                'name' => $campaign->name,
                'status' => $campaign->status,
                'created_at' => $campaign->created_at->format('M d, Y'),
                'import' => [
                    'filename' => $campaign->import?->filename ?? 'N/A',
                ],
                'total_messages' => $campaign->total_recipients ?? 0,
                'sent_count' => $campaign->sent_count ?? 0,
                'failed_count' => $campaign->failed_count ?? 0,
                'queued_count' => ($campaign->total_recipients ?? 0) - ($campaign->sent_count ?? 0) - ($campaign->failed_count ?? 0),
                'device' => [
                    'label' => $campaign->waSession?->device_label ?? 'N/A',
                    'status' => $campaign->waSession?->status ?? 'disconnected',
                ],
            ]);

        return Inertia::render('campaigns/Index', [
            'campaigns' => $campaigns,
        ]);
    }

    /**
     * Show create campaign form
     */
    public function create(Request $request)
    {
        // Get user's connected WhatsApp devices
        $connectedDevices = WaSession::where('user_id', $request->user()->id)
            ->where('status', 'connected')
            ->orderBy('is_primary', 'desc')
            ->get()
            ->map(fn($session) => [
                'id' => $session->id,
                'device_id' => $session->device_id,
                'device_label' => $session->device_label,
                'phone' => $session->getPhoneNumber(),
                'is_primary' => $session->is_primary,
            ]);

        if ($connectedDevices->isEmpty()) {
            return redirect()->route('wa.connect')
                ->with('error', 'Please connect a WhatsApp device first before creating a campaign.');
        }

        // Get user's imports with valid recipients
        $imports = Import::where('user_id', $request->user()->id)
            ->where('valid_rows', '>', 0)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn($import) => [
                'id' => $import->id,
                'filename' => $import->filename,
                'valid_rows' => $import->valid_rows,
                'created_at' => $import->created_at->format('M d, Y'),
            ]);

        if ($imports->isEmpty()) {
            return redirect()->route('imports.index')
                ->with('error', 'Please import contacts first before creating a campaign.');
        }

        // Get preview recipient (first valid recipient from user)
        $previewRecipient = Recipient::where('user_id', $request->user()->id)
            ->where('is_valid', true)
            ->first();

        // Get available variables from first recipient's extra fields
        $availableVariables = ['first_name', 'last_name', 'email', 'phone'];
        if ($previewRecipient && $previewRecipient->extra_json) {
            $availableVariables = array_merge(
                $availableVariables,
                array_keys($previewRecipient->extra_json)
            );
        }

        return Inertia::render('campaigns/Create', [
            'connectedDevices' => $connectedDevices,
            'imports' => $imports,
            'previewRecipient' => $previewRecipient ? [
                'first_name' => $previewRecipient->first_name,
                'last_name' => $previewRecipient->last_name,
                'email' => $previewRecipient->email,
                'phone_e164' => $previewRecipient->phone_e164,
                'extra_json' => $previewRecipient->extra_json ?? [],
            ] : null,
            'availableVariables' => array_unique($availableVariables),
        ]);
    }

    /**
     * Store new campaign
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'wa_session_id' => 'required|exists:wa_sessions,id',
            'import_id' => 'required|exists:imports,id',
            'message_template' => 'required|string|max:4096',
            'messages_per_minute' => 'nullable|integer|min:5|max:30',
            'delay_seconds' => 'nullable|integer|min:2|max:10',
            'start_immediately' => 'nullable|boolean',
        ]);

        // Verify the WhatsApp session belongs to user and is connected
        $waSession = WaSession::where('id', $validated['wa_session_id'])
            ->where('user_id', $request->user()->id)
            ->where('status', 'connected')
            ->firstOrFail();

        // Verify import belongs to user
        $import = Import::where('id', $validated['import_id'])
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        // Get valid recipients from import
        $validRecipientsCount = Recipient::where('import_id', $import->id)
            ->where('user_id', $request->user()->id)
            ->where('is_valid', true)
            ->count();

        if ($validRecipientsCount === 0) {
            return back()->with('error', 'This import has no valid recipients.');
        }

        // Create campaign
        $campaign = Campaign::create([
            'user_id' => $request->user()->id,
            'wa_session_id' => $waSession->id,
            'import_id' => $import->id,
            'name' => $validated['name'],
            'message_template' => $validated['message_template'],
            'status' => $validated['start_immediately'] ? 'running' : 'pending',
            'total_recipients' => $validRecipientsCount,
            'sent_count' => 0,
            'failed_count' => 0,
            'started_at' => $validated['start_immediately'] ? now() : null,
            'throttling_cfg_json' => [
                'messages_per_minute' => $validated['messages_per_minute'] ?? 15,
                'delay_seconds' => $validated['delay_seconds'] ?? 4,
            ],
        ]);

        Log::info('Campaign created', [
            'campaign_id' => $campaign->id,
            'user_id' => $request->user()->id,
            'wa_session_id' => $waSession->id,
            'total_recipients' => $validRecipientsCount,
            'start_immediately' => $validated['start_immediately'] ?? false,
        ]);

        // If start immediately, dispatch jobs
        if ($validated['start_immediately']) {
            $this->dispatchCampaignJobs($campaign);

            return redirect()->route('campaigns.show', $campaign)
                ->with('success', "Campaign started! Sending {$validRecipientsCount} messages...");
        }

        return redirect()->route('campaigns.show', $campaign)
            ->with('success', 'Campaign created successfully. Click "Start Campaign" to begin sending.');
    }

    /**
     * Show campaign details
     */
    public function show(Request $request, Campaign $campaign)
    {
        $this->authorize('view', $campaign);

        $campaign->load(['waSession', 'import', 'messages' => function ($query) {
            $query->with('recipient')->latest()->limit(50);
        }]);

        // Get statistics
        $stats = [
            'total' => $campaign->total_recipients ?? 0,
            'sent' => $campaign->sent_count ?? 0,
            'failed' => $campaign->failed_count ?? 0,
            'queued' => max(0, ($campaign->total_recipients ?? 0) - ($campaign->sent_count ?? 0) - ($campaign->failed_count ?? 0)),
            'progress_percentage' => $campaign->getProgressPercentage(),
        ];

        // Format messages
        $messages = $campaign->messages->map(fn($message) => [
            'id' => $message->id,
            'recipient_name' => $message->recipient?->first_name . ' ' . $message->recipient?->last_name,
            'phone' => $message->phone_e164,
            'status' => $message->status,
            'body' => substr($message->body_rendered, 0, 100) . '...',
            'sent_at' => $message->sent_at?->format('M d, Y H:i:s'),
            'error_message' => $message->error_message,
            'error_code' => $message->error_code,
        ]);

        return Inertia::render('campaigns/Show', [
            'campaign' => [
                'id' => $campaign->id,
                'name' => $campaign->name,
                'status' => $campaign->status,
                'message_template' => $campaign->message_template,
                'throttling' => $campaign->throttling_cfg_json ?? [
                        'messages_per_minute' => 15,
                        'delay_seconds' => 4,
                    ],
                'started_at' => $campaign->started_at?->format('M d, Y H:i:s'),
                'finished_at' => $campaign->finished_at?->format('M d, Y H:i:s'),
                'created_at' => $campaign->created_at->format('M d, Y H:i:s'),
                'import' => [
                    'id' => $campaign->import?->id,
                    'filename' => $campaign->import?->filename ?? 'N/A',
                    'valid_rows' => $campaign->import?->valid_rows ?? 0,
                ],
                'device' => [
                    'id' => $campaign->waSession?->id,
                    'label' => $campaign->waSession?->device_label ?? 'N/A',
                    'status' => $campaign->waSession?->status ?? 'disconnected',
                    'phone' => $campaign->waSession?->getPhoneNumber(),
                ],
                'can_start' => $campaign->canStart(),
                'can_pause' => $campaign->canPause(),
                'can_resume' => $campaign->canResume(),
                'can_cancel' => $campaign->canCancel(),
            ],
            'stats' => $stats,
            'messages' => $messages,
        ]);
    }

    /**
     * Start campaign execution
     */
    public function start(Request $request, Campaign $campaign)
    {
        $this->authorize('update', $campaign);

        if (!$campaign->canStart()) {
            return back()->with('error', 'Cannot start this campaign. Status: ' . $campaign->status);
        }

        // Verify device is still connected
        if (!$campaign->waSession || !$campaign->waSession->isConnected()) {
            return back()->with('error', 'WhatsApp device is not connected. Please connect your device first.');
        }

        // Update campaign status
        $campaign->update([
            'status' => 'running',
            'started_at' => now(),
        ]);

        // Dispatch jobs
        $jobsDispatched = $this->dispatchCampaignJobs($campaign);

        Log::info('Campaign started manually', [
            'campaign_id' => $campaign->id,
            'user_id' => $request->user()->id,
            'jobs_dispatched' => $jobsDispatched,
        ]);

        return back()->with('success', "Campaign started! Dispatched {$jobsDispatched} messages.");
    }

    /**
     * Pause campaign
     */
    public function pause(Request $request, Campaign $campaign)
    {
        $this->authorize('update', $campaign);

        if (!$campaign->canPause()) {
            return back()->with('error', 'Cannot pause this campaign.');
        }

        $campaign->update(['status' => 'paused']);

        Log::info('Campaign paused', [
            'campaign_id' => $campaign->id,
            'user_id' => $request->user()->id,
        ]);

        return back()->with('success', 'Campaign paused. Queued messages will not be sent.');
    }

    /**
     * Resume paused campaign
     */
    public function resume(Request $request, Campaign $campaign)
    {
        $this->authorize('update', $campaign);

        if (!$campaign->canResume()) {
            return back()->with('error', 'Cannot resume this campaign.');
        }

        // Verify device is still connected
        if (!$campaign->waSession || !$campaign->waSession->isConnected()) {
            return back()->with('error', 'WhatsApp device is not connected.');
        }

        $campaign->update(['status' => 'running']);

        // Note: Already queued jobs will continue automatically
        // We don't need to re-dispatch them

        Log::info('Campaign resumed', [
            'campaign_id' => $campaign->id,
            'user_id' => $request->user()->id,
        ]);

        return back()->with('success', 'Campaign resumed. Messages will continue sending.');
    }

    /**
     * Cancel campaign
     */
    public function cancel(Request $request, Campaign $campaign)
    {
        $this->authorize('update', $campaign);

        if (!$campaign->canCancel()) {
            return back()->with('error', 'Cannot cancel this campaign.');
        }

        $campaign->update([
            'status' => 'canceled',
            'completed_at' => now(),
        ]);

        Log::info('Campaign canceled', [
            'campaign_id' => $campaign->id,
            'user_id' => $request->user()->id,
            'sent_count' => $campaign->sent_count,
            'failed_count' => $campaign->failed_count,
        ]);

        return back()->with('success', 'Campaign cancelled. No more messages will be sent.');
    }

    /**
     * Delete campaign
     */
    public function destroy(Request $request, Campaign $campaign)
    {
        $this->authorize('delete', $campaign);

        if (in_array($campaign->status, ['running', 'paused'])) {
            return back()->with('error', 'Cannot delete active campaign. Please cancel it first.');
        }

        $campaignId = $campaign->id;
        $campaign->delete();

        Log::info('Campaign deleted', [
            'campaign_id' => $campaignId,
            'user_id' => $request->user()->id,
        ]);

        return redirect()->route('campaigns.index')
            ->with('success', 'Campaign deleted successfully.');
    }

    /**
     * Dispatch campaign jobs with throttling
     */
    private function dispatchCampaignJobs(Campaign $campaign): int
    {
        // Get valid recipients from the import
        $recipients = Recipient::where('import_id', $campaign->import_id)
            ->where('user_id', $campaign->user_id)
            ->where('is_valid', true)
            ->get();

        if ($recipients->isEmpty()) {
            Log::warning('No valid recipients found for campaign', [
                'campaign_id' => $campaign->id,
            ]);
            return 0;
        }

        $throttling = $campaign->getThrottlingConfig();
        $delaySeconds = $throttling['delay_between_messages'] ?? 4;

        // Dispatch jobs for each recipient with delay
        $delay = 0;
        $dispatched = 0;

        foreach ($recipients as $recipient) {
            SendCampaignMessageJob::dispatch($campaign->id, $recipient->id)
                ->delay(now()->addSeconds($delay));

            $delay += $delaySeconds;
            $dispatched++;
        }

        Log::info('Campaign jobs dispatched', [
            'campaign_id' => $campaign->id,
            'total_jobs' => $dispatched,
            'total_delay' => $delay,
            'delay_per_message' => $delaySeconds,
        ]);

        return $dispatched;
    }
}
