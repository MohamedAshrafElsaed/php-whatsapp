<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check for locale in query parameter
        if ($request->has('lang')) {
            $locale = $request->get('lang');
            if (in_array($locale, ['en', 'ar'])) {
                Session::put('locale', $locale);
                App::setLocale($locale);
                return $next($request);
            }
        }

        // Check for locale in session
        if (Session::has('locale')) {
            $locale = Session::get('locale');
            if (in_array($locale, ['en', 'ar'])) {
                App::setLocale($locale);
                return $next($request);
            }
        }

        // Check for locale in user preferences (if authenticated)
        if (auth()->check() && auth()->user()->locale) {
            $locale = auth()->user()->locale;
            if (in_array($locale, ['en', 'ar'])) {
                Session::put('locale', $locale);
                App::setLocale($locale);
                return $next($request);
            }
        }

        // Default to Arabic
        App::setLocale('ar');
        Session::put('locale', 'ar');

        return $next($request);
    }
}
