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
        static::created(function (OrderItem $item) {
            if ($item->order?->reservesStock()) {
                Product::whereKey($item->product_id)->decrement('stock', $item->quantity);
            }

            $item->order?->recalculateTotal();
        });

        static::updated(function (OrderItem $item) {
            if ($item->order?->reservesStock()) {
                if ($item->wasChanged('product_id')) {
                    Product::whereKey($item->getOriginal('product_id'))
                        ->increment('stock', $item->getOriginal('quantity'));
                    Product::whereKey($item->product_id)->decrement('stock', $item->quantity);
                } elseif ($item->wasChanged('quantity')) {
                    $difference = $item->quantity - $item->getOriginal('quantity');
                    Product::whereKey($item->product_id)->decrement('stock', $difference);
                }
            }

            $item->order?->recalculateTotal();
        });

        static::deleted(function (OrderItem $item) {
            if ($item->order?->reservesStock()) {
                Product::whereKey($item->product_id)->increment('stock', $item->quantity);
            }

            $item->order?->recalculateTotal();
        });
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
