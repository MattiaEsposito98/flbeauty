<?php

namespace App\Filament\Resources\Orders\Schemas;

use App\Models\Product;
use App\Models\ShippingRate;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;

class OrderForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            // Il form radice di Filament è a 2 colonne: senza questo, l'intero
            // layout finirebbe compresso dentro una sola metà della pagina.
            ->columns(['default' => 1, 'lg' => 3])
            ->components([
                Group::make([
                    Section::make('Cliente')
                        ->compact()
                        ->columns(2)
                        ->schema([
                            TextInput::make('customer_name')
                                ->label('Nome e cognome')
                                ->required()
                                ->maxLength(255),
                            TextInput::make('customer_email')
                                ->label('Email')
                                ->email()
                                ->required()
                                ->maxLength(255),
                            TextInput::make('customer_phone')
                                ->label('Telefono')
                                ->tel(),
                            Select::make('user_id')
                                ->label('Account registrato')
                                ->relationship('user', 'name')
                                ->searchable()
                                ->preload()
                                ->placeholder('Ordine da ospite')
                                ->helperText('Solo se l\'ordine arriva da un utente registrato.'),
                        ]),

                    Section::make('Articoli ordinati')
                        ->compact()
                        ->schema([
                            Repeater::make('items')
                                ->hiddenLabel()
                                ->relationship()
                                ->live()
                                ->columns(6)
                                ->schema([
                                    Select::make('product_id')
                                        ->label('Prodotto')
                                        ->relationship('product', 'name')
                                        ->searchable()
                                        ->preload()
                                        ->required()
                                        ->live()
                                        ->afterStateUpdated(function ($state, callable $set) {
                                            $set('unit_price', Product::find($state)?->price ?? 0);
                                        })
                                        ->columnSpan(3),
                                    TextInput::make('quantity')
                                        ->label('Qtà')
                                        ->numeric()
                                        ->default(1)
                                        ->minValue(1)
                                        ->required()
                                        ->live()
                                        ->columnSpan(1),
                                    TextInput::make('unit_price')
                                        ->label('Prezzo unitario')
                                        ->numeric()
                                        ->prefix('€')
                                        ->required()
                                        ->live()
                                        ->columnSpan(2),
                                ])
                                ->addActionLabel('Aggiungi prodotto')
                                ->defaultItems(1)
                                ->itemLabel(fn (array $state): ?string => filled($state['product_id'] ?? null)
                                    ? Product::find($state['product_id'])?->name
                                    : null),
                        ]),
                ])->columnSpan(['lg' => 2]),

                Group::make([
                    Section::make('Riepilogo')
                        ->compact()
                        ->schema([
                            Placeholder::make('subtotal_preview')
                                ->label('Subtotale articoli')
                                ->content(fn (callable $get) => self::formatEuro(self::itemsSubtotal($get))),
                            Placeholder::make('total_preview')
                                ->label('Totale ordine')
                                ->content(function (callable $get) {
                                    $total = self::itemsSubtotal($get) + (float) ($get('shipping_cost') ?? 0);

                                    return new HtmlString(
                                        '<span class="fl-total">'.self::formatEuro($total).'</span>'
                                    );
                                })
                                ->helperText('Articoli + spedizione. Ricalcolato al salvataggio.'),
                        ]),

                    Section::make('Spedizione')
                        ->compact()
                        ->schema([
                            Select::make('shipping_rate_id')
                                ->label('Metodo')
                                ->relationship('shippingRate', 'name', fn ($query) => $query->where('is_active', true))
                                ->searchable()
                                ->preload()
                                ->placeholder('Nessuna spedizione')
                                ->live()
                                ->afterStateUpdated(function ($state, callable $set) {
                                    $set('shipping_cost', ShippingRate::find($state)?->price ?? 0);
                                }),
                            TextInput::make('shipping_cost')
                                ->label('Costo spedizione')
                                ->numeric()
                                ->prefix('€')
                                ->default(0)
                                ->required()
                                ->live(),
                        ]),

                    Section::make('Stato e note')
                        ->compact()
                        ->schema([
                            Select::make('status')
                                ->label('Stato ordine')
                                ->options([
                                    'nuovo' => 'Nuovo',
                                    'in_lavorazione' => 'In lavorazione',
                                    'evaso' => 'Evaso',
                                    'annullato' => 'Annullato',
                                ])
                                ->default('nuovo')
                                ->required()
                                ->native(false),
                            Select::make('discount_id')
                                ->label('Codice sconto')
                                ->relationship('discount', 'code')
                                ->searchable()
                                ->preload()
                                ->placeholder('Nessuno'),
                            Textarea::make('notes')
                                ->label('Note interne')
                                ->rows(3),
                        ]),
                ])->columnSpan(['lg' => 1]),
            ]);
    }

    protected static function itemsSubtotal(callable $get): float
    {
        $items = $get('items') ?? [];

        return collect($items)->sum(
            fn ($item) => (float) ($item['quantity'] ?? 0) * (float) ($item['unit_price'] ?? 0)
        );
    }

    protected static function formatEuro(float $value): string
    {
        return number_format($value, 2, ',', '.').' €';
    }
}
