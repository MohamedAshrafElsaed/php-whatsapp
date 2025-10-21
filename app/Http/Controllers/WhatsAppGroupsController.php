<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class WhatsAppGroupsController extends Controller
{
    /**
     * Display WhatsApp Groups page (Coming Soon)
     */
    public function index(Request $request): Response
    {
        return Inertia::render('whatsapp/Groups');
    }
}

class AutoReplyController extends Controller
{
    /**
     * Display Auto Reply page (Coming Soon)
     */
    public function index(Request $request): Response
    {
        return Inertia::render('whatsapp/AutoReply');
    }
}

class ReportsController extends Controller
{
    /**
     * Display Reports page (Coming Soon)
     */
    public function index(Request $request): Response
    {
        return Inertia::render('reports/Index');
    }
}
