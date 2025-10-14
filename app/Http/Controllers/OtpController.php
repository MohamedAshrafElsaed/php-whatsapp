<?php
// app/Http/Controllers/OtpController.php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Otp;
use App\Models\User;
use App\Models\UserDevice;
use App\Services\BridgeClient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class OtpController extends Controller
{
    public function __construct(
        private BridgeClient $bridgeClient
    ) {}

    /**
     * Show OTP verification page
     */
    public function create(Request $request): Response|RedirectResponse
    {
        $countryCode = $request->session()->get('otp_country_code');
        $phone = $request->session()->get('otp_phone');
        $type = $request->session()->get('otp_type', 'login');

        if (!$countryCode || !$phone) {
            return redirect()->route('login');
        }

        return Inertia::render('auth/VerifyOtp', [
            'country_code' => $countryCode,
            'phone' => $phone,
            'type' => $type,
        ]);
    }

    /**
     * Verify OTP code
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'otp_code' => 'required|string|size:6',
        ]);

        $countryCode = $request->session()->get('otp_country_code');
        $phone = $request->session()->get('otp_phone');
        $type = $request->session()->get('otp_type');
        $userId = $request->session()->get('otp_user_id');

        if (!$countryCode || !$phone) {
            return back()->withErrors(['otp_code' => 'Session expired. Please try again.']);
        }

        // Find valid OTP
        $otp = Otp::where('country_code', $countryCode)
            ->where('phone', $phone)
            ->where('otp_code', $request->otp_code)
            ->where('verified', false)
            ->where('expires_at', '>', now())
            ->first();

        if (!$otp) {
            // Log failed OTP attempt
            Log::warning('Failed OTP verification attempt', [
                'country_code' => $countryCode,
                'phone' => $phone,
                'ip' => $request->ip(),
            ]);

            return back()->withErrors(['otp_code' => 'Invalid or expired OTP code.']);
        }

        // Mark OTP as verified
        $otp->markAsVerified();

        // Get user
        $user = User::where('country_code', $countryCode)
            ->where('phone', $phone)
            ->first();

        if (!$user) {
            return back()->withErrors(['otp_code' => 'User not found.']);
        }

        // Mark phone as verified
        if (!$user->phone_verified) {
            $user->update(['phone_verified' => true]);
        }

        Auth::login($user);

        // Record device
        $device = UserDevice::recordDevice($user->id);

        // Log successful verification
        AuditLog::create([
            'user_id' => $user->id,
            'action' => $type === 'register' ? 'user_registered_verified' : 'user_logged_in_otp',
            'entity' => 'User',
            'entity_id' => $user->id,
            'meta_json' => [
                'method' => 'otp',
                'type' => $type,
                'country_code' => $countryCode,
                'phone' => $phone,
                'device_fingerprint' => $device->device_fingerprint,
                'ip' => $request->ip(),
            ],
        ]);

        $request->session()->regenerate();
        $request->session()->forget(['otp_country_code', 'otp_phone', 'otp_type', 'otp_user_id']);

        return redirect()->intended(route('dashboard', absolute: false));
    }

    /**
     * Resend OTP code
     */
    public function resend(Request $request): RedirectResponse
    {
        $countryCode = $request->session()->get('otp_country_code');
        $phone = $request->session()->get('otp_phone');
        $type = $request->session()->get('otp_type', 'login');

        if (!$countryCode || !$phone) {
            return back()->withErrors(['otp_code' => 'Session expired. Please try again.']);
        }

        try {
            // Get user to build full phone
            $user = User::where('country_code', $countryCode)
                ->where('phone', $phone)
                ->first();

            if (!$user) {
                return back()->withErrors(['otp_code' => 'User not found.']);
            }

            $this->sendOtp($countryCode, $phone, $user->full_phone, $type);

            return back()->with('status', 'OTP code resent successfully.');
        } catch (\Exception $e) {
            Log::error('Failed to resend OTP', [
                'error' => $e->getMessage(),
                'country_code' => $countryCode,
                'phone' => $phone,
            ]);

            return back()->withErrors(['otp_code' => 'Failed to send OTP. Please try again.']);
        }
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

        // Send OTP via WhatsApp
        $message = "Your verification code is: *{$otpCode}*\n\nThis code will expire in 5 minutes.\n\n_Do not share this code with anyone._";

        $this->bridgeClient->sendMessage(
            $adminUserId,
            $fullPhone,
            $message
        );

        Log::info('OTP sent successfully', [
            'country_code' => $countryCode,
            'phone' => $phone,
            'type' => $type,
        ]);
    }
}
