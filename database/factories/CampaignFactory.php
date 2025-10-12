<?php

namespace Database\Factories;

use App\Models\Campaign;
use App\Models\Import;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class CampaignFactory extends Factory
{
    protected $model = Campaign::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'import_id' => Import::factory(),
            'name' => fake()->words(3, true) . ' Campaign',
            'message_template' => 'Hello {{first_name}}, this is a test message for {{company}}.',
            'variables_json' => ['first_name', 'company'],
            'status' => fake()->randomElement(['draft', 'running', 'paused', 'finished']),
            'throttling_cfg_json' => [
                'messages_per_minute' => 20,
                'delay_seconds' => 3,
            ],
            'started_at' => null,
            'finished_at' => null,
        ];
    }

    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'draft',
        ]);
    }

    public function running(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'running',
            'started_at' => now(),
        ]);
    }
}
