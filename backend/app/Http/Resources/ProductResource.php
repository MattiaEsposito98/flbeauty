<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $hasVariants = $this->relationLoaded('activeVariants') && $this->activeVariants->isNotEmpty();

        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'price' => (float) $this->price,
            // Con le varianti è la somma di quelle attive (vedi Product::syncStockFromVariants()).
            'stock' => $this->stock,
            'in_stock' => $this->stock > 0,
            'has_variants' => $hasVariants,
            'variant_label' => $hasVariants ? ($this->variant_label ?: 'Variante') : null,
            'variants' => $this->when($this->relationLoaded('activeVariants'), fn () => $this->activeVariants->map(fn ($variant) => [
                'id' => $variant->id,
                'name' => $variant->name,
                'price' => $variant->effective_price,
                'stock' => $variant->stock,
                'in_stock' => $variant->stock > 0,
                'image' => $variant->image ? Storage::disk('public')->url($variant->image) : null,
            ])->values()),
            'images' => collect($this->images ?? [])
                ->map(fn (string $path) => Storage::disk('public')->url($path))
                ->values(),
            'category' => $this->whenLoaded('category', fn () => [
                'id' => $this->category->id,
                'name' => $this->category->name,
                'slug' => $this->category->slug,
            ]),
        ];
    }
}
