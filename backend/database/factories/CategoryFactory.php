<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class CategoryFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->unique()->randomElement([
            'Trucco viso', 'Trucco occhi', 'Trucco labbra', 'Skincare',
            'Profumeria', 'Capelli', 'Corpo e bagno', 'Unghie',
            'Accessori beauty', 'Make-up set',
        ]);

        return [
            'name' => $name,
            'description' => fake()->sentence(10),
            'is_active' => true,
        ];
    }
}
