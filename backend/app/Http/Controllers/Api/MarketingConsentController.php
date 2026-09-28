<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;

class MarketingConsentController extends Controller
{
    /**
     * Il cliente attiva o disattiva le email promozionali dal proprio account.
     */
    public function update(Request $request)
    {
        $data = $request->validate([
            'marketing_consent' => ['required', 'boolean'],
        ]);

        $request->user()->setMarketingConsent($data['marketing_consent']);

        return $request->user()->fresh()->load('addresses.comune');
    }

    /**
     * Disiscrizione dal link in fondo alle email promozionali, senza login:
     * l'identità è garantita dalla firma dell'URL (vedi unsubscribeUrl()).
     * È una POST e non una GET perché i filtri antispam aprono i link delle
     * email in automatico: con una GET disiscriverebbero il cliente da soli.
     * La stessa rotta serve la disiscrizione "in un clic" di Gmail/Outlook
     * (header List-Unsubscribe-Post).
     */
    public function unsubscribe(User $user)
    {
        $user->setMarketingConsent(false);

        return response()->json([
            'message' => 'Fatto! Non riceverai più email promozionali da F&L Beauty. Continuerai a ricevere solo le comunicazioni sui tuoi ordini e sul tuo account.',
        ]);
    }

    /**
     * Link firmati per disiscriversi, senza scadenza. La firma è relativa (non
     * include il dominio), così vale anche se l'API sta dietro un proxy.
     *
     * - `api`: chiamato direttamente dai client di posta (disiscrizione in un clic)
     * - `page`: pagina del sito linkata nel testo dell'email, che chiede conferma
     *   e poi chiama la rotta API con la stessa firma
     *
     * @return array{api: string, page: string}
     */
    public static function unsubscribeUrls(User $user): array
    {
        $signedPath = URL::signedRoute('marketing.unsubscribe', ['user' => $user->id], absolute: false);
        parse_str((string) parse_url($signedPath, PHP_URL_QUERY), $query);

        return [
            'api' => url($signedPath),
            'page' => config('app.frontend_url').'/disiscrizione?'.http_build_query([
                'u' => $user->id,
                'signature' => $query['signature'] ?? '',
            ]),
        ];
    }
}
