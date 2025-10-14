<?php

namespace Database\Factories;

use App\Models\Import;
use App\Models\Recipient;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class RecipientFactory extends Factory
{
    protected $model = Recipient::class;

    public function definition(): array
    {
        $phoneE164 = fake()->e164PhoneNumber();

        return [
            'import_id' => Import::factory(),
            'user_id' => User::factory(),
            'phone_raw' => $phoneE164,
            'phone_e164' => $phoneE164,
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'email' => fake()->safeEmail(),
            'extra_json' => [
                'company' => fake()->company(),
                'city' => fake()->city(),
            ],
            'is_valid' => true,
            'validation_errors_json' => null,
        ];
    }

    public function invalid(): static
    {
        return $this->state(fn(array $attributes) => [
            'phone_e164' => null,
            'is_valid' => false,
            'validation_errors_json' => ['phone' => 'Invalid phone number format'],
        ]);
    }
}
