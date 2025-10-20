<?php

namespace App\Http\Controllers;

use App\Models\Segment;
use App\Models\Recipient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class SegmentController extends Controller
{
    /**
     * Display segments list
     */
    public function index(Request $request)
    {
        $segments = Segment::where('user_id', $request->user()->id)
            ->withCount(['recipients', 'campaigns'])
            ->orderBy('created_at', 'desc')
            ->paginate(20)
            ->through(fn($segment) => [
                'id' => $segment->id,
                'name' => $segment->name,
                'description' => $segment->description,
                'total_contacts' => $segment->total_contacts,
                'valid_contacts' => $segment->valid_contacts,
                'invalid_contacts' => $segment->invalid_contacts,
                'campaigns_count' => $segment->campaigns_count,
                'created_at' => $segment->created_at->format('M d, Y'),
            ]);

        return Inertia::render('contacts/segments/Index', [
            'segments' => $segments,
        ]);
    }

    /**
     * Show create segment form
     */
    public function create(Request $request)
    {
        // Get user's contacts
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

        return Inertia::render('contacts/segments/Create', [
            'contacts' => $contacts,
        ]);
    }

    /**
     * Store new segment
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'recipient_ids' => 'required|array|min:1',
            'recipient_ids.*' => 'exists:recipients,id',
        ]);

        // Verify recipients belong to user
        $recipientIds = Recipient::whereIn('id', $validated['recipient_ids'])
            ->where('user_id', $request->user()->id)
            ->pluck('id')
            ->toArray();

        if (count($recipientIds) === 0) {
            return back()->with('error', 'No valid recipients selected.');
        }

        DB::beginTransaction();
        try {
            // Create segment
            $segment = Segment::create([
                'user_id' => $request->user()->id,
                'name' => $validated['name'],
                'description' => $validated['description'],
            ]);

            // Attach recipients
            $segment->recipients()->attach($recipientIds);

            // Update counts
            $segment->updateCounts();

            DB::commit();

            Log::info('Segment created', [
                'segment_id' => $segment->id,
                'user_id' => $request->user()->id,
                'total_contacts' => count($recipientIds),
            ]);

            return redirect()->route('segments.show', $segment)
                ->with('success', 'Segment created successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create segment', [
                'error' => $e->getMessage(),
                'user_id' => $request->user()->id,
            ]);

            return back()->with('error', 'Failed to create segment: ' . $e->getMessage());
        }
    }

    /**
     * Show segment details with statistics
     */
    public function show(Request $request, Segment $segment)
    {
        $this->authorize('view', $segment);

        $segment->load(['recipients' => function ($query) {
            $query->orderBy('first_name')->orderBy('last_name')->limit(100);
        }, 'campaigns' => function ($query) {
            $query->latest()->limit(10);
        }]);

        // Get statistics
        $statistics = $segment->getStatistics();

        // Format contacts
        $contacts = $segment->recipients->map(fn($recipient) => [
            'id' => $recipient->id,
            'full_name' => trim($recipient->first_name . ' ' . $recipient->last_name),
            'phone_e164' => $recipient->phone_e164,
            'email' => $recipient->email,
            'is_valid' => $recipient->is_valid,
        ]);

        // Format campaigns
        $campaigns = $segment->campaigns->map(fn($campaign) => [
            'id' => $campaign->id,
            'name' => $campaign->name,
            'status' => $campaign->status,
            'sent_count' => $campaign->sent_count,
            'failed_count' => $campaign->failed_count,
            'total_recipients' => $campaign->total_recipients,
            'created_at' => $campaign->created_at->format('M d, Y'),
        ]);

        return Inertia::render('contacts/segments/Show', [
            'segment' => [
                'id' => $segment->id,
                'name' => $segment->name,
                'description' => $segment->description,
                'total_contacts' => $segment->total_contacts,
                'valid_contacts' => $segment->valid_contacts,
                'invalid_contacts' => $segment->invalid_contacts,
                'created_at' => $segment->created_at->format('M d, Y'),
            ],
            'statistics' => $statistics,
            'contacts' => $contacts,
            'campaigns' => $campaigns,
        ]);
    }

    /**
     * Show edit segment form
     */
    public function edit(Request $request, Segment $segment)
    {
        $this->authorize('update', $segment);

        $segment->load('recipients');

        // Get all user's contacts
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

        // Get selected recipient IDs
        $selectedRecipientIds = $segment->recipients->pluck('id')->toArray();

        return Inertia::render('contacts/segments/Edit', [
            'segment' => [
                'id' => $segment->id,
                'name' => $segment->name,
                'description' => $segment->description,
                'recipient_ids' => $selectedRecipientIds,
            ],
            'contacts' => $contacts,
        ]);
    }

    /**
     * Update segment
     */
    public function update(Request $request, Segment $segment)
    {
        $this->authorize('update', $segment);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'recipient_ids' => 'required|array|min:1',
            'recipient_ids.*' => 'exists:recipients,id',
        ]);

        // Verify recipients belong to user
        $recipientIds = Recipient::whereIn('id', $validated['recipient_ids'])
            ->where('user_id', $request->user()->id)
            ->pluck('id')
            ->toArray();

        if (count($recipientIds) === 0) {
            return back()->with('error', 'No valid recipients selected.');
        }

        DB::beginTransaction();
        try {
            // Update segment
            $segment->update([
                'name' => $validated['name'],
                'description' => $validated['description'],
            ]);

            // Sync recipients
            $segment->recipients()->sync($recipientIds);

            // Update counts
            $segment->updateCounts();

            DB::commit();

            Log::info('Segment updated', [
                'segment_id' => $segment->id,
                'user_id' => $request->user()->id,
                'total_contacts' => count($recipientIds),
            ]);

            return redirect()->route('segments.show', $segment)
                ->with('success', 'Segment updated successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update segment', [
                'error' => $e->getMessage(),
                'segment_id' => $segment->id,
            ]);

            return back()->with('error', 'Failed to update segment: ' . $e->getMessage());
        }
    }

    /**
     * Delete segment
     */
    public function destroy(Request $request, Segment $segment)
    {
        $this->authorize('delete', $segment);

        // Check if segment is used in active campaigns
        $activeCampaigns = $segment->campaigns()
            ->whereIn('status', ['running', 'paused'])
            ->count();

        if ($activeCampaigns > 0) {
            return back()->with('error', "Cannot delete segment. It is used in {$activeCampaigns} active campaign(s).");
        }

        $segmentId = $segment->id;
        $segment->delete();

        Log::info('Segment deleted', [
            'segment_id' => $segmentId,
            'user_id' => $request->user()->id,
        ]);

        return redirect()->route('segments.index')
            ->with('success', 'Segment deleted successfully.');
    }
}
