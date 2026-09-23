<?php

namespace App\Filament\Resources\ShippingRates\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\TextInputColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class ShippingRatesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Destinazione')
                    ->searchable()
                    ->sortable()
                    ->weight('medium'),
                TextColumn::make('type')
                    ->label('Tipo')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => $state === 'ritiro' ? 'Punto di ritiro' : 'Spedizione')
                    ->color(fn (string $state) => $state === 'ritiro' ? 'info' : 'primary'),
                TextInputColumn::make('price')
                    ->label('Prezzo (€)')
                    ->type('number')
                    ->rules(['required', 'numeric', 'min:0'])
                    ->sortable(),
                ToggleColumn::make('is_active')
                    ->label('Attivo')
                    ->alignCenter(),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->label('Tipo')
                    ->options([
                        'regione' => 'Spedizione regionale',
                        'ritiro' => 'Punto di ritiro',
                    ]),
                TernaryFilter::make('is_active')
                    ->label('Attivo'),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('name')
            ->defaultPaginationPageOption(25)
            ->striped()
            ->emptyStateHeading('Nessun metodo di spedizione')
            ->emptyStateDescription('Aggiungi le destinazioni e i relativi costi di spedizione.');
    }
}
