<?php

namespace App\Filament\Resources\Orders\Schemas;

use App\Models\Order;
use App\Models\Product;
use App\Models\ShippingRate;
use App\Models\User;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
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
                                ->maxLength(255)
                                ->live(onBlur: true)
                                ->helperText(fn (callable $get) => self::registeredEmailHint($get))
                                // Un ospite non può avere l'email di un cliente registrato:
                                // l'ordine va collegato al suo account, altrimenti non lo vede.
                                ->rule(fn (callable $get) => function (string $attribute, $value, \Closure $fail) use ($get) {
                                    $owner = self::customerWithEmail($value);

                                    if ($owner && blank($get('user_id'))) {
                                        $fail("Questa email appartiene al cliente registrato {$owner->name}: selezionalo in \"Account registrato\" invece di creare l'ordine come ospite.");
                                    }
                                }),
                            TextInput::make('customer_phone')
                                ->label('Telefono')
                                ->tel()
                                ->helperText('Serve per inviare il riepilogo su WhatsApp.'),
                            Select::make('user_id')
                                ->label('Account registrato')
                                ->relationship('user', 'name', fn ($query) => $query->where('is_admin', false))
                                ->searchable(['name', 'username', 'email'])
                                ->preload()
                                ->placeholder('Ordine da ospite')
                                ->helperText('Lascia vuoto per un ospite. Scegliendo un cliente registrato, i suoi dati si compilano da soli.')
                                ->live()
                                ->afterStateUpdated(function ($state, callable $set) {
                                    $user = User::with('defaultAddress.comune')->find($state);

                                    if (! $user) {
                                        return;
                                    }

                                    $address = $user->defaultAddress;

                                    $set('customer_name', $user->name);
                                    $set('customer_email', $user->email);
                                    $set('customer_phone', $address?->phone);
                                    $set('shipping_address_line', $address?->address_line);
                                    $set('shipping_postal_code', $address?->postal_code);
                                    $set('shipping_city', $address?->comune?->name);
                                    $set('shipping_province', $address?->province);
                                }),
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
                                        // Disponibilità accanto al nome; gli esauriti non si
                                        // possono scegliere (a meno che siano già nella riga).
                                        ->getOptionLabelFromRecordUsing(fn (Product $record) => $record->name.' · '
                                            .($record->stock > 0 ? $record->stock.' disponibili' : 'esaurito'))
                                        ->disableOptionWhen(fn ($value, $state) => (string) $value !== (string) $state
                                            && (self::stockMap()[$value] ?? 0) <= 0)
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
                                        ->live(onBlur: true)
                                        ->helperText(fn (callable $get, $livewire) => self::availabilityHint($get, $livewire))
                                        ->rule(fn (callable $get, $livewire) => function (string $attribute, $value, \Closure $fail) use ($get, $livewire) {
                                            $check = self::availabilityCheck($get, $livewire);

                                            if ($check && $check['requested'] > $check['available']) {
                                                $fail("Disponibili solo {$check['available']} pezzi (richiesti {$check['requested']} in totale).");
                                            }
                                        })
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
                                // Poste Italiane già selezionato: nei nuovi ordini (default) e
                                // negli ordini arrivati dal sito, che nascono senza corriere.
                                ->default(Order::DEFAULT_CARRIER)
                                ->afterStateHydrated(function ($component, $state) {
                                    if (blank($state)) {
                                        $component->state(Order::DEFAULT_CARRIER);
                                    }
                                })
                                ->helperText('Con Poste Italiane o SDA il link di tracking si crea da solo dal numero.'),
                            TextInput::make('tracking_number')
                                ->label('Numero di tracking')
                                ->maxLength(255)
                                ->live(onBlur: true)
                                ->helperText('Visibile al cliente nella pagina dell\'ordine e inviato via email quando l\'ordine è evaso.'),
                            TextInput::make('tracking_url')
                                ->label('Link tracking')
                                ->url()
                                ->maxLength(2048)
                                ->placeholder(fn (callable $get) => filled($get('tracking_number')) && isset(Order::CARRIER_TRACKING_URLS[$get('carrier')])
                                    ? 'Automatico: pagina Poste della spedizione '.$get('tracking_number')
                                    : 'https://...')
                                ->helperText('Per Poste/SDA lascialo vuoto: il cliente riceve il link diretto alla spedizione. Compilalo solo per altri corrieri o per un link diverso.')
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
                            // Solo alla creazione: non viene salvato nell'ordine (dehydrated false),
                            // lo legge CreateOrder::afterCreate().
                            Toggle::make('send_confirmation_email')
                                ->label('Invia email di conferma al cliente')
                                ->helperText('Con il riepilogo dell\'ordine, all\'email indicata sopra.')
                                ->default(true)
                                ->dehydrated(false)
                                ->visibleOn('create'),
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

    /**
     * Cliente registrato con questa email (lo staff non conta: non è un cliente).
     */
    protected static function customerWithEmail(?string $email): ?User
    {
        $user = User::findByEmail($email);

        return $user && ! $user->is_admin ? $user : null;
    }

    protected static function registeredEmailHint(callable $get): ?HtmlString
    {
        $owner = blank($get('user_id')) ? self::customerWithEmail($get('customer_email')) : null;

        return $owner
            ? new HtmlString('<span style="color: var(--danger-600)">Email del cliente registrato '.e($owner->name).': selezionalo in "Account registrato".</span>')
            : null;
    }

    /** @var array<int, int>|null */
    private static ?array $stockMap = null;

    /**
     * Magazzino di tutti i prodotti, letto una volta per richiesta (serve per
     * disattivare gli esauriti nell'elenco senza una query per opzione).
     *
     * @return array<int, int>
     */
    protected static function stockMap(): array
    {
        return self::$stockMap ??= Product::query()->pluck('stock', 'id')->map(fn ($stock) => (int) $stock)->all();
    }

    /**
     * Disponibilità del prodotto della riga, contando tutte le righe dello
     * stesso prodotto. In modifica, i pezzi che l'ordine tiene già riservati
     * si sommano al magazzino (sono "suoi"). Stessa regola di ChecksOrderStock.
     *
     * @return array{name: string, available: int, requested: int}|null
     */
    protected static function availabilityCheck(callable $get, $livewire): ?array
    {
        $productId = $get('product_id');

        if (blank($productId) || $get('../../status') === Order::STATUS_CANCELLED) {
            return null;
        }

        $product = Product::find($productId);

        if (! $product) {
            return null;
        }

        $order = $livewire->record ?? null;
        $reserved = $order instanceof Order && $order->exists && $order->reservesStock()
            ? (int) $order->items()->where('product_id', $productId)->sum('quantity')
            : 0;

        $requested = collect($get('../../items') ?? [])
            ->where('product_id', $productId)
            ->sum(fn ($item) => (int) ($item['quantity'] ?? 0));

        return [
            'name' => $product->name,
            'available' => $product->stock + $reserved,
            'requested' => $requested,
        ];
    }

    protected static function availabilityHint(callable $get, $livewire): ?HtmlString
    {
        $check = self::availabilityCheck($get, $livewire);

        if (! $check) {
            return null;
        }

        if ($check['requested'] > $check['available']) {
            return new HtmlString('<span style="color: var(--danger-600)">Disponibili solo '.$check['available'].'</span>');
        }

        return new HtmlString('Disponibili: '.$check['available']);
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
