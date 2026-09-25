<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class DiscountFactory extends Factory
{
    public function definition(): array
    {
        $type = fake()->randomElement(['percentuale', 'fisso']);

        return [
            'code' => strtoupper(fake()->unique()->bothify('FL##??')),
            'description' => fake()->sentence(6),
            'type' => $type,
            'value' => $type === 'percentuale'
                ? fake()->randomElement([5, 10, 15, 20])
                : fake()->randomElement([5, 10, 15]),
            'starts_at' => now()->subDays(fake()->numberBetween(0, 10)),
            'ends_at' => now()->addDays(fake()->numberBetween(5, 30)),
            'is_active' => true,
        ];
    }
}
