<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;

class WhatsAppSettingsController extends Controller
{
    /**
     * Show WhatsApp settings page
     */
    public function index()
    {
        return Inertia::render('WhatsApp/Settings', [
            'settings' => [
                'auto_reply_enabled' => auth()->user()->wa_auto_reply_enabled,
                'auto_reply_message' => auth()->user()->wa_auto_reply_message,
                'auto_mark_read' => auth()->user()->wa_auto_mark_read,
            ],
        ]);
    }

    /**
     * Update WhatsApp settings
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            'auto_reply_enabled' => 'required|boolean',
            'auto_reply_message' => 'nullable|string|max:1000',
            'auto_mark_read' => 'required|boolean',
        ]);

        auth()->user()->update([
            'wa_auto_reply_enabled' => $validated['auto_reply_enabled'],
            'wa_auto_reply_message' => $validated['auto_reply_message'],
            'wa_auto_mark_read' => $validated['auto_mark_read'],
        ]);

        return back()->with('success', 'Settings updated successfully');
    }
}
