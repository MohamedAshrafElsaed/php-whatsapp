<?php

namespace Database\Factories;

use App\Models\Campaign;
use App\Models\Message;
use App\Models\Recipient;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class MessageFactory extends Factory
{
    protected $model = Message::class;

    public function definition(): array
    {
        return [
            'campaign_id' => Campaign::factory(),
            'recipient_id' => Recipient::factory(),
            'user_id' => User::factory(),
            'phone_e164' => fake()->e164PhoneNumber(),
            'body_rendered' => fake()->paragraph(),
            'status' => fake()->randomElement(['queued', 'sent', 'failed']),
            'error_code' => null,
            'error_message' => null,
            'sent_at' => null,
        ];
    }

    public function sent(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'sent',
            'sent_at' => now(),
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'failed',
            'error_code' => 'INVALID_PHONE',
            'error_message' => 'Invalid phone number',
        ]);
    }
}
