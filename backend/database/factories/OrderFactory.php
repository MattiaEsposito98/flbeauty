<?php

namespace Database\Factories;

use App\Models\ShippingRate;
use Illuminate\Database\Eloquent\Factories\Factory;

class OrderFactory extends Factory
{
    public function definition(): array
    {
        $shippingRate = ShippingRate::inRandomOrder()->first();

        return [
            'customer_name' => fake()->name(),
            'customer_email' => fake()->safeEmail(),
            'customer_phone' => fake()->boolean(70) ? fake()->phoneNumber() : null,
            'status' => fake()->randomElement(['nuovo', 'in_lavorazione', 'evaso', 'annullato']),
            'shipping_rate_id' => $shippingRate?->id,
            'shipping_cost' => $shippingRate?->price ?? 0,
            'notes' => fake()->boolean(30) ? fake()->sentence(8) : null,
        ];
    }
}
