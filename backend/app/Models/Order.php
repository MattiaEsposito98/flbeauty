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
        'shipping_address_line',
        'shipping_postal_code',
        'shipping_city',
        'shipping_province',
        'status',
        'carrier',
        'tracking_number',
        'tracking_url',
        'total',
        'notes',
    ];

    public const STATUS_CANCELLED = 'annullato';

    /**
     * Pagina di tracking usata quando l'admin non inserisce un link specifico.
     * La pagina Poste non riceve il codice nell'URL: il cliente lo incolla nella
     * ricerca (sul sito c'è il pulsante "Copia" accanto al numero).
     */
    public const CARRIER_TRACKING_PAGES = [
        'Poste Italiane' => 'https://business.poste.it/grandi-imprese/cerca-spedizioni/index.html#/risultati-spedizioni',
        'SDA' => 'https://business.poste.it/grandi-imprese/cerca-spedizioni/index.html#/risultati-spedizioni',
    ];

    public const CARRIERS = ['Poste Italiane', 'SDA', 'BRT', 'GLS', 'DHL', 'UPS', 'Altro'];

    protected $appends = ['effective_tracking_url', 'tracking_needs_manual_code'];

    public function getEffectiveTrackingUrlAttribute(): ?string
    {
        if (blank($this->tracking_number)) {
            return null;
        }

        return $this->tracking_url ?: (self::CARRIER_TRACKING_PAGES[$this->carrier] ?? null);
    }

    // Vero quando il link porta alla ricerca generica del corriere, dove il
    // cliente deve incollare il codice a mano.
    public function getTrackingNeedsManualCodeAttribute(): bool
    {
        return in_array($this->effective_tracking_url, self::CARRIER_TRACKING_PAGES, true);
    }

    protected function casts(): array
    {
        return [
            'total' => 'decimal:2',
            'shipping_cost' => 'decimal:2',
        ];
    }

    /**
     * Stock: i pezzi vengono scalati quando l'ordine arriva (vedi OrderItem) e
     * restano riservati finché l'ordine non è annullato. Annullare li rimette in
     * magazzino, riattivare un ordine annullato li riscala.
     */
    protected static function booted(): void
    {
        static::saved(fn (Order $order) => $order->recalculateTotal());

        static::updated(function (Order $order) {
            if (! $order->wasChanged('status')) {
                return;
            }

            $wasCancelled = $order->getOriginal('status') === self::STATUS_CANCELLED;

            if (! $wasCancelled && ! $order->reservesStock()) {
                $order->adjustStockForItems(+1);
            } elseif ($wasCancelled && $order->reservesStock()) {
                $order->adjustStockForItems(-1);
            }
        });

        // Le righe vengono eliminate in cascata dal database, senza passare dagli
        // eventi di OrderItem: lo stock va quindi restituito qui.
        static::deleting(function (Order $order) {
            if ($order->reservesStock()) {
                $order->adjustStockForItems(+1);
            }
        });
    }

    public function reservesStock(): bool
    {
        return $this->status !== self::STATUS_CANCELLED;
    }

    private function adjustStockForItems(int $direction): void
    {
        foreach ($this->items()->get() as $item) {
            Product::whereKey($item->product_id)->increment('stock', $direction * $item->quantity);
        }
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

        $discountAmount = $this->calculateDiscountAmount($itemsTotal);

        $total = max(0, $itemsTotal - $discountAmount) + (float) $this->shipping_cost;

        if ((float) $this->total !== $total) {
            $this->updateQuietly(['total' => $total]);
        }
    }

    private function calculateDiscountAmount(float $itemsTotal): float
    {
        $discount = $this->discount;

        if (! $discount || ! $discount->is_active) {
            return 0;
        }

        if ($discount->type === 'percentuale') {
            return $itemsTotal * ((float) $discount->value / 100);
        }

        return min((float) $discount->value, $itemsTotal);
    }
}
