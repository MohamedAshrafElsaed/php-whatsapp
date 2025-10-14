<?php
// app/Http/Controllers/PhoneVerificationController.php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Otp;
use App\Services\BridgeClient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class PhoneVerificationController extends Controller
{
    public function __construct(
        private BridgeClient $bridgeClient
    ) {}

    /**
     * Show phone verification notice
     */
    public function notice(): Response
    {
        $user = auth()->user();

        return Inertia::render('auth/VerifyPhone', [
            'phone_verified' => $user->phone_verified,
            'country_code' => $user->country_code,
            'phone' => $user->phone,
        ]);
    }

    /**
     * Send verification OTP
     */
    public function send(Request $request): RedirectResponse
    {
        $user = auth()->user();

        Log::info('Phone verification OTP send requested', [
            'user_id' => $user->id,
            'phone' => $user->full_phone,
        ]);

        if ($user->phone_verified) {
            Log::info('Phone already verified', ['user_id' => $user->id]);
            return back()->with('status', 'Phone already verified.');
        }

        try {
            // Generate OTP code
            $otpCode = Otp::generateCode();

            // Store OTP in database
            Otp::create([
                'country_code' => $user->country_code,
                'phone' => $user->phone,
                'otp_code' => $otpCode,
                'type' => 'verify',
                'expires_at' => now()->addMinutes(5),
            ]);

            Log::info('OTP generated and stored', [
                'user_id' => $user->id,
                'otp_code' => $otpCode, // Remove in production
            ]);

            // Get admin user (ID 1)
            $adminUserId = 1;

            // Send OTP via WhatsApp
            $message = "Your verification code is: *{$otpCode}*\n\nThis code will expire in 5 minutes.\n\n_Do not share this code with anyone._";

            $fullPhone = $user->country_code . $user->phone;

            Log::info('Attempting to send OTP via WhatsApp', [
                'user_id' => $user->id,
                'admin_user_id' => $adminUserId,
                'phone' => $fullPhone,
            ]);

            $result = $this->bridgeClient->sendMessage(
                $adminUserId,
                $fullPhone,
                $message
            );

            Log::info('Phone verification OTP sent successfully', [
                'user_id' => $user->id,
                'result' => $result,
            ]);

            return back()->with('status', 'Verification code sent to your WhatsApp.');
        } catch (\Exception $e) {
            Log::error('Failed to send phone verification OTP', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => $user->id,
            ]);

            return back()->withErrors(['phone' => 'Failed to send verification code: ' . $e->getMessage()]);
        }
    }

    /**
     * Verify phone with OTP
     */
    public function verify(Request $request): RedirectResponse
    {
        $request->validate([
            'otp_code' => 'required|string|size:6',
        ]);

        $user = auth()->user();

        Log::info('Phone verification attempt', [
            'user_id' => $user->id,
            'otp_code' => $request->otp_code,
        ]);

        if ($user->phone_verified) {
            return redirect()->route('dashboard')->with('status', 'Phone already verified.');
        }

        // Find valid OTP
        $otp = Otp::where('country_code', $user->country_code)
            ->where('phone', $user->phone)
            ->where('otp_code', $request->otp_code)
            ->where('verified', false)
            ->where('expires_at', '>', now())
            ->first();

        if (!$otp) {
            Log::warning('Invalid or expired OTP attempt', [
                'user_id' => $user->id,
                'otp_code' => $request->otp_code,
            ]);

            return back()->withErrors(['otp_code' => 'Invalid or expired OTP code.']);
        }

        // Mark OTP as verified
        $otp->markAsVerified();

        // Mark phone as verified
        $user->update(['phone_verified' => true]);

        // Log verification
        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'phone_verified',
            'entity' => 'User',
            'entity_id' => $user->id,
            'meta_json' => [
                'country_code' => $user->country_code,
                'phone' => $user->phone,
                'ip' => $request->ip(),
            ],
        ]);

        Log::info('Phone verified successfully', [
            'user_id' => $user->id,
        ]);

        return redirect()->route('dashboard')->with('status', 'Phone verified successfully!');
    }
}
