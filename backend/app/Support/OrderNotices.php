<?php

namespace App\Support;

use App\Mail\OrderStatusUpdated;
use App\Models\Order;

/**
 * Le notifiche nel sito (campanella) legate agli ordini: le stesse cose che dicono le email, per il
 * cliente (se ha un account) e per l'admin. Vedi SiteNotifier.
 */
class OrderNotices
{
    public static function number(Order $order): string
    {
        return '#'.str_pad((string) $order->id, 5, '0', STR_PAD_LEFT);
    }

    public static function money(float|string|null $value): string
    {
        return number_format((float) $value, 2, ',', '.').' €';
    }

    private static function customerUrl(Order $order): string
    {
        return '/ordini/'.$order->id;
    }

    private static function adminUrl(Order $order): string
    {
        return url("/admin/orders/{$order->id}/edit");
    }

    /** Ordine ricevuto dal sito. */
    public static function placed(Order $order): void
    {
        $number = self::number($order);

        SiteNotifier::toUser(
            $order->user,
            'order',
            "Ordine {$number} ricevuto",
            'Totale '.self::money($order->total).'. Ti scriviamo su WhatsApp con le istruzioni per il pagamento: sul sito non devi pagare nulla.',
            self::customerUrl($order),
        );

        SiteNotifier::toAdmins("Nuovo ordine {$number}", "{$order->customer_name} · ".self::money($order->total), self::adminUrl($order), 'success');
    }

    /** Cambio di stato (o tracking aggiunto) deciso dall'admin: lo stesso testo dell'email. */
    public static function statusChanged(Order $order): void
    {
        $number = self::number($order);
        $label = OrderStatusUpdated::STATUS_LABELS[$order->status] ?? $order->status;

        $body = match ($order->status) {
            'in_lavorazione' => 'Abbiamo ricevuto il pagamento: stiamo preparando il tuo ordine.',
            'evaso' => filled($order->tracking_number)
                ? trim('Il tuo ordine è stato spedito'.($order->carrier ? " con {$order->carrier}" : '').'. Numero di tracking: '.$order->tracking_number.'.')
                : 'Il tuo ordine è stato spedito.',
            'annullato' => 'L\'ordine è stato annullato. Se hai dubbi scrivici su WhatsApp.',
            default => 'Il tuo ordine è in attesa di pagamento.',
        };

        SiteNotifier::toUser($order->user, 'order', "Ordine {$number}: {$label}", $body, self::customerUrl($order));
    }

    /**
     * Ordine modificato: dal cliente (l'admin lo viene a sapere, il cliente ha la conferma) oppure
     * dall'admin (il cliente viene avvisato col pulsante «Avvisa il cliente»).
     *
     * @param  list<string>  $lines  cosa è cambiato
     */
    public static function edited(Order $order, array $lines, bool $byCustomer): void
    {
        $number = self::number($order);
        $text = implode(' · ', $lines);

        if ($byCustomer) {
            SiteNotifier::toUser($order->user, 'order', "Hai modificato l'ordine {$number}", $text, self::customerUrl($order));
            SiteNotifier::toAdmins("Ordine {$number} modificato dal cliente", "{$order->customer_name}: {$text}", self::adminUrl($order), 'warning');

            return;
        }

        SiteNotifier::toUser($order->user, 'order', "Il tuo ordine {$number} è stato aggiornato", $text, self::customerUrl($order));
    }

    /** Ordine annullato dal cliente. */
    public static function cancelledByCustomer(Order $order): void
    {
        $number = self::number($order);

        SiteNotifier::toAdmins("Ordine {$number} annullato dal cliente", $order->customer_name.' · '.self::money($order->total), self::adminUrl($order), 'danger');
    }
}
