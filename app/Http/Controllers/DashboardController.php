<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Models\Import;
use App\Models\Message;
use App\Models\Recipient;
use App\Models\WaSession;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * Show the dashboard with statistics
     */
    public function index(Request $request): Response
    {
        $user = $request->user();

        // Get statistics
        $stats = [
            'total_contacts' => Recipient::where('user_id', $user->id)
                ->where('is_valid', true)
                ->count(),

            'total_imports' => Import::where('user_id', $user->id)
                ->where('status', 'ready')
                ->count(),

            'total_campaigns' => Campaign::where('user_id', $user->id)->count(),

            'messages_sent' => Message::where('user_id', $user->id)
                ->where('status', 'sent')
                ->count(),

            'messages_failed' => Message::where('user_id', $user->id)
                ->where('status', 'failed')
                ->count(),

            'messages_queued' => Message::where('user_id', $user->id)
                ->where('status', 'queued')
                ->count(),

            'whatsapp_connected' => WaSession::where('user_id', $user->id)
                ->where('status', 'connected')
                ->exists(),
        ];

        // Get recent imports
        $recentImports = Import::where('user_id', $user->id)
            ->latest()
            ->limit(5)
            ->get();

        // Get recent campaigns
        $recentCampaigns = Campaign::where('user_id', $user->id)
            ->latest()
            ->limit(5)
            ->get();

        return Inertia::render('Dashboard', [
            'stats' => $stats,
            'recentImports' => $recentImports,
            'recentCampaigns' => $recentCampaigns,
        ]);
    }
}
