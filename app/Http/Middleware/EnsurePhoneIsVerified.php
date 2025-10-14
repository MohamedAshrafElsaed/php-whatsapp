<?php
// app/Http/Middleware/EnsurePhoneIsVerified.php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePhoneIsVerified
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!$request->user() || !$request->user()->phone_verified) {
            return redirect()->route('phone.verification.notice');
        }

        return $next($request);
    }
}
