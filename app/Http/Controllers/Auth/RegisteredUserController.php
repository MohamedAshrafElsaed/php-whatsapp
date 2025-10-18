<?php
// app/Http/Controllers/Auth/RegisteredUserController.php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use App\Models\UserDevice;
use App\Services\FacebookConversionsApiService;
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
        private readonly PhoneValidator $phoneValidator,
        private readonly FacebookConversionsApiService $facebookService
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
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255|unique:users,email',
            'industry' => 'nullable|string|max:255',
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

        // Create user
        $user = User::create([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'email' => $request->email,
            'industry' => $request->industry,
            'country_code' => $phoneData['country_code'],
            'phone' => $phoneData['phone'],
            'password' => Hash::make($request->password),
            'phone_verified' => true,
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
                'email' => $request->email,
                'industry' => $request->industry,
                'ip' => $request->ip(),
            ],
        ]);

        // Track CompleteRegistration event on Facebook
        try {
            $userData = [
                'email' => $user->email,
                'phone' => $user->full_phone,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'external_id' => (string) $user->id,
            ];

            $customData = [
                'content_name' => 'User Registration',
                'status' => 'completed',
            ];

            if ($user->industry) {
                $customData['content_category'] = $user->industry;
            }

            $this->facebookService->trackRegistration($userData, $customData);

            Log::info('Facebook CompleteRegistration event tracked', [
                'user_id' => $user->id,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to track Facebook CompleteRegistration event', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }

        // Login user directly
        Auth::login($user);
        UserDevice::recordDevice($user->id);
        $request->session()->regenerate();

        Log::info('User registered and logged in', [
            'user_id' => $user->id,
        ]);

        return redirect()->route('dashboard');
    }
}
