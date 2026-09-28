<?php

namespace App\Filament\Resources\Users\Widgets;

use App\Models\User;
use App\Models\UserLogin;
use Carbon\CarbonPeriod;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class UserActivityChart extends ChartWidget
{
    protected ?string $heading = 'Accessi e nuove registrazioni';

    protected ?string $pollingInterval = null;

    protected ?string $maxHeight = '280px';

    public ?string $filter = '30';

    protected function getFilters(): ?array
    {
        return [
            '7' => 'Ultimi 7 giorni',
            '30' => 'Ultimi 30 giorni',
            '90' => 'Ultimi 90 giorni',
        ];
    }

    protected function getData(): array
    {
        $days = (int) $this->filter;
        $from = now()->subDays($days - 1)->startOfDay();

        $logins = UserLogin::query()
            ->where('logged_in_at', '>=', $from)
            ->selectRaw('DATE(logged_in_at) as day, COUNT(*) as total')
            ->groupBy(DB::raw('DATE(logged_in_at)'))
            ->pluck('total', 'day');

        $registrations = User::query()
            ->where('is_admin', false)
            ->where('created_at', '>=', $from)
            ->selectRaw('DATE(created_at) as day, COUNT(*) as total')
            ->groupBy(DB::raw('DATE(created_at)'))
            ->pluck('total', 'day');

        $period = collect(CarbonPeriod::create($from, now()->startOfDay()));

        return [
            'datasets' => [
                [
                    'label' => 'Accessi',
                    'data' => $period->map(fn ($day) => (int) ($logins[$day->toDateString()] ?? 0))->all(),
                    'borderColor' => '#B76E79',
                    'backgroundColor' => 'rgba(183, 110, 121, 0.15)',
                    'fill' => true,
                    'tension' => 0.3,
                ],
                [
                    'label' => 'Nuove registrazioni',
                    'data' => $period->map(fn ($day) => (int) ($registrations[$day->toDateString()] ?? 0))->all(),
                    'borderColor' => '#8D6C65',
                    'backgroundColor' => 'rgba(141, 108, 101, 0.1)',
                    'tension' => 0.3,
                ],
            ],
            'labels' => $period->map(fn ($day) => $day->format('d/m'))->all(),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getOptions(): array
    {
        return [
            'scales' => ['y' => ['beginAtZero' => true, 'ticks' => ['precision' => 0]]],
        ];
    }
}
