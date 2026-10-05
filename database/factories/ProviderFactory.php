<?php

namespace Database\Factories;

use App\Models\Provider;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Provider>
 */
class ProviderFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id'         => User::factory()->doctor(),
            'npi'             => fake()->unique()->numerify('############'),
            'license_number'  => 'PRC-' . fake()->unique()->numerify('#######'),
            'specialty'       => fake()->randomElement(['Cardiology', 'Interventional Cardiology', 'Cardiothoracic Surgery']),
            'credentials'     => fake()->randomElement(['MD', 'DO']),
        ];
    }
}