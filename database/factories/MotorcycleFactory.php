<?php

namespace Database\Factories;

use App\Models\Motorcycle;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Motorcycle> */
class MotorcycleFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->unique()->words(2, true).' '.fake()->numberBetween(100, 250);

        return [
            'name' => ucwords($name),
            'slug' => Str::slug($name).'-'.Str::random(4),
            'brand' => fake()->randomElement(['Honda', 'Kawasaki', 'Yamaha']),
            'model' => 'Trail',
            'plate_number' => strtoupper(fake()->bothify('F #### ??')),
            'year' => 2024,
            'cc' => 150,
            'transmission' => 'Manual',
            'capacity' => 1,
            'color' => 'Merah',
            'price_daily' => 250000,
            'price_weekly' => 1500000,
            'price_monthly' => 5500000,
            'deposit' => 500000,
            'facilities' => ['Helm', 'Jas hujan'],
            'status' => 'available',
        ];
    }
}
