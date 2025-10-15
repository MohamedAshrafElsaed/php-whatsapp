<?php
// app/Http/Controllers/Auth/AuthenticatedSessionController.php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use App\Models\UserDevice;
use App\Services\FacebookConversionsApiService;
use App\Services\PhoneValidator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class AuthenticatedSessionController extends Controller
{
    public function __construct(
        private readonly PhoneValidator $phoneValidator,
        private readonly FacebookConversionsApiService $facebookService
    ) {}

    /**
     * Show the login page.
     */
    public function create(Request $request): Response
    {
        return Inertia::render('auth/Login', [
            'status' => $request->session()->get('status'),
        ]);
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'country_code' => 'required|string|max:5',
            'phone' => 'required|string|max:20',
            'password' => 'required|string',
        ]);

        // Validate and normalize phone number
        try {
            $phoneData = $this->phoneValidator->validateAndNormalize(
                $request->country_code,
                $request->phone
            );
        } catch (\InvalidArgumentException $e) {
            throw ValidationException::withMessages([
                'phone' => $e->getMessage(),
            ]);
        }

        // Check if user exists
        $user = User::where('country_code', $phoneData['country_code'])
            ->where('phone', $phoneData['phone'])
            ->first();

        if (!$user) {
            Log::warning('Login attempt with non-existent phone', [
                'country_code' => $phoneData['country_code'],
                'phone' => $phoneData['phone'],
                'ip' => $request->ip(),
            ]);

            throw ValidationException::withMessages([
                'phone' => 'No account found with this phone number.',
            ]);
        }

        // Check password
        if (!Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'password' => 'The provided password is incorrect.',
            ]);
        }

        // Login with password
        Auth::login($user);
        UserDevice::recordDevice($user->id);

        // Log login
        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'user_logged_in_password',
            'entity' => 'User',
            'entity_id' => $user->id,
            'meta_json' => [
                'method' => 'password',
                'ip' => $request->ip(),
            ],
        ]);

        // Track Lead event on Facebook (user logged in)
        try {
            $userData = [
                'email' => $user->email,
                'phone' => $user->full_phone,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'external_id' => (string) $user->id,
            ];

            $customData = [
                'content_name' => 'User Login',
                'status' => 'completed',
            ];

            $this->facebookService->trackLead($userData, $customData);

            Log::info('Facebook Lead event tracked for login', [
                'user_id' => $user->id,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to track Facebook Lead event', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }

        $request->session()->regenerate();

        Log::info('Password login successful', [
            'user_id' => $user->id,
        ]);

        return redirect()->intended(route('dashboard', absolute: false));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $userId = Auth::id();

        // Record device last used
        if ($userId) {
            $fingerprint = UserDevice::generateFingerprint();
            $device = UserDevice::where('device_fingerprint', $fingerprint)
                ->where('user_id', $userId)
                ->first();

            if ($device) {
                $device->updateLastUsed();
            }

            // Log logout
            AuditLog::create([
                'user_id' => $userId,
                'action' => 'user_logged_out',
                'entity' => 'User',
                'entity_id' => $userId,
                'meta_json' => [
                    'ip' => $request->ip(),
                    'device_fingerprint' => $fingerprint,
                ],
            ]);
        }

        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
