<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

class UserLogin extends Model
{
    public $timestamps = false;

    protected $fillable = ['user_id', 'logged_in_at'];

    protected function casts(): array
    {
        return [
            'logged_in_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Registra un accesso riuscito dal sito: storico per le statistiche e
     * contatore/ultimo accesso sull'utente. Lo storico si tiene 12 mesi
     * (dichiarato nella privacy policy); la pulizia gira ogni tanto qui, così
     * non serve un cron.
     */
    public static function record(User $user): void
    {
        DB::transaction(function () use ($user) {
            static::create(['user_id' => $user->id, 'logged_in_at' => now()]);

            $user->forceFill(['last_login_at' => now()])->save();
            $user->increment('login_count');
        });

        if (random_int(1, 50) === 1) {
            static::where('logged_in_at', '<', now()->subYear())->delete();
        }
    }
}
