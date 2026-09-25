<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Discount;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Database\Seeder;

/**
 * Popola il database locale con dati finti (categorie, prodotti, sconti, ordini)
 * senza mai toccare i dati già presenti: aggiunge soltanto, non cancella nulla.
 */
class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $targetCategories = 6;
        $existingNames = Category::pluck('name')->all();

        while (Category::count() < $targetCategories) {
            $candidate = Category::factory()->make();

            if (in_array($candidate->name, $existingNames, true)) {
                continue;
            }

            $candidate->save();
            $existingNames[] = $candidate->name;
        }

        $categories = Category::all();

        $products = Product::factory()
            ->count(13)
            ->create()
            ->each(function (Product $product) use ($categories) {
                if (blank($product->category_id)) {
                    $product->update(['category_id' => $categories->random()->id]);
                }
            })
            ->merge(Product::all());

        Discount::factory()->count(4)->create();

        Order::factory()
            ->count(6)
            ->create()
            ->each(function (Order $order) use ($products) {
                $items = $products->random(min($products->count(), random_int(1, 4)));

                foreach ($items as $product) {
                    $order->items()->create([
                        'product_id' => $product->id,
                        'quantity' => random_int(1, 3),
                        'unit_price' => $product->price,
                    ]);
                }
            });
    }
}
