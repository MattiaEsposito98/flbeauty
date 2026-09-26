<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ShippingRate;

class ShippingRateController extends Controller
{
    public function __invoke()
    {
        return ShippingRate::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'type', 'price']);
    }
}
