<?php

namespace Database\Factories;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/** @extends Factory<User> */
class UserFactory extends Factory
{
    protected static ?string $password;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => '08'.fake()->numerify('##########'),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => null,
            'role_id' => fn () => Role::firstOrCreate(['slug' => 'customer'], ['name' => 'Customer'])->id,
        ];
    }

    public function role(string $slug, string $name): static
    {
        return $this->state(fn () => ['role_id' => Role::firstOrCreate(['slug' => $slug], ['name' => $name])->id]);
    }

    public function admin(): static
    {
        return $this->role('admin', 'Admin');
    }
}
