<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use App\Models\Product;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class OrdersOverview extends StatsOverviewWidget
{
    protected static ?int $sort = -2;

    protected function getStats(): array
    {
        $nuovi = Order::where('status', 'nuovo')->count();

        $fatturatoMese = Order::whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->where('status', '!=', 'annullato')
            ->sum('total');

        $prodottiAttivi = Product::where('is_active', true)->count();

        return [
            Stat::make('Ordini da gestire', $nuovi)
                ->description('Nuovi ordini in attesa')
                ->color($nuovi > 0 ? 'warning' : 'success'),

            Stat::make('Fatturato stimato (mese)', number_format((float) $fatturatoMese, 2, ',', '.').' €')
                ->description('Ordini non annullati')
                ->color('success'),

            Stat::make('Prodotti attivi', $prodottiAttivi)
                ->description('Visibili nel negozio')
                ->color('primary'),
        ];
    }
}
