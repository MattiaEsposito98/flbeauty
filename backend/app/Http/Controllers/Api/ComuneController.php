<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Comune;
use Illuminate\Http\Request;

class ComuneController extends Controller
{
    /**
     * Autocomplete per il campo "comune" nei form di indirizzo.
     */
    public function __invoke(Request $request)
    {
        $search = (string) $request->query('search', '');

        return Comune::query()
            ->when($search !== '', fn ($query) => $query->where('name', 'like', $search.'%'))
            ->orderBy('name')
            ->limit(20)
            ->get(['id', 'name', 'province', 'postal_codes']);
    }
}
