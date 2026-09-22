<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'discount_id',
        'shipping_rate_id',
        'shipping_cost',
        'customer_name',
        'customer_email',
        'customer_phone',
        'status',
        'total',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'total' => 'decimal:2',
            'shipping_cost' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::saved(fn (Order $order) => $order->recalculateTotal());
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function discount(): BelongsTo
    {
        return $this->belongsTo(Discount::class);
    }

    public function shippingRate(): BelongsTo
    {
        return $this->belongsTo(ShippingRate::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function recalculateTotal(): void
    {
        $itemsTotal = $this->items()->get()->sum(
            fn (OrderItem $item) => $item->quantity * $item->unit_price
        );

        $total = $itemsTotal + (float) $this->shipping_cost;

        if ((float) $this->total !== $total) {
            $this->updateQuietly(['total' => $total]);
        }
    }
}
