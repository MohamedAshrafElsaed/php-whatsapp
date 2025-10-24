<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

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
