<?php

namespace App\Filament\Resources\Discounts\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
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
                    ->weight('bold')
                    ->copyable()
                    ->copyMessage('Codice copiato')
                    ->description(fn ($record) => $record->description),
                TextColumn::make('value')
                    ->label('Sconto')
                    ->badge()
                    ->color(fn ($record) => $record->type === 'fisso' ? 'info' : 'primary')
                    ->formatStateUsing(fn ($state, $record) => $record->type === 'fisso'
                        ? number_format((float) $state, 2, ',', '.').' €'
                        : rtrim(rtrim(number_format((float) $state, 2, ',', '.'), '0'), ',').'%')
                    ->sortable(),
                TextColumn::make('starts_at')
                    ->label('Valido dal')
                    ->dateTime('d/m/Y')
                    ->placeholder('Sempre')
                    ->sortable(),
                TextColumn::make('ends_at')
                    ->label('Fino al')
                    ->dateTime('d/m/Y')
                    ->placeholder('Nessuna scadenza')
                    ->sortable(),
                ToggleColumn::make('is_active')
                    ->label('Attivo')
                    ->alignCenter(),
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
            ->defaultSort('created_at', 'desc')
            ->striped()
            ->emptyStateHeading('Nessuno sconto')
            ->emptyStateDescription('Crea un codice sconto da comunicare ai clienti.');
    }
}
