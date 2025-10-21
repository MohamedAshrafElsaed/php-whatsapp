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
    public function index(Request $request)
    {
        $campaigns = Campaign::with(['waSession', 'import', 'segment'])
            ->where('user_id', $request->user()->id)
            ->orderBy('created_at', 'desc')
            ->paginate(20)
            ->through(fn($campaign) => [
                'id' => $campaign->id,
                'name' => $campaign->name,
                'status' => $campaign->status,
                'message_type' => $campaign->message_type,
                'created_at' => $campaign->created_at->format('M d, Y'),
                'import' => [
                    'filename' => $campaign->import?->filename ?? ($campaign->segment?->name ?? 'N/A'),
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

    public function store(Request $request)
    {
        // Base validation rules
        $rules = [
            'name' => 'required|string|max:255',
            'wa_session_id' => 'required|exists:wa_sessions,id',
            'selection_type' => ['required', Rule::in(['import', 'segment', 'contacts'])],
            'import_id' => 'nullable|required_if:selection_type,import|exists:imports,id',
            'segment_id' => 'nullable|required_if:selection_type,segment|exists:segments,id',
            'recipient_ids' => 'nullable|required_if:selection_type,contacts|array|min:1',
            'recipient_ids.*' => 'exists:recipients,id',

            'message_type' => ['required', Rule::in(['text', 'image', 'video', 'audio', 'file', 'link', 'location', 'contact', 'poll'])],
            'messages_per_minute' => 'nullable|integer|min:5|max:30',
            'delay_seconds' => 'nullable|integer|min:2|max:10',
            'start_immediately' => 'nullable|boolean',
        ];

        // Add conditional validation based on message_type
        $messageType = $request->input('message_type');

        switch ($messageType) {
            case 'text':
                $rules['message_template'] = 'required|string|max:4096';
                break;

            case 'image':
                $rules['media'] = 'required|file|mimes:jpg,jpeg,png,gif,webp|max:10240';
                $rules['caption'] = 'nullable|string|max:1024';
                break;

            case 'video':
                $rules['media'] = 'required|file|mimes:mp4,avi,mov,mkv|max:102400';
                $rules['caption'] = 'nullable|string|max:1024';
                break;

            case 'audio':
                $rules['media'] = 'required|file|mimes:mp3,wav,ogg,m4a,aac|max:10240';
                break;

            case 'file':
                $rules['media'] = 'required|file|max:102400';
                $rules['caption'] = 'nullable|string|max:1024';
                break;

            case 'link':
                $rules['link_url'] = 'required|url|max:2048';
                $rules['caption'] = 'nullable|string|max:1024';
                break;

            case 'location':
                $rules['latitude'] = 'required|numeric|between:-90,90';
                $rules['longitude'] = 'required|numeric|between:-180,180';
                break;

            case 'contact':
                $rules['contact_name'] = 'required|string|max:255';
                $rules['contact_phone'] = 'required|string|max:50';
                break;

            case 'poll':
                $rules['poll_question'] = 'required|string|max:255';
                $rules['poll_options'] = 'required|array|min:2|max:12';
                $rules['poll_options.*'] = 'required|string|max:100';
                $rules['poll_max_answer'] = 'nullable|integer|min:1';
                break;
        }

        $validated = $request->validate($rules);

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
            $import = Import::where('id', $validated['import_id'])
                ->where('user_id', $request->user()->id)
                ->firstOrFail();

            $importId = $import->id;

            $recipientIds = Recipient::where('import_id', $import->id)
                ->where('user_id', $request->user()->id)
                ->where('is_valid', true)
                ->pluck('id')
                ->toArray();

            $recipientsCount = count($recipientIds);

            if ($recipientsCount === 0) {
                return back()->withErrors(['error' => 'This import has no valid recipients.'])->withInput();
            }
        } elseif ($validated['selection_type'] === 'segment') {
            $segment = Segment::where('id', $validated['segment_id'])
                ->where('user_id', $request->user()->id)
                ->firstOrFail();

            $segmentId = $segment->id;

            $recipientIds = $segment->recipients()
                ->where('is_valid', true)
                ->pluck('recipients.id')
                ->toArray();

            $recipientsCount = count($recipientIds);

            if ($recipientsCount === 0) {
                return back()->withErrors(['error' => 'This segment has no valid recipients.'])->withInput();
            }
        } else {
            $recipientIds = Recipient::whereIn('id', $validated['recipient_ids'])
                ->where('user_id', $request->user()->id)
                ->where('is_valid', true)
                ->pluck('id')
                ->toArray();

            $recipientsCount = count($recipientIds);

            if ($recipientsCount === 0) {
                return back()->withErrors(['error' => 'No valid recipients selected.'])->withInput();
            }
        }

        DB::beginTransaction();
        try {
            $mediaPath = null;
            $mediaFilename = null;
            $mediaMimeType = null;

            if ($request->hasFile('media')) {
                $file = $request->file('media');

                // Validate file was uploaded successfully
                if (!$file->isValid()) {
                    throw new \Exception('File upload failed');
                }

                $mediaFilename = time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $file->getClientOriginalName());
                $mediaPath = $file->storeAs('campaign_media', $mediaFilename, 'public');

                if (!$mediaPath) {
                    throw new \Exception('Failed to store media file');
                }

                $mediaMimeType = $file->getMimeType();
            }

            $campaign = Campaign::create([
                'user_id' => $request->user()->id,
                'wa_session_id' => $waSession->id,
                'import_id' => $importId,
                'segment_id' => $segmentId,
                'name' => $validated['name'],

                'message_type' => $validated['message_type'],
                'message_template' => $validated['message_template'] ?? null,

                'media_path' => $mediaPath,
                'media_filename' => $mediaFilename,
                'media_mime_type' => $mediaMimeType,
                'caption' => $validated['caption'] ?? null,

                'link_url' => $validated['link_url'] ?? null,

                'latitude' => $validated['latitude'] ?? null,
                'longitude' => $validated['longitude'] ?? null,

                'contact_name' => $validated['contact_name'] ?? null,
                'contact_phone' => $validated['contact_phone'] ?? null,

                'poll_question' => $validated['poll_question'] ?? null,
                'poll_options' => $validated['poll_options'] ?? null,
                'poll_max_answer' => $validated['poll_max_answer'] ?? 1,

                'status' => $validated['start_immediately'] ?? false ? 'running' : 'draft',
                'total_recipients' => $recipientsCount,
                'sent_count' => 0,
                'failed_count' => 0,
                'started_at' => $validated['start_immediately'] ?? false ? now() : null,
                'throttling_cfg_json' => [
                    'messages_per_minute' => $validated['messages_per_minute'] ?? 15,
                    'delay_between_messages' => $validated['delay_seconds'] ?? 4,
                ],
                'settings_json' => [
                    'selection_type' => $validated['selection_type'],
                    'recipient_ids' => $recipientIds,
                ],
            ]);

            $this->createMessageRecords($campaign, $recipientIds);

            DB::commit();

            Log::info('Campaign created', [
                'campaign_id' => $campaign->id,
                'user_id' => $request->user()->id,
                'wa_session_id' => $waSession->id,
                'selection_type' => $validated['selection_type'],
                'message_type' => $validated['message_type'],
                'total_recipients' => $recipientsCount,
                'start_immediately' => $validated['start_immediately'] ?? false,
            ]);

            if ($validated['start_immediately'] ?? false) {
                $jobsDispatched = $this->dispatchCampaignJobs($campaign);

                return redirect()->route('campaigns.show', $campaign)
                    ->with('success', "Campaign started! Sending {$recipientsCount} messages...");
            }

            return redirect()->route('campaigns.show', $campaign)
                ->with('success', 'Campaign created successfully. Click "Start Campaign" to begin sending.');

        } catch (Exception $e) {
            DB::rollBack();

            if (isset($mediaPath) && $mediaPath && Storage::disk('public')->exists($mediaPath)) {
                Storage::disk('public')->delete($mediaPath);
            }

            Log::error('Failed to create campaign', [
                'error' => $e->getMessage(),
                'user_id' => $request->user()->id,
                'trace' => $e->getTraceAsString(),
            ]);

            return back()->withErrors(['error' => 'Failed to create campaign: ' . $e->getMessage()])->withInput();
        }
    }

    public function create(Request $request)
    {
        $connectedDevices = WaSession::where('user_id', $request->user()->id)
            ->where('status', 'connected')
            ->orderBy('is_primary', 'desc')
            ->orderBy('created_at', 'desc')
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

        $previewRecipient = Recipient::where('user_id', $request->user()->id)
            ->where('is_valid', true)
            ->first();

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

    private function createMessageRecords(Campaign $campaign, array $recipientIds): void
    {
        $recipients = Recipient::whereIn('id', $recipientIds)->get();

        $messageData = [];
        foreach ($recipients as $recipient) {
            $renderedBody = null;

            if ($campaign->message_type === 'text') {
                $renderedBody = $this->renderMessageTemplate($campaign->message_template ?? '', $recipient);
            } elseif (in_array($campaign->message_type, ['image', 'video', 'file', 'link']) && $campaign->caption) {
                $renderedBody = $this->renderMessageTemplate($campaign->caption, $recipient);
            } else {
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

        // Insert in chunks for large campaigns
        $chunks = array_chunk($messageData, 500);
        foreach ($chunks as $chunk) {
            Message::insert($chunk);
        }
    }

    private function renderMessageTemplate(?string $template, Recipient $recipient): string
    {
        if (!$template) {
            return '';
        }

        $rendered = $template;

        $rendered = str_replace('{{first_name}}', $recipient->first_name ?? '', $rendered);
        $rendered = str_replace('{{last_name}}', $recipient->last_name ?? '', $rendered);
        $rendered = str_replace('{{email}}', $recipient->email ?? '', $rendered);
        $rendered = str_replace('{{phone}}', $recipient->phone_e164 ?? '', $rendered);

        if ($recipient->extra_json) {
            foreach ($recipient->extra_json as $key => $value) {
                $rendered = str_replace("{{" . $key . "}}", (string)$value, $rendered);
            }
        }

        return $rendered;
    }

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

    private function dispatchCampaignJobs(Campaign $campaign): int
    {
        $messages = Message::where('campaign_id', $campaign->id)
            ->where('status', 'queued')
            ->get();

        if ($messages->isEmpty()) {
            Log::warning('No queued messages found for campaign', [
                'campaign_id' => $campaign->id,
            ]);
            return 0;
        }

        $throttling = $campaign->throttling_cfg_json ?? [];
        $delaySeconds = $throttling['delay_between_messages'] ?? 4;

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

    public function show(Request $request, Campaign $campaign)
    {
        $this->authorize('view', $campaign);

        $campaign->load(['waSession', 'import', 'segment']);

        $stats = [
            'total' => $campaign->total_recipients ?? 0,
            'sent' => $campaign->sent_count ?? 0,
            'failed' => $campaign->failed_count ?? 0,
            'queued' => max(0, ($campaign->total_recipients ?? 0) - ($campaign->sent_count ?? 0) - ($campaign->failed_count ?? 0)),
            'progress_percentage' => $campaign->total_recipients > 0
                ? round((($campaign->sent_count + $campaign->failed_count) / $campaign->total_recipients) * 100, 2)
                : 0,
        ];

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
                'error_code' => $message->error_code,
            ]);

        $selectionType = $campaign->settings_json['selection_type'] ?? 'import';

        return Inertia::render('campaigns/Show', [
            'campaign' => [
                'id' => $campaign->id,
                'name' => $campaign->name,
                'status' => $campaign->status,
                'message_type' => $campaign->message_type,
                'selection_type' => $selectionType,
                'message_template' => $campaign->message_template,
                'caption' => $campaign->caption,
                'throttling' => $campaign->throttling_cfg_json ?? [
                        'messages_per_minute' => 15,
                        'delay_between_messages' => 4,
                    ],
                'started_at' => $campaign->started_at?->format('M d, Y H:i:s'),
                'finished_at' => $campaign->finished_at?->format('M d, Y H:i:s'),
                'created_at' => $campaign->created_at->format('M d, Y H:i:s'),
                'import' => $campaign->import ? [
                    'id' => $campaign->import->id,
                    'filename' => $campaign->import->filename,
                    'valid_rows' => $campaign->import->valid_rows,
                ] : null,
                'segment' => $campaign->segment ? [
                    'id' => $campaign->segment->id,
                    'name' => $campaign->segment->name,
                    'valid_contacts' => $campaign->segment->valid_contacts,
                ] : null,
            ],
            'stats' => $stats,
            'messages' => $messages,
        ]);
    }

    public function start(Request $request, Campaign $campaign)
    {
        $this->authorize('update', $campaign);

        if (!in_array($campaign->status, ['draft', 'paused'])) {
            return back()->with('error', 'Cannot start this campaign. Status: ' . $campaign->status);
        }

        if (!$campaign->waSession || !$campaign->waSession->isConnected()) {
            return back()->with('error', 'WhatsApp device is not connected. Please connect your device first.');
        }

        $campaign->update([
            'status' => 'running',
            'started_at' => $campaign->started_at ?? now(),
        ]);

        $jobsDispatched = $this->dispatchCampaignJobs($campaign);

        Log::info('Campaign started manually', [
            'campaign_id' => $campaign->id,
            'user_id' => $request->user()->id,
            'jobs_dispatched' => $jobsDispatched,
        ]);

        return back()->with('success', "Campaign started! Dispatched {$jobsDispatched} messages.");
    }

    public function pause(Request $request, Campaign $campaign)
    {
        $this->authorize('update', $campaign);

        if ($campaign->status !== 'running') {
            return back()->with('error', 'Cannot pause this campaign. Current status: ' . $campaign->status);
        }

        $campaign->update(['status' => 'paused']);

        Log::info('Campaign paused', [
            'campaign_id' => $campaign->id,
            'user_id' => $request->user()->id,
        ]);

        return back()->with('success', 'Campaign paused. Queued messages will not be sent.');
    }

    public function resume(Request $request, Campaign $campaign)
    {
        $this->authorize('update', $campaign);

        if ($campaign->status !== 'paused') {
            return back()->with('error', 'Cannot resume this campaign. Current status: ' . $campaign->status);
        }

        if (!$campaign->waSession || !$campaign->waSession->isConnected()) {
            return back()->with('error', 'WhatsApp device is not connected.');
        }

        $campaign->update(['status' => 'running']);

        $jobsDispatched = $this->dispatchCampaignJobs($campaign);

        Log::info('Campaign resumed', [
            'campaign_id' => $campaign->id,
            'user_id' => $request->user()->id,
            'jobs_dispatched' => $jobsDispatched,
        ]);

        return back()->with('success', "Campaign resumed. Dispatched {$jobsDispatched} pending messages.");
    }

    public function cancel(Request $request, Campaign $campaign)
    {
        $this->authorize('update', $campaign);

        if (!in_array($campaign->status, ['draft', 'running', 'paused'])) {
            return back()->with('error', 'Cannot cancel this campaign. Current status: ' . $campaign->status);
        }

        $campaign->update([
            'status' => 'canceled',
            'finished_at' => now(),
        ]);

        Log::info('Campaign canceled', [
            'campaign_id' => $campaign->id,
            'user_id' => $request->user()->id,
            'sent_count' => $campaign->sent_count,
            'failed_count' => $campaign->failed_count,
        ]);

        return back()->with('success', 'Campaign cancelled. No more messages will be sent.');
    }

    public function destroy(Request $request, Campaign $campaign)
    {
        $this->authorize('delete', $campaign);

        if (in_array($campaign->status, ['running', 'paused'])) {
            return back()->with('error', 'Cannot delete active campaign. Please cancel it first.');
        }

        $campaignId = $campaign->id;

        // Delete associated media file if exists
        if ($campaign->media_path && Storage::disk('public')->exists($campaign->media_path)) {
            Storage::disk('public')->delete($campaign->media_path);
        }

        $campaign->delete();

        Log::info('Campaign deleted', [
            'campaign_id' => $campaignId,
            'user_id' => $request->user()->id,
        ]);

        return redirect()->route('campaigns.index')
            ->with('success', 'Campaign deleted successfully.');
    }
}
