<?php

namespace App\Http\Concerns;

use App\Models\Comune;
use Illuminate\Validation\ValidationException;

trait ResolvesComuneForAddress
{
    /**
     * La provincia è sempre quella del comune scelto (mai fidarsi del client),
     * e il CAP deve essere uno di quelli validi per quel comune, quando noti.
     */
    private function resolveComuneFields(int $comuneId, string $postalCode, string $postalCodeField = 'postal_code'): array
    {
        $comune = Comune::findOrFail($comuneId);

        if (filled($comune->postal_codes) && ! in_array($postalCode, $comune->postal_codes, true)) {
            throw ValidationException::withMessages([
                $postalCodeField => ["Il CAP {$postalCode} non è valido per {$comune->name}."],
            ]);
        }

        return [
            'province' => $comune->province,
            'postal_code' => $postalCode,
        ];
    }
}
