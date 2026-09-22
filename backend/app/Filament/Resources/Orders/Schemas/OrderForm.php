<?php

namespace App\Filament\Resources\Orders\Schemas;

use App\Models\Product;
use App\Models\ShippingRate;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class OrderForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Cliente')
                    ->columns(2)
                    ->schema([
                        TextInput::make('customer_name')
                            ->label('Nome cliente')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('customer_email')
                            ->label('Email cliente')
                            ->email()
                            ->required()
                            ->maxLength(255),
                        TextInput::make('customer_phone')
                            ->label('Telefono')
                            ->tel(),
                        Select::make('user_id')
                            ->label('Utente registrato')
                            ->relationship('user', 'name')
                            ->searchable()
                            ->preload()
                            ->helperText('Solo se l\'ordine proviene da un utente registrato'),
                    ]),

                Section::make('Articoli ordine')
                    ->schema([
                        Repeater::make('items')
                            ->label('Prodotti')
                            ->relationship()
                            ->live()
                            ->columns(3)
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
                                    }),
                                TextInput::make('quantity')
                                    ->label('Quantità')
                                    ->numeric()
                                    ->default(1)
                                    ->minValue(1)
                                    ->required()
                                    ->live(),
                                TextInput::make('unit_price')
                                    ->label('Prezzo unitario')
                                    ->numeric()
                                    ->prefix('€')
                                    ->required()
                                    ->live(),
                            ])
                            ->addActionLabel('Aggiungi prodotto')
                            ->defaultItems(1)
                            ->columnSpanFull(),

                        Placeholder::make('subtotal_preview')
                            ->label('Subtotale articoli')
                            ->content(fn (callable $get) => self::formatEuro(self::itemsSubtotal($get))),
                    ]),

                Section::make('Spedizione')
                    ->columns(2)
                    ->schema([
                        Select::make('shipping_rate_id')
                            ->label('Metodo di spedizione')
                            ->relationship('shippingRate', 'name', fn ($query) => $query->where('is_active', true))
                            ->searchable()
                            ->preload()
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
                            ->live()
                            ->helperText('Precompilato in base al metodo scelto, puoi modificarlo.'),
                    ]),

                Section::make('Sconto, stato e totale')
                    ->columns(2)
                    ->schema([
                        Select::make('discount_id')
                            ->label('Codice sconto')
                            ->relationship('discount', 'code')
                            ->searchable()
                            ->preload()
                            ->live(),
                        Select::make('status')
                            ->label('Stato ordine')
                            ->options([
                                'nuovo' => 'Nuovo',
                                'in_lavorazione' => 'In lavorazione',
                                'evaso' => 'Evaso',
                                'annullato' => 'Annullato',
                            ])
                            ->default('nuovo')
                            ->required(),
                        Placeholder::make('total_preview')
                            ->label('Totale ordine (articoli + spedizione, calcolato al salvataggio)')
                            ->content(function (callable $get) {
                                $total = self::itemsSubtotal($get) + (float) ($get('shipping_cost') ?? 0);

                                return self::formatEuro($total);
                            })
                            ->columnSpanFull(),
                        Textarea::make('notes')
                            ->label('Note')
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),
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
