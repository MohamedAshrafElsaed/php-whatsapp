<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Models\Import;
use App\Models\Recipient;
use App\Models\WaSession;
use App\Jobs\SendCampaignMessageJob;
use Illuminate\Http\Request;
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

        // Create campaign
        $campaign = Campaign::create([
            'user_id' => $request->user()->id,
            'wa_session_id' => $waSession->id,
            'import_id' => $import->id,
            'name' => $validated['name'],
            'message_template' => $validated['message_template'],
            'status' => 'pending',
            'total_recipients' => $validRecipientsCount,
            'sent_count' => 0,
            'failed_count' => 0,
            'throttling_cfg_json' => [
                'messages_per_minute' => $validated['messages_per_minute'] ?? 15,
                'delay_seconds' => $validated['delay_seconds'] ?? 4,
            ],
        ]);

        // If start immediately, redirect to show page where user can start it
        if ($validated['start_immediately'] ?? false) {
            return redirect()->route('campaigns.show', $campaign)
                ->with('success', 'Campaign created. Click "Start Campaign" to begin sending.');
        }

        return redirect()->route('campaigns.show', $campaign)
            ->with('success', 'Campaign created successfully. You can start it when ready.');
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
            'queued' => ($campaign->total_recipients ?? 0) - ($campaign->sent_count ?? 0) - ($campaign->failed_count ?? 0),
        ];

        // Format messages
        $messages = $campaign->messages->map(fn($message) => [
            'id' => $message->id,
            'recipient_name' => $message->recipient?->full_name ?? 'N/A',
            'phone' => $message->phone_e164,
            'status' => $message->status,
            'sent_at' => $message->sent_at?->format('M d, Y H:i'),
            'error_message' => $message->error_message,
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
                'started_at' => $campaign->started_at?->format('M d, Y H:i'),
                'finished_at' => $campaign->finished_at?->format('M d, Y H:i'),
                'created_at' => $campaign->created_at->format('M d, Y H:i'),
                'import' => [
                    'id' => $campaign->import?->id,
                    'filename' => $campaign->import?->filename ?? 'N/A',
                    'valid_rows' => $campaign->import?->valid_rows ?? 0,
                ],
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
            return back()->with('error', 'Cannot start this campaign.');
        }

        // Verify device is still connected
        if (!$campaign->waSession || !$campaign->waSession->isConnected()) {
            return back()->with('error', 'WhatsApp device is not connected.');
        }

        // Update campaign status
        $campaign->update([
            'status' => 'running',
            'started_at' => now(),
        ]);

        // Get valid recipients from the import
        $recipients = Recipient::where('import_id', $campaign->import_id)
            ->where('user_id', $campaign->user_id)
            ->where('is_valid', true)
            ->get();

        $throttling = $campaign->throttling_cfg_json ?? ['delay_seconds' => 4];
        $delaySeconds = $throttling['delay_seconds'] ?? 4;

        // Dispatch jobs for each recipient with delay
        $delay = 0;
        foreach ($recipients as $recipient) {
            SendCampaignMessageJob::dispatch($campaign, $recipient)
                ->delay(now()->addSeconds($delay));

            $delay += $delaySeconds;
        }

        return back()->with('success', 'Campaign started successfully. Messages are being sent.');
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

        return back()->with('success', 'Campaign paused.');
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

        return back()->with('success', 'Campaign cancelled.');
    }

    /**
     * Delete campaign
     */
    public function destroy(Request $request, Campaign $campaign)
    {
        $this->authorize('delete', $campaign);

        if (in_array($campaign->status, ['running', 'paused'])) {
            return back()->with('error', 'Cannot delete active campaign.');
        }

        $campaign->delete();

        return redirect()->route('campaigns.index')
            ->with('success', 'Campaign deleted.');
    }
}
