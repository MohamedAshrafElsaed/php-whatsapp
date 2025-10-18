<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Message;
use App\Models\Recipient;
use App\Models\WaSession;
use App\Services\BridgeManager;
use App\Services\FacebookConversionsApiService;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;
use Log;

class ContactController extends Controller
{
    public function __construct(
        private readonly BridgeManager $bridgeManager,
        private readonly FacebookConversionsApiService $facebookService
    ) {
    }

    /**
     * List all contacts with search
     */
    public function index(Request $request): Response
    {
        $search = $request->input('search');

        $contacts = Recipient::where('user_id', $request->user()->id)
            ->where('is_valid', true)
            ->with(['import', 'messages' => function ($query) {
                $query->latest()->limit(1);
            }])
            ->search($search)
            ->orderBy('created_at', 'desc')
            ->paginate(20)
            ->withQueryString()
            ->through(fn($contact) => [
                'id' => $contact->id,
                'full_name' => $contact->full_name,
                'phone_e164' => $contact->phone_e164,
                'email' => $contact->email,
                'import_source' => $contact->import?->filename ?? 'Manual Entry',
                'last_message_date' => $contact->messages->first()?->created_at?->format('M d, Y'),
            ]);

        return Inertia::render('contacts/Index', [
            'contacts' => $contacts,
            'search' => $search,
        ]);
    }

    /**
     * Store new contact
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'phone' => 'required|string|max:50',
            'email' => 'nullable|email|max:255',
            'extra_fields' => 'nullable|array',
        ]);

        $phoneUtil = PhoneNumberUtil::getInstance();
        $phoneRaw = trim($request->phone);
        $phoneE164 = null;
        $errors = [];

        // Normalize phone to E.164
        try {
            $phoneNumber = $phoneUtil->parse($phoneRaw, null);
            if ($phoneUtil->isValidNumber($phoneNumber)) {
                $phoneE164 = $phoneUtil->format($phoneNumber, PhoneNumberFormat::E164);
            } else {
                $errors[] = 'Invalid phone number';
            }
        } catch (NumberParseException $e) {
            $errors[] = 'Failed to parse phone number';
        }

        // Check for duplicate
        if ($phoneE164) {
            $exists = Recipient::where('user_id', $request->user()->id)
                ->where('phone_e164', $phoneE164)
                ->exists();

            if ($exists) {
                return redirect()->back()
                    ->withErrors(['phone' => 'This phone number already exists in your contacts.'])
                    ->withInput();
            }
        }

        $isValid = empty($errors) && $phoneE164;

        $contact = Recipient::create([
            'user_id' => $request->user()->id,
            'import_id' => null,
            'phone_raw' => $phoneRaw,
            'phone_e164' => $phoneE164,
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'email' => $request->email,
            'extra_json' => $request->extra_fields ?? [],
            'is_valid' => $isValid,
            'validation_errors_json' => $errors ?: null,
        ]);

        AuditLog::log('created', 'Recipient', $contact->id, [
            'name' => $contact->full_name,
            'phone' => $contact->phone_e164,
        ]);

        if (!$isValid) {
            return redirect()->route('contacts.index')
                ->with('error', 'Contact created but phone number is invalid: ' . implode(', ', $errors));
        }

        return redirect()->route('contacts.show', $contact)
            ->with('success', 'Contact created successfully.');
    }

    /**
     * Show contact creation form
     */
    public function create(): Response
    {
        return Inertia::render('contacts/Create');
    }

    /**
     * Show contact details and message history
     */
    public function show(Request $request, Recipient $recipient): Response
    {
        $this->authorize('view', $recipient);

        $recipient->load('import');

        // Get user's connected WhatsApp sessions
        $sessions = WaSession::where('user_id', $request->user()->id)
            ->where('status', 'connected')
            ->orderBy('is_primary', 'desc')
            ->get()
            ->map(fn($session) => [
                'id' => $session->id,
                'device_id' => $session->device_id,
                'device_label' => $session->device_label,
                'phone' => $session->getPhoneNumber(),
                'name' => $session->getName(),
                'is_primary' => $session->is_primary,
            ]);

        // Get message history (last 50 messages)
        $messages = Message::where('recipient_id', $recipient->id)
            ->where('user_id', $request->user()->id)
            ->with('campaign')
            ->latest()
            ->limit(50)
            ->get()
            ->map(fn($message) => [
                'id' => $message->id,
                'campaign_name' => $message->campaign?->name ?? 'Individual Message',
                'body' => $message->body_rendered,
                'status' => $message->status,
                'sent_at' => $message->sent_at?->format('M d, Y H:i'),
                'created_at' => $message->created_at->format('M d, Y H:i'),
            ]);

        return Inertia::render('contacts/Show', [
            'contact' => [
                'id' => $recipient->id,
                'full_name' => $recipient->full_name,
                'first_name' => $recipient->first_name,
                'last_name' => $recipient->last_name,
                'phone_e164' => $recipient->phone_e164,
                'phone_raw' => $recipient->phone_raw,
                'email' => $recipient->email,
                'extra_json' => $recipient->extra_json,
                'import_source' => $recipient->import?->filename ?? 'Manual Entry',
                'created_at' => $recipient->created_at->format('M d, Y'),
            ],
            'messages' => $messages,
            'sessions' => $sessions,
        ]);
    }

    /**
     * Send text message to contact
     */
    public function sendMessage(Request $request, Recipient $recipient): RedirectResponse
    {
        $this->authorize('view', $recipient);

        $request->validate([
            'message' => 'required|string|max:4096',
            'wa_session_id' => 'nullable|exists:wa_sessions,id',
        ]);

        try {
            // Get WhatsApp session
            $session = $this->getActiveSession($request);

            // Create message record
            $message = Message::create([
                'campaign_id' => null,
                'recipient_id' => $recipient->id,
                'user_id' => $request->user()->id,
                'wa_session_id' => $session->id,
                'phone_e164' => $recipient->phone_e164,
                'body_rendered' => $request->message,
                'status' => 'queued',
            ]);

            // Get bridge client for this session
            $bridge = $this->bridgeManager->getClientForSession($session);

            // Send message
            $response = $bridge->sendMessage(
                $recipient->phone_e164,
                $request->message
            );

            // Mark as sent
            $message->update([
                'status' => 'sent',
                'sent_at' => now(),
            ]);

            AuditLog::log('sent_message', 'Message', $message->id, [
                'recipient_id' => $recipient->id,
                'phone' => $recipient->phone_e164,
                'session_id' => $session->id,
            ]);

            // Track Facebook event
            $this->trackFacebookContact($request, $message, $recipient);

            return redirect()->route('contacts.show', $recipient)
                ->with('success', 'Message sent successfully.');

        } catch (Exception $e) {
            if (isset($message)) {
                $message->update([
                    'status' => 'failed',
                    'error_code' => 'SEND_ERROR',
                    'error_message' => substr($e->getMessage(), 0, 500),
                ]);
            }

            Log::error('Failed to send message', [
                'recipient_id' => $recipient->id,
                'error' => $e->getMessage(),
            ]);

            return redirect()->route('contacts.show', $recipient)
                ->with('error', 'Failed to send message: ' . $e->getMessage());
        }
    }

    /**
     * Send media message (image, video, audio, file)
     */
    public function sendMedia(Request $request, Recipient $recipient): RedirectResponse
    {
        $this->authorize('view', $recipient);

        $request->validate([
            'media_type' => 'required|in:image,video,audio,file',
            'media' => 'required|file|max:102400', // 100MB max
            'caption' => 'nullable|string|max:1024',
            'wa_session_id' => 'nullable|exists:wa_sessions,id',
        ]);

        try {
            $session = $this->getActiveSession($request);
            $bridge = $this->bridgeManager->getClientForSession($session);

            $file = $request->file('media');
            $mediaType = $request->media_type;
            $caption = $request->caption ?? '';

            // Get file contents and info
            $fileContents = file_get_contents($file->getRealPath());
            $fileName = $file->getClientOriginalName();
            $mimeType = $file->getMimeType();

            // Send based on type
            switch ($mediaType) {
                case 'image':
                    $response = $bridge->sendImage($recipient->phone_e164, $fileContents, $fileName, $caption);
                    break;
                case 'video':
                    $response = $bridge->sendVideo($recipient->phone_e164, $fileContents, $fileName, $caption);
                    break;
                case 'audio':
                    $response = $bridge->sendAudio($recipient->phone_e164, $fileContents, $fileName);
                    break;
                case 'file':
                    $response = $bridge->sendFile($recipient->phone_e164, $fileContents, $fileName, $caption);
                    break;
            }

            // Create message record
            $message = Message::create([
                'campaign_id' => null,
                'recipient_id' => $recipient->id,
                'user_id' => $request->user()->id,
                'wa_session_id' => $session->id,
                'phone_e164' => $recipient->phone_e164,
                'body_rendered' => $caption ?: "[{$mediaType}]",
                'status' => 'sent',
                'sent_at' => now(),
            ]);

            AuditLog::log('sent_media', 'Message', $message->id, [
                'recipient_id' => $recipient->id,
                'media_type' => $mediaType,
            ]);

            return redirect()->route('contacts.show', $recipient)
                ->with('success', ucfirst($mediaType) . ' sent successfully.');

        } catch (Exception $e) {
            Log::error('Failed to send media', [
                'recipient_id' => $recipient->id,
                'media_type' => $request->media_type,
                'error' => $e->getMessage(),
            ]);
            dd($e->getMessage());

            return redirect()->route('contacts.show', $recipient)
                ->with('error', 'Failed to send media: ' . $e->getMessage());
        }
    }

    /**
     * Send link with preview
     */
    public function sendLink(Request $request, Recipient $recipient): RedirectResponse
    {
        $this->authorize('view', $recipient);

        $request->validate([
            'link' => 'required|url|max:2048',
            'caption' => 'nullable|string|max:1024',
            'wa_session_id' => 'nullable|exists:wa_sessions,id',
        ]);

        try {
            $session = $this->getActiveSession($request);
            $bridge = $this->bridgeManager->getClientForSession($session);

            $response = $bridge->sendLink(
                $recipient->phone_e164,
                $request->link,
                $request->caption ?? ''
            );

            $message = Message::create([
                'campaign_id' => null,
                'recipient_id' => $recipient->id,
                'user_id' => $request->user()->id,
                'wa_session_id' => $session->id,
                'phone_e164' => $recipient->phone_e164,
                'body_rendered' => $request->link . ($request->caption ? "\n\n" . $request->caption : ''),
                'status' => 'sent',
                'sent_at' => now(),
            ]);

            return redirect()->route('contacts.show', $recipient)
                ->with('success', 'Link sent successfully.');

        } catch (Exception $e) {
            return redirect()->route('contacts.show', $recipient)
                ->with('error', 'Failed to send link: ' . $e->getMessage());
        }
    }

    /**
     * Send location
     */
    public function sendLocation(Request $request, Recipient $recipient): RedirectResponse
    {
        $this->authorize('view', $recipient);

        $request->validate([
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'wa_session_id' => 'nullable|exists:wa_sessions,id',
        ]);

        try {
            $session = $this->getActiveSession($request);
            $bridge = $this->bridgeManager->getClientForSession($session);

            $response = $bridge->sendLocation(
                $recipient->phone_e164,
                $request->latitude,
                $request->longitude
            );

            $message = Message::create([
                'campaign_id' => null,
                'recipient_id' => $recipient->id,
                'user_id' => $request->user()->id,
                'wa_session_id' => $session->id,
                'phone_e164' => $recipient->phone_e164,
                'body_rendered' => "[Location: {$request->latitude}, {$request->longitude}]",
                'status' => 'sent',
                'sent_at' => now(),
            ]);

            return redirect()->route('contacts.show', $recipient)
                ->with('success', 'Location sent successfully.');

        } catch (Exception $e) {
            return redirect()->route('contacts.show', $recipient)
                ->with('error', 'Failed to send location: ' . $e->getMessage());
        }
    }

    /**
     * Send contact card
     */
    public function sendContact(Request $request, Recipient $recipient): RedirectResponse
    {
        $this->authorize('view', $recipient);

        $request->validate([
            'contact_name' => 'required|string|max:255',
            'contact_phone' => 'required|string|max:50',
            'wa_session_id' => 'nullable|exists:wa_sessions,id',
        ]);

        try {
            $session = $this->getActiveSession($request);
            $bridge = $this->bridgeManager->getClientForSession($session);

            $response = $bridge->sendContact(
                $recipient->phone_e164,
                $request->contact_name,
                $request->contact_phone
            );

            $message = Message::create([
                'campaign_id' => null,
                'recipient_id' => $recipient->id,
                'user_id' => $request->user()->id,
                'wa_session_id' => $session->id,
                'phone_e164' => $recipient->phone_e164,
                'body_rendered' => "[Contact: {$request->contact_name}]",
                'status' => 'sent',
                'sent_at' => now(),
            ]);

            return redirect()->route('contacts.show', $recipient)
                ->with('success', 'Contact sent successfully.');

        } catch (Exception $e) {
            return redirect()->route('contacts.show', $recipient)
                ->with('error', 'Failed to send contact: ' . $e->getMessage());
        }
    }

    /**
     * Send poll
     */
    public function sendPoll(Request $request, Recipient $recipient): RedirectResponse
    {
        $this->authorize('view', $recipient);

        $request->validate([
            'question' => 'required|string|max:255',
            'options' => 'required|array|min:2|max:12',
            'options.*' => 'required|string|max:100',
            'max_answer' => 'nullable|integer|min:1',
            'wa_session_id' => 'nullable|exists:wa_sessions,id',
        ]);

        try {
            $session = $this->getActiveSession($request);
            $bridge = $this->bridgeManager->getClientForSession($session);

            $response = $bridge->sendPoll(
                $recipient->phone_e164,
                $request->question,
                $request->options,
                $request->max_answer ?? 1
            );

            $message = Message::create([
                'campaign_id' => null,
                'recipient_id' => $recipient->id,
                'user_id' => $request->user()->id,
                'wa_session_id' => $session->id,
                'phone_e164' => $recipient->phone_e164,
                'body_rendered' => "[Poll: {$request->question}]",
                'status' => 'sent',
                'sent_at' => now(),
            ]);

            return redirect()->route('contacts.show', $recipient)
                ->with('success', 'Poll sent successfully.');

        } catch (Exception $e) {
            return redirect()->route('contacts.show', $recipient)
                ->with('error', 'Failed to send poll: ' . $e->getMessage());
        }
    }

    /**
     * Delete contact
     */
    public function destroy(Request $request, Recipient $recipient): RedirectResponse
    {
        $this->authorize('delete', $recipient);

        $recipientId = $recipient->id;
        $recipient->delete();

        AuditLog::log('deleted', 'Recipient', $recipientId);

        return redirect()->route('contacts.index')
            ->with('success', 'Contact deleted successfully.');
    }

    /**
     * Get active WhatsApp session for user
     */
    private function getActiveSession(Request $request): WaSession
    {
        // If session ID provided, use that
        if ($request->wa_session_id) {
            $session = WaSession::where('id', $request->wa_session_id)
                ->where('user_id', $request->user()->id)
                ->where('status', 'connected')
                ->firstOrFail();
        } else {
            // Otherwise use primary or first connected session
            $session = WaSession::where('user_id', $request->user()->id)
                ->where('status', 'connected')
                ->orderBy('is_primary', 'desc')
                ->firstOrFail();
        }

        if (!$session) {
            throw new Exception('No active WhatsApp session found. Please connect a device first.');
        }

        return $session;
    }

    /**
     * Track Facebook Contact event
     */
    private function trackFacebookContact(Request $request, Message $message, Recipient $recipient): void
    {
        try {
            $userData = $this->facebookService->buildUserDataFromAuth();

            $customData = [
                'content_name' => 'Individual Message Sent',
                'status' => 'completed',
            ];

            if ($recipient->first_name) {
                $customData['content_category'] = 'Direct Message';
            }

            $this->facebookService->trackContact($userData, $customData);

            Log::info('Facebook Contact event tracked', [
                'user_id' => $request->user()->id,
                'message_id' => $message->id,
            ]);
        } catch (Exception $e) {
            Log::error('Failed to track Facebook Contact event', [
                'user_id' => $request->user()->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
