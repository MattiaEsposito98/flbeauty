<?php

namespace App\Filament\Resources\Orders\Schemas;

use App\Models\Order;
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

                    Section::make('Indirizzo di spedizione')
                        ->compact()
                        ->columns(6)
                        ->schema([
                            TextInput::make('shipping_address_line')
                                ->label('Via e civico')
                                ->maxLength(255)
                                ->columnSpan(6),
                            TextInput::make('shipping_postal_code')
                                ->label('CAP')
                                ->maxLength(10)
                                ->columnSpan(2),
                            TextInput::make('shipping_city')
                                ->label('Comune')
                                ->maxLength(255)
                                ->columnSpan(3),
                            TextInput::make('shipping_province')
                                ->label('Prov.')
                                ->maxLength(5)
                                ->columnSpan(1),
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

                    // Qui e non a destra: nella colonna laterale allungava la pagina
                    // e spingeva i pulsanti in fondo.
                    Section::make('Spedizione e tracking')
                        ->compact()
                        ->columns(['default' => 1, 'md' => 2])
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
                            Select::make('carrier')
                                ->label('Corriere')
                                ->options(array_combine(Order::CARRIERS, Order::CARRIERS))
                                ->placeholder('Nessuno')
                                ->native(false)
                                ->live()
                                // Compila il link del corriere, senza sovrascrivere un link
                                // inserito a mano (solo se vuoto o se è quello di un altro corriere).
                                ->afterStateUpdated(function ($state, callable $get, callable $set) {
                                    $current = $get('tracking_url');

                                    if (blank($current) || in_array($current, Order::CARRIER_TRACKING_PAGES, true)) {
                                        $set('tracking_url', Order::CARRIER_TRACKING_PAGES[$state] ?? null);
                                    }
                                })
                                ->helperText('Con Poste Italiane o SDA il link di tracking si compila da solo.'),
                            TextInput::make('tracking_number')
                                ->label('Numero di tracking')
                                ->maxLength(255)
                                ->helperText('Visibile al cliente nella pagina dell\'ordine e inviato via email quando l\'ordine è evaso.'),
                            TextInput::make('tracking_url')
                                ->label('Link tracking')
                                ->url()
                                ->maxLength(2048)
                                ->placeholder('https://...')
                                ->helperText('Compilato in automatico per Poste/SDA; puoi sostituirlo con un link specifico.')
                                ->columnSpanFull(),
                        ]),
                ])->columnSpan(['lg' => 2]),

                Group::make([
                    // In cima alla colonna perché è il campo che si cambia più spesso.
                    Section::make('Stato e note')
                        ->compact()
                        ->schema([
                            Select::make('status')
                                ->label('Stato ordine')
                                ->options([
                                    'nuovo' => 'In attesa di pagamento',
                                    'in_lavorazione' => 'In lavorazione · pagato',
                                    'evaso' => 'Evaso · spedito',
                                    'annullato' => 'Annullato',
                                ])
                                ->default('nuovo')
                                ->required()
                                ->native(false)
                                ->helperText('I pezzi restano scalati dal magazzino finché l\'ordine non è annullato: annullandolo tornano disponibili, riattivandolo vengono riscalati.'),
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
