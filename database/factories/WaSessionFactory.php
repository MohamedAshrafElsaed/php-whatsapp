<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\WaSession;
use Illuminate\Database\Eloquent\Factories\Factory;

class WaSessionFactory extends Factory
{
    protected $model = WaSession::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'status' => fake()->randomElement(['pending', 'connected', 'expired', 'disconnected']),
            'meta_json' => [
                'phone' => fake()->e164PhoneNumber(),
                'name' => fake()->name(),
                'avatar' => fake()->imageUrl(200, 200, 'people'),
            ],
            'last_seen_at' => fake()->dateTimeBetween('-1 hour', 'now'),
            'expires_at' => fake()->dateTimeBetween('now', '+1 day'),
        ];
    }

    public function connected(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => 'connected',
            'last_seen_at' => now(),
            'expires_at' => now()->addDays(30),
        ]);
    }

    public function pending(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => 'pending',
            'meta_json' => [
                'qr_base64' => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==',
            ],
            'expires_at' => now()->addMinutes(5),
        ]);
    }
}
