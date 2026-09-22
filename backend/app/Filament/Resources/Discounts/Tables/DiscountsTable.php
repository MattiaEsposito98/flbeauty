<?php

namespace App\Filament\Resources\Discounts\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class DiscountsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')
                    ->label('Codice')
                    ->searchable()
                    ->weight('bold'),
                TextColumn::make('description')
                    ->label('Descrizione')
                    ->searchable()
                    ->limit(40),
                TextColumn::make('type')
                    ->label('Tipo')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => $state === 'fisso' ? 'Importo fisso' : 'Percentuale')
                    ->color(fn (string $state) => $state === 'fisso' ? 'info' : 'primary'),
                TextColumn::make('value')
                    ->label('Valore')
                    ->formatStateUsing(fn ($state, $record) => $record->type === 'fisso'
                        ? number_format((float) $state, 2, ',', '.').' €'
                        : number_format((float) $state, 0).' %')
                    ->sortable(),
                TextColumn::make('starts_at')
                    ->label('Dal')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                TextColumn::make('ends_at')
                    ->label('Al')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                IconColumn::make('is_active')
                    ->label('Attivo')
                    ->boolean(),
                TextColumn::make('created_at')
                    ->label('Creato il')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
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
            ->defaultSort('created_at', 'desc');
    }
}
