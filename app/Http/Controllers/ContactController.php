<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Message;
use App\Models\Recipient;
use App\Services\BridgeClient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumberUtil;

class ContactController extends Controller
{
    public function __construct(private BridgeClient $bridge)
    {
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
     * Show contact creation form
     */
    public function create(): Response
    {
        return Inertia::render('contacts/Create');
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
                $phoneE164 = $phoneUtil->format($phoneNumber, \libphonenumber\PhoneNumberFormat::E164);
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
            'import_id' => null, // Manual entry, no import
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
     * Show contact details and message history
     */
    public function show(Request $request, Recipient $recipient): Response
    {
        $this->authorize('view', $recipient);

        $recipient->load('import');

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
        ]);
    }

    /**
     * Send individual message to contact
     */
    public function sendMessage(Request $request, Recipient $recipient): RedirectResponse
    {
        $this->authorize('view', $recipient);

        $request->validate([
            'message' => 'required|string|max:4096',
        ]);

        try {
            // Create message record
            $message = Message::create([
                'campaign_id' => null, // Individual message, not part of campaign
                'recipient_id' => $recipient->id,
                'user_id' => $request->user()->id,
                'phone_e164' => $recipient->phone_e164,
                'body_rendered' => $request->message,
                'status' => 'queued',
            ]);

            // Send message immediately via bridge
            $response = $this->bridge->sendMessage(
                $request->user()->id,
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
            ]);

            return redirect()->route('contacts.show', $recipient)
                ->with('success', 'Message sent successfully.');

        } catch (\Exception $e) {
            // Mark as failed if exists
            if (isset($message)) {
                $message->update([
                    'status' => 'failed',
                    'error_code' => 'SEND_ERROR',
                    'error_message' => substr($e->getMessage(), 0, 500),
                ]);
            }

            return redirect()->route('contacts.show', $recipient)
                ->with('error', 'Failed to send message: ' . $e->getMessage());
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
}
