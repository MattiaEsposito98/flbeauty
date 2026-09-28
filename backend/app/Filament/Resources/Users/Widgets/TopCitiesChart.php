<?php

namespace App\Filament\Resources\Users\Widgets;

use App\Models\Address;
use Filament\Widgets\ChartWidget;

class TopCitiesChart extends ChartWidget
{
    protected ?string $heading = 'Città con più utenti';

    protected ?string $description = "In base all'indirizzo principale (prime 10)";

    protected ?string $pollingInterval = null;

    protected ?string $maxHeight = '280px';

    protected function getData(): array
    {
        $cities = Address::query()
            ->where('is_default', true)
            ->whereHas('user', fn ($q) => $q->where('is_admin', false))
            ->join('comuni', 'comuni.id', '=', 'addresses.comune_id')
            ->selectRaw('comuni.name as city, addresses.province as province, COUNT(*) as total')
            ->groupBy('comuni.name', 'addresses.province')
            ->orderByDesc('total')
            ->orderBy('comuni.name')
            ->limit(10)
            ->get();

        return [
            'datasets' => [
                [
                    'label' => 'Utenti',
                    'data' => $cities->pluck('total')->map(fn ($value) => (int) $value)->all(),
                    'backgroundColor' => '#D6A1A4',
                    'borderColor' => '#B76E79',
                    'borderWidth' => 1,
                    'borderRadius' => 6,
                ],
            ],
            'labels' => $cities->map(fn ($city) => $city->city.' ('.$city->province.')')->all(),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getOptions(): array
    {
        return [
            'indexAxis' => 'y',
            'plugins' => ['legend' => ['display' => false]],
            'scales' => ['x' => ['beginAtZero' => true, 'ticks' => ['precision' => 0]]],
        ];
    }
}
