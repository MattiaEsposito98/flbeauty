<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'product_id',
        'product_variant_id',
        'variant_name',
        'quantity',
        'unit_price',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'unit_price' => 'decimal:2',
        ];
    }

    /**
     * Ogni riga di un ordine non annullato tiene riservati i suoi pezzi: creare,
     * modificare o eliminare una riga (dal sito o dall'admin) aggiorna lo stock
     * della sola differenza.
     */
    protected static function booted(): void
    {
        // Il nome della variante si "fotografa" qui: resta leggibile anche se poi la variante si elimina.
        static::creating(function (OrderItem $item) {
            if ($item->product_variant_id && blank($item->variant_name)) {
                $item->variant_name = ProductVariant::whereKey($item->product_variant_id)->value('name');
            }
        });

        static::updating(function (OrderItem $item) {
            if ($item->isDirty('product_variant_id')) {
                $item->variant_name = $item->product_variant_id
                    ? ProductVariant::whereKey($item->product_variant_id)->value('name')
                    : null;
            }
        });

        static::created(function (OrderItem $item) {
            if ($item->order?->reservesStock()) {
                self::adjustStock($item->product_id, $item->product_variant_id, -$item->quantity);
            }

            $item->order?->recalculateTotal();
        });

        static::updated(function (OrderItem $item) {
            if ($item->order?->reservesStock()) {
                if ($item->wasChanged('product_id') || $item->wasChanged('product_variant_id')) {
                    self::adjustStock(
                        $item->getOriginal('product_id'),
                        $item->getOriginal('product_variant_id'),
                        $item->getOriginal('quantity')
                    );
                    self::adjustStock($item->product_id, $item->product_variant_id, -$item->quantity);
                } elseif ($item->wasChanged('quantity')) {
                    $difference = $item->quantity - $item->getOriginal('quantity');
                    self::adjustStock($item->product_id, $item->product_variant_id, -$difference);
                }
            }

            $item->order?->recalculateTotal();
        });

        static::deleted(function (OrderItem $item) {
            if ($item->order?->reservesStock()) {
                self::adjustStock($item->product_id, $item->product_variant_id, $item->quantity);
            }

            $item->order?->recalculateTotal();
        });
    }

    /**
     * Sposta pezzi dal magazzino di una riga d'ordine: della variante se ce l'ha, altrimenti
     * del prodotto. Un numero negativo scala, uno positivo restituisce.
     */
    public static function adjustStock(?int $productId, ?int $variantId, int $delta): void
    {
        if ($variantId) {
            ProductVariant::adjustStock($variantId, $delta);
        } elseif ($productId) {
            Product::whereKey($productId)->increment('stock', $delta);
        }
    }
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    /** Nome da mostrare: "Rossetto matte – Rosso". */
    public function displayName(): string
    {
        $name = $this->product?->name ?? 'Prodotto';

        return $this->variant_name ? $name.' – '.$this->variant_name : $name;
    }
}
