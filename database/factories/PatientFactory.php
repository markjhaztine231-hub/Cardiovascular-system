<?php

namespace Database\Factories;

use App\Models\Patient;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Patient>
 */
class PatientFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id'     => User::factory()->patient(),
            'mrn'         => 'MRN-' . fake()->unique()->numerify('#####'),
            'date_of_birth' => fake()->dateTimeBetween('-80 years', '-18 years')->format('Y-m-d'),
            'sex'         => fake()->randomElement(['Male', 'Female']),
            'blood_type'  => fake()->randomElement(['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-']),
            'contact_phone' => fake()->phoneNumber(),
        ];
    }
}