<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected static ?string $password;

    public function definition(): array
    {
        return [
            'name'                     => fake()->name(),
            'email'                    => fake()->unique()->safeEmail(),
            'email_verified_at'        => now(),
            'password'                 => static::$password ??= Hash::make('password'),
            'remember_token'           => Str::random(10),
            'role'                     => 'patient',
            'status'                   => 'Active',
            'two_factor_secret'        => null,
            'two_factor_recovery_codes'=> null,
            'two_factor_confirmed_at'  => null,
        ];
    }

    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    public function withTwoFactor(): static
    {
        return $this->state(fn (array $attributes) => [
            'two_factor_secret' => encrypt('secret'),
            'two_factor_recovery_codes' => encrypt(json_encode(['recovery-code-1'])),
            'two_factor_confirmed_at' => now(),
        ]);
    }

    public function role(string $role): static
    {
        return $this->state(['role' => $role]);
    }

    public function active(): static
    {
        return $this->state(['status' => 'Active']);
    }

    public function patient(): static
    {
        return $this->state(['role' => 'patient']);
    }

    public function doctor(): static
    {
        return $this->state(['role' => 'doctor']);
    }

    public function receptionist(): static
    {
        return $this->state(['role' => 'receptionist']);
    }

    public function admin(): static
    {
        return $this->state(['role' => 'admin']);
    }
}