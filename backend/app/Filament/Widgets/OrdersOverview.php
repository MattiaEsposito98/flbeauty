<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Orders\OrderResource;
use App\Filament\Resources\Products\ProductResource;
use App\Models\Order;
use App\Models\Product;
use Filament\Support\Icons\Heroicon;
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
                ->descriptionIcon(Heroicon::OutlinedArrowRight, 'after')
                ->color($nuovi > 0 ? 'warning' : 'success')
                ->url(OrderResource::getUrl('index')),

            Stat::make('Fatturato stimato (mese)', number_format((float) $fatturatoMese, 2, ',', '.').' €')
                ->description('Ordini non annullati')
                ->descriptionIcon(Heroicon::OutlinedArrowRight, 'after')
                ->color('success')
                ->url(OrderResource::getUrl('index')),

            Stat::make('Prodotti attivi', $prodottiAttivi)
                ->description('Visibili nel negozio')
                ->descriptionIcon(Heroicon::OutlinedArrowRight, 'after')
                ->color('primary')
                ->url(ProductResource::getUrl('index')),
        ];
    }
}
