<?php
// app/Http/Controllers/Auth/AuthenticatedSessionController.php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Otp;
use App\Models\User;
use App\Models\UserDevice;
use App\Services\BridgeClient;
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
        private readonly BridgeClient $bridgeClient,
        private readonly PhoneValidator $phoneValidator
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
            'password' => 'nullable|string', // Password is optional
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

        // If password provided, use password authentication
        if ($request->filled('password')) {
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

            $request->session()->regenerate();

            Log::info('Password login successful', [
                'user_id' => $user->id,
            ]);

            return redirect()->intended(route('dashboard', absolute: false));
        }

        // Try OTP authentication
        try {
            // Send OTP
            $this->sendOtp(
                $phoneData['country_code'],
                $phoneData['phone'],
                $phoneData['full_phone'],
                'login'
            );

            // Store data in session for verification
            $request->session()->put([
                'otp_country_code' => $phoneData['country_code'],
                'otp_phone' => $phoneData['phone'],
                'otp_type' => 'login',
                'otp_user_id' => $user->id,
            ]);

            Log::info('Login OTP requested', [
                'user_id' => $user->id,
                'country_code' => $phoneData['country_code'],
                'phone' => $phoneData['phone'],
            ]);

            return redirect()->route('otp.verify');
        } catch (\Exception $e) {
            Log::error('Failed to send login OTP', [
                'error' => $e->getMessage(),
                'user_id' => $user->id,
                'country_code' => $phoneData['country_code'],
                'phone' => $phoneData['phone'],
            ]);

            // Redirect back with option to use password
            return back()->with('otp_failed', true)->withInput($request->only('country_code', 'phone'));
        }
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

    /**
     * Send OTP via WhatsApp
     */
    private function sendOtp(string $countryCode, string $phone, string $fullPhone, string $type): void
    {
        // Generate OTP code
        $otpCode = Otp::generateCode();

        // Store OTP in database
        Otp::create([
            'country_code' => $countryCode,
            'phone' => $phone,
            'otp_code' => $otpCode,
            'type' => $type,
            'expires_at' => now()->addMinutes(5),
        ]);

        // Get admin user (ID 1)
        $adminUserId = 1;

        // Send OTP via WhatsApp using full E.164 format
        $message = "Your verification code is: *{$otpCode}*\n\nThis code will expire in 5 minutes.\n\n_Do not share this code with anyone._";

        $this->bridgeClient->sendMessage(
            $adminUserId,
            $fullPhone,
            $message
        );
    }
}
