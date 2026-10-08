<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/**
 * Variante di un prodotto (colore, profumo...). Ha un magazzino suo: il prodotto "padre"
 * tiene solo la somma delle varianti attive (vedi Product::syncStockFromVariants()).
 */
class ProductVariant extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'name',
        'price',
        'stock',
        'image',
        'is_active',
        'sort_order',
    ];

    protected $appends = ['effective_price'];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'stock' => 'integer',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        // Ogni modifica a una variante riallinea la disponibilità totale del prodotto.
        static::saved(fn (ProductVariant $variant) => $variant->product?->syncStockFromVariants());
        static::deleted(fn (ProductVariant $variant) => Product::find($variant->product_id)?->syncStockFromVariants());
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /** Prezzo della variante, o quello del prodotto se non ne ha uno suo. */
    public function getEffectivePriceAttribute(): float
    {
        return (float) ($this->price ?? $this->product?->price ?? 0);
    }

    public function getImageUrlAttribute(): ?string
    {
        return $this->image ? Storage::disk('public')->url($this->image) : null;
    }

    /**
     * Scala (o restituisce, con un numero negativo) pezzi dal magazzino della variante e
     * riallinea il totale del prodotto. Unico punto da usare per ordini e annullamenti.
     */
    public static function adjustStock(int $variantId, int $delta): void
    {
        $variant = static::find($variantId);

        if (! $variant) {
            return;
        }

        static::whereKey($variantId)->increment('stock', $delta);
        Product::find($variant->product_id)?->syncStockFromVariants();
    }
}
