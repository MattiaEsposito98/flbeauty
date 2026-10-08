<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductVariantFactory extends Factory
{
    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'name' => ucfirst(fake()->colorName()),
            'price' => null,
            'stock' => fake()->numberBetween(0, 20),
            'is_active' => true,
            'sort_order' => 0,
        ];
    }
}
