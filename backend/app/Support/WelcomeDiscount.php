<?php

namespace App\Support;

use App\Mail\WelcomeDiscountMail;
use App\Models\Discount;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Codice sconto di benvenuto: quando un cliente verifica l'email gli creiamo un codice personale
 * (percentuale e giorni in config/shop.php) e glielo mandiamo per email. Vale solo per il suo
 * account, una volta sola, entro la scadenza (vedi Discount::errorFor()).
 */
class WelcomeDiscount
{
    /** Senza O/0, I/1: il codice si copia a mano e non deve confondere. */
    private const ALPHABET = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

    /**
     * Crea il codice e manda l'email. Una sola volta per account: se esiste già (secondo clic sul
     * link, utente che si riverifica) non fa nulla e restituisce null. Lo staff non lo riceve.
     */
    public static function issueFor(User $user): ?Discount
    {
        if ($user->is_admin || Discount::where('user_id', $user->id)->where('kind', Discount::KIND_WELCOME)->exists()) {
            return null;
        }

        $percent = (int) config('shop.welcome_discount_percent');
        $days = (int) config('shop.welcome_discount_days');

        try {
            $discount = Discount::create([
                'code' => self::newCode(),
                'kind' => Discount::KIND_WELCOME,
                'user_id' => $user->id,
                'description' => "Codice di benvenuto di {$user->name}",
                'type' => 'percentuale',
                'value' => $percent,
                'starts_at' => now(),
                'ends_at' => now()->addDays($days),
                'is_active' => true,
            ]);
        } catch (UniqueConstraintViolationException) {
            return null;   // due verifiche quasi insieme: l'altra ha già creato il codice
        }

        // Un problema con la posta non deve mai bloccare la verifica dell'email: il codice esiste
        // comunque (l'admin lo vede senza «email inviata» nella scheda dei codici di benvenuto).
        try {
            Mail::to($user->email)->send(new WelcomeDiscountMail($user, $discount));
            $discount->forceFill(['emailed_at' => now()])->save();
        } catch (Throwable $e) {
            report($e);
        }

        return $discount;
    }

    private static function newCode(): string
    {
        do {
            $suffix = '';
            for ($i = 0; $i < 6; $i++) {
                $suffix .= self::ALPHABET[random_int(0, strlen(self::ALPHABET) - 1)];
            }
            $code = 'BENVENUTO-'.$suffix;
        } while (Discount::where('code', $code)->exists());

        return $code;
    }
}
