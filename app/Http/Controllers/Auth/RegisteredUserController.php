<?php
// app/Http/Controllers/Auth/RegisteredUserController.php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Otp;
use App\Models\User;
use App\Services\BridgeClient;
use App\Services\PhoneValidator;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    public function __construct(
        private readonly BridgeClient $bridgeClient,
        private readonly PhoneValidator $phoneValidator
    ) {}

    /**
     * Show the registration page.
     */
    public function create(): Response
    {
        return Inertia::render('auth/Register');
    }

    /**
     * Handle an incoming registration request.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'country_code' => 'required|string|max:5',
            'phone' => 'required|string|max:20',
            'password' => ['required', 'confirmed', Password::defaults()],
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

        // Check if phone already exists
        $existingUser = User::where('country_code', $phoneData['country_code'])
            ->where('phone', $phoneData['phone'])
            ->first();

        if ($existingUser) {
            Log::warning('Registration attempt with existing phone', [
                'country_code' => $phoneData['country_code'],
                'phone' => $phoneData['phone'],
                'ip' => $request->ip(),
            ]);

            throw ValidationException::withMessages([
                'phone' => 'This phone number is already registered.',
            ]);
        }

        // Create user with password
        $user = User::create([
            'name' => $request->name,
            'country_code' => $phoneData['country_code'],
            'phone' => $phoneData['phone'],
            'password' => Hash::make($request->password),
            'phone_verified' => false, // Not verified yet
        ]);

        event(new Registered($user));

        // Log registration
        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'user_registered',
            'entity' => 'User',
            'entity_id' => $user->id,
            'meta_json' => [
                'country_code' => $phoneData['country_code'],
                'phone' => $phoneData['phone'],
                'ip' => $request->ip(),
            ],
        ]);

        // Try to send OTP for verification
        try {
            $this->sendOtp(
                $phoneData['country_code'],
                $phoneData['phone'],
                $phoneData['full_phone'],
                'register'
            );

            // Store data in session for verification
            $request->session()->put([
                'otp_country_code' => $phoneData['country_code'],
                'otp_phone' => $phoneData['phone'],
                'otp_type' => 'register',
                'otp_user_id' => $user->id,
            ]);

            Log::info('Registration successful, OTP sent', [
                'user_id' => $user->id,
                'country_code' => $phoneData['country_code'],
                'phone' => $phoneData['phone'],
            ]);

            return redirect()->route('otp.verify');
        } catch (\Exception $e) {
            Log::error('Failed to send registration OTP', [
                'error' => $e->getMessage(),
                'user_id' => $user->id,
                'country_code' => $phoneData['country_code'],
                'phone' => $phoneData['phone'],
            ]);

            // Login user without phone verification
            Auth::login($user);
            $request->session()->regenerate();

            return redirect()->route('dashboard')->with('warning',
                'Registration successful! However, we could not send verification code. Please verify your phone in settings.'
            );
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

        // Send OTP via WhatsApp using full E.164 format
        $message = "Your verification code is: *{$otpCode}*\n\nThis code will expire in 5 minutes.\n\n_Do not share this code with anyone._";

        $this->bridgeClient->sendMessage(
            $adminUserId,
            $fullPhone,
            $message
        );
    }
}
