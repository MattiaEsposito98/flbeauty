<?php

namespace App\Filament\Resources\Orders\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\SelectColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class OrdersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('Ordine')
                    ->formatStateUsing(fn (int $state) => '#'.str_pad((string) $state, 5, '0', STR_PAD_LEFT))
                    ->weight('bold')
                    ->sortable(),
                TextColumn::make('customer_name')
                    ->label('Cliente')
                    ->description(fn ($record) => $record->customer_email)
                    ->searchable(['customer_name', 'customer_email'])
                    ->sortable(),
                TextColumn::make('items_count')
                    ->label('Articoli')
                    ->counts('items')
                    ->badge()
                    ->color('gray')
                    ->alignCenter(),
                SelectColumn::make('status')
                    ->label('Stato')
                    ->options([
                        'nuovo' => 'Nuovo',
                        'in_lavorazione' => 'In lavorazione',
                        'evaso' => 'Evaso',
                        'annullato' => 'Annullato',
                    ])
                    ->selectablePlaceholder(false),
                TextColumn::make('total')
                    ->label('Totale')
                    ->money('EUR')
                    ->weight('medium')
                    ->sortable()
                    ->alignEnd(),
                TextColumn::make('shippingRate.name')
                    ->label('Spedizione')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('discount.code')
                    ->label('Sconto')
                    ->badge()
                    ->color('info')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->label('Ricevuto il')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Stato')
                    ->options([
                        'nuovo' => 'Nuovo',
                        'in_lavorazione' => 'In lavorazione',
                        'evaso' => 'Evaso',
                        'annullato' => 'Annullato',
                    ]),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->striped()
            ->emptyStateHeading('Nessun ordine')
            ->emptyStateDescription('Qui compariranno gli ordini inviati dai clienti dal sito.');
    }
}
