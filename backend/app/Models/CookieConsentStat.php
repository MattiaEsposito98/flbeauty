<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Conteggi giornalieri anonimi del banner cookie: quante volte è stato
 * mostrato, accettato, rifiutato. Nessun dato che identifica il visitatore.
 */
class CookieConsentStat extends Model
{
    public $timestamps = false;

    public const EVENTS = ['shown', 'accepted', 'rejected'];

    protected $fillable = ['date', 'shown', 'accepted', 'rejected'];

    protected function casts(): array
    {
        return [
            'date' => 'date',
        ];
    }

    public static function record(string $event): void
    {
        $today = now()->toDateString();

        static::query()->insertOrIgnore(['date' => $today]);
        static::query()->where('date', $today)->increment($event);
    }
}
