<?php

namespace App\Http\Controllers;

use App\Models\FeatureRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class FeatureRequestController extends Controller
{
    /**
     * Show all feature requests for current user
     */
    public function index(Request $request): Response
    {
        $requests = FeatureRequest::where('user_id', $request->user()->id)
            ->latest()
            ->paginate(20);

        return Inertia::render('featurerequests/Index', [
            'requests' => $requests,
        ]);
    }

    /**
     * Show form to create new feature request
     */
    public function create(): Response
    {
        return Inertia::render('featurerequests/Create');
    }

    /**
     * Store new feature request
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string|max:5000',
        ]);

        FeatureRequest::create([
            'user_id' => $request->user()->id,
            'title' => $validated['title'],
            'description' => $validated['description'],
            'status' => 'pending',
        ]);

        return redirect()->route('feature-requests.index')
            ->with('success', 'Feature request submitted successfully! We will review it soon.');
    }

    /**
     * Show single feature request
     */
    public function show(Request $request, FeatureRequest $featureRequest): Response
    {
        // Ensure user can only view their own requests
        if ($featureRequest->user_id !== $request->user()->id) {
            abort(403);
        }

        return Inertia::render('featurerequests/Show', [
            'request' => $featureRequest->load('user'),
        ]);
    }
}
