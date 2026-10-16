<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Una modifica (o annullamento) di un ordine, con l'elenco di cosa è cambiato. */
class OrderEvent extends Model
{
    public const ACTOR_CUSTOMER = 'customer';

    public const ACTOR_ADMIN = 'admin';

    public const TYPE_EDITED = 'edited';

    public const TYPE_CANCELLED = 'cancelled';

    protected $fillable = [
        'order_id',
        'actor',
        'type',
        'summary',
        'total_before',
        'total_after',
        'notified_at',
    ];

    protected function casts(): array
    {
        return [
            'summary' => 'array',
            'total_before' => 'decimal:2',
            'total_after' => 'decimal:2',
            'notified_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
