<?php

namespace App\Filament\Resources\Orders\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
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
                    ->weight('bold'),
                TextColumn::make('customer_name')
                    ->label('Cliente')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('customer_email')
                    ->label('Email')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('shippingRate.name')
                    ->label('Spedizione')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('discount.code')
                    ->label('Sconto')
                    ->badge()
                    ->color('info')
                    ->placeholder('—'),
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
                    ->sortable(),
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
            ->defaultSort('created_at', 'desc');
    }
}
