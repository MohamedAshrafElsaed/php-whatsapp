<?php

namespace Database\Factories;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class AuditLogFactory extends Factory
{
    protected $model = AuditLog::class;

    public function definition(): array
    {
        $entities = ['WaSession', 'Import', 'Campaign', 'Message'];
        $actions = ['created', 'updated', 'deleted', 'connected', 'disconnected', 'sent'];

        return [
            'user_id' => User::factory(),
            'action' => fake()->randomElement($actions),
            'entity' => fake()->randomElement($entities),
            'entity_id' => fake()->numberBetween(1, 100),
            'meta_json' => [
                'ip' => fake()->ipv4(),
                'user_agent' => fake()->userAgent(),
            ],
        ];
    }
}
