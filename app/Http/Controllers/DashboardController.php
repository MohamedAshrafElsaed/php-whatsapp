<?php
// app/Http/Controllers/DashboardController.php

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Models\Import;
use App\Models\Message;
use App\Models\Recipient;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        // Get statistics
        $stats = [
            'total_imports' => Import::where('user_id', $user->id)->count(),
            'total_contacts' => Recipient::where('user_id', $user->id)->where('is_valid', true)->count(),
            'total_campaigns' => Campaign::where('user_id', $user->id)->count(),
            'messages_sent' => Message::where('user_id', $user->id)->where('status', 'sent')->count(),
        ];

        // Get recent imports
        $recentImports = Import::where('user_id', $user->id)
            ->latest()
            ->take(5)
            ->get();

        // Get recent campaigns
        $recentCampaigns = Campaign::where('user_id', $user->id)
            ->latest()
            ->take(5)
            ->get();

        // Check WhatsApp connection
        $hasWhatsApp = $user->waSession && $user->waSession->isConnected();

        return Inertia::render('Dashboard', [
            'stats' => $stats,
            'recentImports' => $recentImports,
            'recentCampaigns' => $recentCampaigns,
            'hasWhatsApp' => $hasWhatsApp,
            'phoneVerified' => $user->phone_verified,
            'userPhone' => [
                'country_code' => $user->country_code,
                'phone' => $user->phone,
                'full_phone' => $user->full_phone,
            ],
        ]);
    }
}
