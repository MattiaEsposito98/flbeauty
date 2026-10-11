<?php

namespace App\Filament\Resources\Products\Tables;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Order;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\TextInputColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class ProductsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query
                ->with(['category', 'brand'])
                ->withCount('variants')
                // Pezzi venduti: somma delle righe d'ordine, esclusi gli ordini annullati.
                ->withSum(['orderItems as sold_quantity' => fn (Builder $q) => $q->whereHas(
                    'order',
                    fn (Builder $o) => $o->where('status', '!=', Order::STATUS_CANCELLED)
                )], 'quantity'))
            ->columns([
                ImageColumn::make('cover_image')
                    ->label('')
                    ->disk('public')
                    ->square()
                    ->size(48)
                    ->defaultImageUrl(asset('images/logo-mark.png')),
                TextColumn::make('name')
                    ->label('Prodotto')
                    ->description(fn ($record) => collect([
                        $record->category?->name,
                        $record->brand?->name,
                        $record->variants_count ? $record->variants_count.' varianti' : null,
                    ])->filter()->implode(' · '))
                    // Si trova anche scrivendo la marca, la categoria o il nome di una variante.
                    ->searchable(query: fn (Builder $query, string $search) => $query->where(fn (Builder $q) => $q
                        ->where('name', 'like', "%{$search}%")
                        ->orWhereHas('brand', fn (Builder $b) => $b->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('category', fn (Builder $c) => $c->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('variants', fn (Builder $v) => $v->where('name', 'like', "%{$search}%"))))
                    ->sortable()
                    ->weight('medium'),
                TextColumn::make('price')
                    ->label('Prezzo')
                    ->money('EUR')
                    ->sortable()
                    ->alignEnd(),
                // Modifica rapida dei pezzi; con le varianti la disponibilità è la loro somma
                // e si cambia dalla scheda del prodotto.
                TextInputColumn::make('stock')
                    ->label('Pezzi')
                    ->type('number')
                    ->rules(['required', 'integer', 'min:0', 'max:100000'])
                    ->disabled(fn ($record) => $record->variants_count > 0)
                    ->tooltip(fn ($record) => $record->variants_count > 0 ? 'Somma delle varianti: si modifica dentro il prodotto' : null)
                    ->sortable()
                    ->width('7rem'),
                TextColumn::make('sold_quantity')
                    ->label('Venduti')
                    ->numeric()
                    ->default(0)
                    ->sortable()
                    ->alignCenter()
                    ->toggleable(),
                ToggleColumn::make('is_active')
                    ->label('Attivo')
                    ->alignCenter(),
                TextColumn::make('created_at')
                    ->label('Creato il')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label('Ultima modifica')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('category_id')
                    ->label('Categoria')
                    ->relationship('category', 'name')
                    ->multiple()
                    ->preload(),
                SelectFilter::make('brand_id')
                    ->label('Marca')
                    ->relationship('brand', 'name')
                    ->multiple()
                    ->preload(),
                TernaryFilter::make('is_active')
                    ->label('Attivo'),
                TernaryFilter::make('has_variants')
                    ->label('Varianti')
                    ->trueLabel('Con varianti')
                    ->falseLabel('Senza varianti')
                    ->queries(
                        true: fn (Builder $q) => $q->has('variants'),
                        false: fn (Builder $q) => $q->doesntHave('variants'),
                    ),
                SelectFilter::make('incomplete')
                    ->label('Da completare')
                    ->placeholder('Tutti')
                    ->options([
                        'no_images' => 'Senza foto',
                        'no_brand' => 'Senza marca',
                        'no_category' => 'Senza categoria',
                    ])
                    ->query(fn (Builder $query, array $data) => match ($data['value'] ?? null) {
                        'no_images' => $query->where(fn (Builder $q) => $q->whereNull('images')->orWhereJsonLength('images', 0)),
                        'no_brand' => $query->whereNull('brand_id'),
                        'no_category' => $query->whereNull('category_id'),
                        default => $query,
                    }),
            ])
            ->recordActions([
                Action::make('viewInShop')
                    ->label('Vedi nel negozio')
                    ->tooltip('Apri la pagina del prodotto sul sito')
                    ->icon(Heroicon::ArrowTopRightOnSquare)
                    ->iconButton()
                    ->color('gray')
                    ->url(fn ($record) => rtrim(config('app.frontend_url'), '/').'/prodotti/'.$record->slug, shouldOpenInNewTab: true)
                    ->visible(fn ($record) => $record->is_active),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('activate')
                        ->label('Rendi visibili')
                        ->icon(Heroicon::Eye)
                        ->action(fn (Collection $records) => self::bulkUpdate($records, ['is_active' => true]))
                        ->deselectRecordsAfterCompletion(),
                    BulkAction::make('deactivate')
                        ->label('Nascondi')
                        ->icon(Heroicon::EyeSlash)
                        ->action(fn (Collection $records) => self::bulkUpdate($records, ['is_active' => false]))
                        ->deselectRecordsAfterCompletion(),
                    BulkAction::make('changeCategory')
                        ->label('Cambia categoria')
                        ->icon(Heroicon::Tag)
                        ->schema([
                            Select::make('category_id')
                                ->label('Nuova categoria')
                                ->options(fn () => Category::orderBy('name')->pluck('name', 'id'))
                                ->placeholder('Nessuna categoria')
                                ->searchable(),
                        ])
                        ->action(fn (Collection $records, array $data) => self::bulkUpdate($records, ['category_id' => $data['category_id'] ?? null]))
                        ->deselectRecordsAfterCompletion(),
                    BulkAction::make('changeBrand')
                        ->label('Cambia marca')
                        ->icon(Heroicon::Sparkles)
                        ->schema([
                            Select::make('brand_id')
                                ->label('Nuova marca')
                                ->options(fn () => Brand::orderBy('name')->pluck('name', 'id'))
                                ->placeholder('Nessuna marca')
                                ->searchable(),
                        ])
                        ->action(fn (Collection $records, array $data) => self::bulkUpdate($records, ['brand_id' => $data['brand_id'] ?? null]))
                        ->deselectRecordsAfterCompletion(),
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->searchPlaceholder('Cerca per nome, marca, categoria o variante')
            ->striped()
            ->emptyStateHeading('Nessun prodotto')
            ->emptyStateDescription('Carica il primo prodotto per popolare il catalogo.');
    }

    private static function bulkUpdate(Collection $records, array $values): void
    {
        // Uno alla volta (non con un'unica query) così restano attivi slug, sitemap ed eventi del modello.
        $records->each->update($values);

        Notification::make()
            ->title('Prodotti aggiornati: '.$records->count())
            ->success()
            ->send();
    }
}
