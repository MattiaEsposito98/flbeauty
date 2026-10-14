<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Discount;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class DiscountCheckController extends Controller
{
    /**
     * Controlla un codice sconto PRIMA dell'ordine («Applica» nel pagamento): dice se vale per questo
     * cliente e quanto sconta. Non lo consuma: la validità vera si ricontrolla all'invio dell'ordine.
     */
    public function __invoke(Request $request)
    {
        $data = $request->validate(['code' => ['required', 'string', 'max:50']]);

        $discount = Discount::findByCode($data['code']);
        $error = $discount ? $discount->errorFor($request->user()) : 'Codice sconto non valido o scaduto.';

        if ($error) {
            throw ValidationException::withMessages(['code' => [$error]]);
        }

        return response()->json(['data' => [
            'code' => $discount->code,
            'type' => $discount->type,
            'value' => (float) $discount->value,
            'ends_at' => $discount->ends_at?->toIso8601String(),
        ]]);
    }
}
