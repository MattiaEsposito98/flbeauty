<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ShippingRate extends Model
{
    use HasFactory;

    /** Spedizione in una regione italiana. */
    public const TYPE_REGION = 'regione';

    /** Consegna a un punto di ritiro del corriere. */
    public const TYPE_PICKUP_POINT = 'ritiro';

    /** Il cliente passa a ritirare di persona da noi: nessuna spedizione, nessun indirizzo da usare. */
    public const TYPE_HAND = 'a_mano';

    public const TYPES = [
        self::TYPE_REGION => 'Spedizione regionale',
        self::TYPE_PICKUP_POINT => 'Punto di ritiro',
        self::TYPE_HAND => 'Ritiro a mano',
    ];

    protected $fillable = [
        'name',
        'type',
        'price',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function isHandPickup(): bool
    {
        return $this->type === self::TYPE_HAND;
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }
}
