<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CookieConsentStat;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CookieConsentStatController extends Controller
{
    /**
     * Il banner cookie segnala quando viene mostrato, accettato o rifiutato.
     * Si salvano solo i totali del giorno, niente IP né identificativi.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'event' => ['required', Rule::in(CookieConsentStat::EVENTS)],
        ]);

        CookieConsentStat::record($data['event']);

        return response()->noContent();
    }
}
