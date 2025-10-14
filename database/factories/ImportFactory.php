<?php

namespace Database\Factories;

use App\Models\Import;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ImportFactory extends Factory
{
    protected $model = Import::class;

    public function definition(): array
    {
        $total = fake()->numberBetween(50, 500);
        $valid = fake()->numberBetween(40, $total);
        $invalid = $total - $valid;

        return [
            'user_id' => User::factory(),
            'filename' => fake()->word() . '_contacts_' . now()->format('YmdHis') . '.xlsx',
            'total_rows' => $total,
            'valid_rows' => $valid,
            'invalid_rows' => $invalid,
            'status' => fake()->randomElement(['pending', 'validated', 'ready']),
        ];
    }

    public function ready(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => 'ready',
            'valid_rows' => fake()->numberBetween(50, 100),
        ]);
    }
}
