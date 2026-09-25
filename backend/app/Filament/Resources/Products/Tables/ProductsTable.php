<?php

namespace App\Filament\Resources\Products\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class ProductsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('cover_image')
                    ->label('')
                    ->disk('public')
                    ->square()
                    ->size(48)
                    ->defaultImageUrl(asset('images/logo-mark.png')),
                TextColumn::make('name')
                    ->label('Prodotto')
                    ->description(fn ($record) => $record->category?->name)
                    ->searchable()
                    ->sortable()
                    ->weight('medium'),
                TextColumn::make('price')
                    ->label('Prezzo')
                    ->money('EUR')
                    ->sortable()
                    ->alignEnd(),
                TextColumn::make('stock')
                    ->label('Disponibilità')
                    ->badge()
                    ->alignCenter()
                    ->formatStateUsing(fn (int $state) => $state === 0 ? 'Esaurito' : $state.' pz')
                    ->color(fn (int $state): string => match (true) {
                        $state === 0 => 'danger',
                        $state <= 5 => 'warning',
                        default => 'success',
                    })
                    ->sortable(),
                ToggleColumn::make('is_active')
                    ->label('Attivo')
                    ->alignCenter(),
                TextColumn::make('created_at')
                    ->label('Creato il')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('category_id')
                    ->label('Categoria')
                    ->relationship('category', 'name')
                    ->preload(),
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
            ->emptyStateHeading('Nessun prodotto')
            ->emptyStateDescription('Carica il primo prodotto per popolare il catalogo.');
    }
}
