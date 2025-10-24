<?php

namespace App\Http\Controllers;


use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

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
