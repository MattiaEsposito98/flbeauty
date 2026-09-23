<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Orders\OrderResource;
use App\Models\Order;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class LatestOrders extends TableWidget
{
    protected static ?int $sort = -1;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->heading('Ultimi ordini')
            ->query(Order::query()->latest())
            ->columns([
                TextColumn::make('id')
                    ->label('Ordine')
                    ->formatStateUsing(fn (int $state) => '#'.str_pad((string) $state, 5, '0', STR_PAD_LEFT))
                    ->weight('bold'),
                TextColumn::make('customer_name')
                    ->label('Cliente')
                    ->description(fn ($record) => $record->customer_email),
                TextColumn::make('status')
                    ->label('Stato')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'nuovo' => 'Nuovo',
                        'in_lavorazione' => 'In lavorazione',
                        'evaso' => 'Evaso',
                        'annullato' => 'Annullato',
                        default => $state,
                    })
                    ->color(fn (string $state) => match ($state) {
                        'nuovo' => 'info',
                        'in_lavorazione' => 'warning',
                        'evaso' => 'success',
                        'annullato' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('total')
                    ->label('Totale')
                    ->money('EUR')
                    ->alignEnd(),
                TextColumn::make('created_at')
                    ->label('Ricevuto il')
                    ->dateTime('d/m/Y H:i'),
            ])
            ->recordActions([
                Action::make('apri')
                    ->label('Apri')
                    ->icon('heroicon-m-arrow-top-right-on-square')
                    ->url(fn (Order $record) => OrderResource::getUrl('edit', ['record' => $record])),
            ])
            ->paginated([5, 10, 25])
            ->defaultPaginationPageOption(5)
            ->emptyStateHeading('Ancora nessun ordine')
            ->emptyStateDescription('Gli ordini inviati dal sito compariranno qui.');
    }
}
