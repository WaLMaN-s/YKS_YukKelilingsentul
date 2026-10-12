<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Customer> */
class CustomerFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'nik' => fake()->numerify('################'),
            'address' => fake()->address(),
            'birth_date' => fake()->dateTimeBetween('-45 years', '-20 years')->format('Y-m-d'),
            'sim_number' => fake()->numerify('############'),
        ];
    }
}
