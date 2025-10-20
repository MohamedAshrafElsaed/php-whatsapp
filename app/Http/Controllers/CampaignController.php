<?php

namespace App\Http\Controllers;

use App\Jobs\SendCampaignMessageJob;
use App\Models\Campaign;
use App\Models\Import;
use App\Models\Message;
use App\Models\Recipient;
use App\Models\Segment;
use App\Models\WaSession;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
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
                'queued_count' => max(0, ($campaign->total_recipients ?? 0) - ($campaign->sent_count ?? 0) - ($campaign->failed_count ?? 0)),
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
     * Store new campaign
     * @throws \Throwable
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'wa_session_id' => 'required|exists:wa_sessions,id',
            'selection_type' => ['required', Rule::in(['import', 'segment', 'contacts'])],
            'import_id' => 'nullable|required_if:selection_type,import|exists:imports,id',
            'segment_id' => 'nullable|required_if:selection_type,segment|exists:segments,id',
            'recipient_ids' => 'nullable|required_if:selection_type,contacts|array|min:1',
            'recipient_ids.*' => 'exists:recipients,id',

            // Message type and content
            'message_type' => ['required', Rule::in(['text', 'image', 'video', 'audio', 'file', 'link', 'location', 'contact', 'poll'])],
            'message_template' => 'required_if:message_type,text|nullable|string|max:4096',

            // Media fields
            'media' => 'required_if:message_type,image,video,audio,file|nullable|file|max:102400',
            'caption' => 'nullable|string|max:1024',

            // Link fields
            'link_url' => 'required_if:message_type,link|nullable|url|max:2048',

            // Location fields
            'latitude' => 'required_if:message_type,location|nullable|numeric|between:-90,90',
            'longitude' => 'required_if:message_type,location|nullable|numeric|between:-180,180',

            // Contact fields
            'contact_name' => 'required_if:message_type,contact|nullable|string|max:255',
            'contact_phone' => 'required_if:message_type,contact|nullable|string|max:50',

            // Poll fields
            'poll_question' => 'required_if:message_type,poll|nullable|string|max:255',
            'poll_options' => 'required_if:message_type,poll|nullable|array|min:2|max:12',
            'poll_options.*' => 'required|string|max:100',
            'poll_max_answer' => 'nullable|integer|min:1',

            'messages_per_minute' => 'nullable|integer|min:5|max:30',
            'delay_seconds' => 'nullable|integer|min:2|max:10',
            'start_immediately' => 'nullable|boolean',
        ]);

        // Verify the WhatsApp session belongs to user and is connected
        $waSession = WaSession::where('id', $validated['wa_session_id'])
            ->where('user_id', $request->user()->id)
            ->where('status', 'connected')
            ->firstOrFail();

        $recipientIds = [];
        $recipientsCount = 0;
        $importId = null;
        $segmentId = null;

        // Determine recipients based on selection type
        if ($validated['selection_type'] === 'import') {
            // Validate import belongs to user
            $import = Import::where('id', $validated['import_id'])
                ->where('user_id', $request->user()->id)
                ->firstOrFail();

            $importId = $import->id;

            // Get valid recipient IDs from import
            $recipientIds = Recipient::where('import_id', $import->id)
                ->where('user_id', $request->user()->id)
                ->where('is_valid', true)
                ->pluck('id')
                ->toArray();

            $recipientsCount = count($recipientIds);

            if ($recipientsCount === 0) {
                return back()->with('error', 'This import has no valid recipients.');
            }
        } elseif ($validated['selection_type'] === 'segment') {
            // Validate segment belongs to user
            $segment = Segment::where('id', $validated['segment_id'])
                ->where('user_id', $request->user()->id)
                ->firstOrFail();

            $segmentId = $segment->id;

            // Get valid recipient IDs from segment
            $recipientIds = $segment->recipients()
                ->where('is_valid', true)
                ->pluck('recipients.id')
                ->toArray();

            $recipientsCount = count($recipientIds);

            if ($recipientsCount === 0) {
                return back()->with('error', 'This segment has no valid recipients.');
            }
        } else {
            // Validate selected contacts belong to user
            $recipientIds = Recipient::whereIn('id', $validated['recipient_ids'])
                ->where('user_id', $request->user()->id)
                ->where('is_valid', true)
                ->pluck('id')
                ->toArray();

            $recipientsCount = count($recipientIds);

            if ($recipientsCount === 0) {
                return back()->with('error', 'No valid recipients selected.');
            }
        }

        DB::beginTransaction();
        try {

            // Handle media upload
            $mediaPath = null;
            $mediaFilename = null;
            $mediaMimeType = null;

            if ($request->hasFile('media')) {
                $file = $request->file('media');
                $mediaFilename = time() . '_' . $file->getClientOriginalName();
                $mediaPath = $file->storeAs('campaign_media', $mediaFilename, 'public');
                $mediaMimeType = $file->getMimeType();
            }


            // Create campaign
            $campaign = Campaign::create([
                'user_id' => $request->user()->id,
                'wa_session_id' => $waSession->id,
                'import_id' => $importId,
                'segment_id' => $segmentId,
                'name' => $validated['name'],

                // Message content
                'message_type' => $validated['message_type'],
                'message_template' => $validated['message_template'] ?? null,

                // Media
                'media_path' => $mediaPath,
                'media_filename' => $mediaFilename,
                'media_mime_type' => $mediaMimeType,
                'caption' => $validated['caption'] ?? null,

                // Link
                'link_url' => $validated['link_url'] ?? null,

                // Location
                'latitude' => $validated['latitude'] ?? null,
                'longitude' => $validated['longitude'] ?? null,

                // Contact
                'contact_name' => $validated['contact_name'] ?? null,
                'contact_phone' => $validated['contact_phone'] ?? null,

                // Poll
                'poll_question' => $validated['poll_question'] ?? null,
                'poll_options' => $validated['poll_options'] ?? null,
                'poll_max_answer' => $validated['poll_max_answer'] ?? 1,

                'status' => $validated['start_immediately'] ? 'running' : 'draft',
                'total_recipients' => $recipientsCount,
                'sent_count' => 0,
                'failed_count' => 0,
                'started_at' => $validated['start_immediately'] ? now() : null,
                'throttling_cfg_json' => [
                    'messages_per_minute' => $validated['messages_per_minute'] ?? 15,
                    'delay_seconds' => $validated['delay_seconds'] ?? 4,
                ],
                'settings_json' => [
                    'selection_type' => $validated['selection_type'],
                    'recipient_ids' => $recipientIds,
                ],
            ]);

            // Create message records for all recipients
            $this->createMessageRecords($campaign, $recipientIds);

            DB::commit();

            Log::info('Campaign created', [
                'campaign_id' => $campaign->id,
                'user_id' => $request->user()->id,
                'wa_session_id' => $waSession->id,
                'selection_type' => $validated['selection_type'],
                'total_recipients' => $recipientsCount,
                'start_immediately' => $validated['start_immediately'] ?? false,
            ]);

            // If start immediately, dispatch jobs
            if ($validated['start_immediately']) {
                $this->dispatchCampaignJobs($campaign);

                return redirect()->route('campaigns.show', $campaign)
                    ->with('success', "Campaign started! Sending {$recipientsCount} messages...");
            }

            return redirect()->route('campaigns.show', $campaign)
                ->with('success', 'Campaign created successfully. Click "Start Campaign" to begin sending.');

        } catch (Exception $e) {
            DB::rollBack();

            // Delete uploaded file if campaign creation failed
            if ($mediaPath && Storage::disk('public')->exists($mediaPath)) {
                Storage::disk('public')->delete($mediaPath);
            }

            Log::error('Failed to create campaign', [
                'error' => $e->getMessage(),
                'user_id' => $request->user()->id,
            ]);

            return back()->with('error', 'Failed to create campaign: ' . $e->getMessage());
        }
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

        // Get user's segments
        $segments = Segment::where('user_id', $request->user()->id)
            ->where('valid_contacts', '>', 0)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn($segment) => [
                'id' => $segment->id,
                'name' => $segment->name,
                'description' => $segment->description,
                'valid_contacts' => $segment->valid_contacts,
            ]);

        // Get user's individual contacts (not tied to any specific import)
        $contacts = Recipient::where('user_id', $request->user()->id)
            ->where('is_valid', true)
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get()
            ->map(fn($recipient) => [
                'id' => $recipient->id,
                'full_name' => trim($recipient->first_name . ' ' . $recipient->last_name),
                'phone_e164' => $recipient->phone_e164,
                'email' => $recipient->email,
            ]);

        // Get preview recipient (first valid recipient from user)
        $previewRecipient = Recipient::where('user_id', $request->user()->id)
            ->where('is_valid', true)
            ->first();

        // Get available variables from all recipients' extra fields
        $availableVariables = ['first_name', 'last_name', 'email', 'phone'];

        $extraKeys = Recipient::where('user_id', $request->user()->id)
            ->whereNotNull('extra_json')
            ->pluck('extra_json')
            ->filter()
            ->flatMap(fn($extra) => array_keys($extra))
            ->unique()
            ->values()
            ->toArray();

        $availableVariables = array_unique(array_merge($availableVariables, $extraKeys));

        return Inertia::render('campaigns/Create', [
            'connectedDevices' => $connectedDevices,
            'imports' => $imports,
            'segments' => $segments,
            'contacts' => $contacts,
            'previewRecipient' => $previewRecipient ? [
                'first_name' => $previewRecipient->first_name,
                'last_name' => $previewRecipient->last_name,
                'email' => $previewRecipient->email,
                'phone_e164' => $previewRecipient->phone_e164,
                'extra_json' => $previewRecipient->extra_json ?? [],
            ] : null,
            'availableVariables' => $availableVariables,
        ]);
    }

    /**
     * Create message records for campaign recipients
     */
    private function createMessageRecords(Campaign $campaign, array $recipientIds): void
    {
        $recipients = Recipient::whereIn('id', $recipientIds)->get();

        $messageData = [];
        foreach ($recipients as $recipient) {
            // For text messages and captions, render with recipient variables
            $renderedBody = null;

            if ($campaign->message_type === 'text') {
                // Render text message template
                $renderedBody = $this->renderMessageTemplate($campaign->message_template ?? '', $recipient);
            } elseif (in_array($campaign->message_type, ['image', 'video', 'file', 'link']) && $campaign->caption) {
                // Render caption with variables
                $renderedBody = $this->renderMessageTemplate($campaign->caption, $recipient);
            } else {
                // For other types (audio, location, contact, poll), use a placeholder
                $renderedBody = $this->getMessageTypePlaceholder($campaign);
            }

            $messageData[] = [
                'campaign_id' => $campaign->id,
                'recipient_id' => $recipient->id,
                'user_id' => $campaign->user_id,
                'wa_session_id' => $campaign->wa_session_id,
                'phone_e164' => $recipient->phone_e164,
                'body_rendered' => $renderedBody,
                'status' => 'queued',
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        // Bulk insert messages
        Message::insert($messageData);
    }

    /**
     * Get placeholder for non-text message types
     */
    private function getMessageTypePlaceholder(Campaign $campaign): string
    {
        switch ($campaign->message_type) {
            case 'image':
                return '[Image]' . ($campaign->caption ? ': ' . $campaign->caption : '');
            case 'video':
                return '[Video]' . ($campaign->caption ? ': ' . $campaign->caption : '');
            case 'audio':
                return '[Audio]';
            case 'file':
                return '[File]' . ($campaign->caption ? ': ' . $campaign->caption : '');
            case 'link':
                return '[Link: ' . $campaign->link_url . ']' . ($campaign->caption ? ' - ' . $campaign->caption : '');
            case 'location':
                return '[Location: ' . $campaign->latitude . ', ' . $campaign->longitude . ']';
            case 'contact':
                return '[Contact: ' . $campaign->contact_name . ']';
            case 'poll':
                return '[Poll: ' . $campaign->poll_question . ']';
            default:
                return '[Message]';
        }
    }


    /**
     * Render message template with recipient variables
     */
    private function renderMessageTemplate(?string $template, Recipient $recipient): string
    {
        if (!$template) {
            return '';
        }

        $rendered = $template;

        // Replace standard variables
        $rendered = str_replace('{{first_name}}', $recipient->first_name ?? '', $rendered);
        $rendered = str_replace('{{last_name}}', $recipient->last_name ?? '', $rendered);
        $rendered = str_replace('{{email}}', $recipient->email ?? '', $rendered);
        $rendered = str_replace('{{phone}}', $recipient->phone_e164 ?? '', $rendered);

        // Replace extra JSON variables
        if ($recipient->extra_json) {
            foreach ($recipient->extra_json as $key => $value) {
                $rendered = str_replace("{{" . $key . "}}", (string)$value, $rendered);
            }
        }

        return $rendered;
    }

    /**
     * Dispatch campaign jobs with throttling
     */
    private function dispatchCampaignJobs(Campaign $campaign): int
    {
        // Get queued messages for this campaign
        $messages = Message::where('campaign_id', $campaign->id)
            ->where('status', 'queued')
            ->get();

        if ($messages->isEmpty()) {
            Log::warning('No queued messages found for campaign', [
                'campaign_id' => $campaign->id,
            ]);
            return 0;
        }

        $throttling = $campaign->getThrottlingConfig();
        $delaySeconds = $throttling['delay_between_messages'] ?? 4;

        // Dispatch jobs for each message with progressive delay
        $delay = 0;
        $dispatched = 0;

        foreach ($messages as $message) {
            SendCampaignMessageJob::dispatch($message->id)
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

    /**
     * Show campaign details
     */
    public function show(Request $request, Campaign $campaign)
    {
        $this->authorize('view', $campaign);

        $campaign->load(['waSession', 'import']);

        // Get statistics
        $stats = [
            'total' => $campaign->total_recipients ?? 0,
            'sent' => $campaign->sent_count ?? 0,
            'failed' => $campaign->failed_count ?? 0,
            'queued' => max(0, ($campaign->total_recipients ?? 0) - ($campaign->sent_count ?? 0) - ($campaign->failed_count ?? 0)),
            'progress_percentage' => $campaign->getProgressPercentage(),
        ];

        // Get message history (last 50 messages)
        $messages = Message::where('campaign_id', $campaign->id)
            ->with('recipient')
            ->latest()
            ->limit(50)
            ->get()
            ->map(fn($message) => [
                'id' => $message->id,
                'recipient_name' => $message->recipient ? trim($message->recipient->first_name . ' ' . $message->recipient->last_name) : 'Unknown',
                'phone' => $message->phone_e164,
                'status' => $message->status,
                'sent_at' => $message->sent_at?->format('M d, Y H:i:s'),
                'error_message' => $message->error_message,
            ]);

        $selectionType = $campaign->settings_json['selection_type'] ?? 'import';

        return Inertia::render('campaigns/Show', [
            'campaign' => [
                'id' => $campaign->id,
                'name' => $campaign->name,
                'status' => $campaign->status,
                'selection_type' => $selectionType,
                'message_template' => $campaign->message_template,
                'throttling' => $campaign->throttling_cfg_json ?? [
                        'messages_per_minute' => 15,
                        'delay_seconds' => 4,
                    ],
                'started_at' => $campaign->started_at?->format('M d, Y H:i:s'),
                'finished_at' => $campaign->finished_at?->format('M d, Y H:i:s'),
                'created_at' => $campaign->created_at->format('M d, Y H:i:s'),
                'import' => $campaign->import ? [
                    'id' => $campaign->import->id,
                    'filename' => $campaign->import->filename,
                    'valid_rows' => $campaign->import->valid_rows,
                ] : null,
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
}
