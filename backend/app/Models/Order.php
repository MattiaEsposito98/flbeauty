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
     * Link di tracking costruito dal numero quando l'admin non inserisce un
     * link specifico: la pagina Poste apre direttamente la spedizione se il
     * codice è in fondo all'URL ({code}). Vale anche per SDA (gruppo Poste).
     */
    public const CARRIER_TRACKING_URLS = [
        'Poste Italiane' => 'https://business.poste.it/grandi-imprese/cerca-spedizioni/index.html#/risultati-spedizioni/{code}',
        'SDA' => 'https://business.poste.it/grandi-imprese/cerca-spedizioni/index.html#/risultati-spedizioni/{code}',
    ];

    /**
     * Pagina di ricerca Poste senza codice: il vecchio form admin la salvava nel
     * campo link. Non porta alla spedizione, quindi viene sempre scartata.
     */
    public const GENERIC_TRACKING_PAGE = 'https://business.poste.it/grandi-imprese/cerca-spedizioni/index.html#/risultati-spedizioni';

    public const CARRIERS = ['Poste Italiane', 'SDA', 'BRT', 'GLS', 'DHL', 'UPS', 'Altro'];

    public const DEFAULT_CARRIER = 'Poste Italiane';

    protected $appends = ['effective_tracking_url'];

    public static function isGenericTrackingPage(string $url): bool
    {
        return rtrim(trim($url), '/') === self::GENERIC_TRACKING_PAGE;
    }

    public function getEffectiveTrackingUrlAttribute(): ?string
    {
        if (blank($this->tracking_number)) {
            return null;
        }

        if (filled($this->tracking_url) && ! self::isGenericTrackingPage($this->tracking_url)) {
            return $this->tracking_url;
        }

        $template = self::CARRIER_TRACKING_URLS[$this->carrier] ?? null;

        return $template
            ? str_replace('{code}', rawurlencode(trim($this->tracking_number)), $template)
            : null;
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
        static::saving(function (Order $order) {
            if (filled($order->tracking_url) && self::isGenericTrackingPage($order->tracking_url)) {
                $order->tracking_url = null;
            }
        });

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
            OrderItem::adjustStock($item->product_id, $item->product_variant_id, $direction * $item->quantity);
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
