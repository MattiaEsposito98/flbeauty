<?php

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductFactory extends Factory
{
    public function definition(): array
    {
        $name = ucfirst(fake()->words(3, true));

        return [
            'category_id' => Category::inRandomOrder()->value('id'),
            'name' => $name,
            'description' => fake()->paragraph(3),
            'images' => [],
            'video' => null,
            'price' => fake()->randomFloat(2, 4.9, 89.9),
            'stock' => fake()->numberBetween(0, 60),
            'is_active' => fake()->boolean(85),
        ];
    }
}
