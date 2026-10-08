<?php

namespace App\Filament\Resources\Products\Schemas;

use App\Models\Category;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            // Il form radice di Filament è a 2 colonne: senza questo, l'intero
            // layout finirebbe compresso dentro una sola metà della pagina.
            ->columns(['default' => 1, 'lg' => 3])
            ->components([
                Group::make([
                    Section::make('Informazioni prodotto')
                        ->compact()
                        ->columns(2)
                        ->schema([
                            TextInput::make('name')
                                ->label('Nome')
                                ->placeholder('Es. Fondotinta effetto seta')
                                ->required()
                                ->maxLength(255)
                                ->live(onBlur: true)
                                ->afterStateUpdated(fn (string $state, callable $set) => $set('slug', Str::slug($state))),
                            Select::make('category_id')
                                ->label('Categoria')
                                ->relationship('category', 'name', fn ($query) => $query->where('is_active', true))
                                ->searchable()
                                ->preload()
                                ->placeholder('Nessuna categoria')
                                ->createOptionForm([
                                    TextInput::make('name')
                                        ->label('Nome')
                                        ->required()
                                        ->maxLength(255)
                                        ->unique(table: Category::class)
                                        ->validationMessages([
                                            'unique' => 'Esiste già una categoria con questo nome.',
                                        ]),
                                ]),
                            Textarea::make('description')
                                ->label('Descrizione')
                                ->rows(4)
                                ->columnSpanFull(),
                        ]),

                    Section::make('Immagini e video')
                        ->compact()
                        ->schema([
                            FileUpload::make('images')
                                ->label('Immagini prodotto')
                                ->helperText('La prima immagine sarà la copertina. Trascina per riordinare.')
                                ->disk('public')
                                ->image()
                                ->imageEditor()
                                // Foto del telefono da 4-8 MB: le riduciamo a max 1600 px, così le
                                // pagine si caricano in fretta (conta per Google e per i clienti).
                                ->imageResizeMode('contain')
                                ->imageResizeTargetWidth('1600')
                                ->imageResizeTargetHeight('1600')
                                ->imageResizeUpscale(false)
                                ->multiple()
                                ->reorderable()
                                ->maxFiles(8)
                                ->directory('products/images')
                                ->visibility('public')
                                ->panelLayout('grid')
                                ->columnSpanFull(),
                            FileUpload::make('video')
                                ->label('Video prodotto (facoltativo)')
                                ->helperText('Formato MP4, MOV o WebM. Massimo 100 MB.')
                                ->disk('public')
                                ->acceptedFileTypes(['video/mp4', 'video/quicktime', 'video/webm'])
                                ->maxSize(102400)
                                ->directory('products/videos')
                                ->visibility('public')
                                ->columnSpanFull(),
                            Grid::make(2)
                                ->extraAttributes(['class' => 'fl-mobile-only'])
                                ->schema([
                                    FileUpload::make('camera_capture')
                                        ->label('📷 Scatta una foto')
                                        ->helperText('Si aggiunge alla galleria sopra.')
                                        ->disk('public')
                                        ->image()
                                        ->imageEditor()
                                        ->imageResizeMode('contain')
                                        ->imageResizeTargetWidth('1600')
                                        ->imageResizeTargetHeight('1600')
                                        ->imageResizeUpscale(false)
                                        ->extraInputAttributes(['capture' => 'environment'])
                                        ->directory('products/images')
                                        ->visibility('public')
                                        ->live()
                                        ->dehydrated(false)
                                        ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                            if (blank($state)) {
                                                return;
                                            }

                                            $set('images', [...($get('images') ?? []), $state]);
                                            $set('camera_capture', null);
                                        }),
                                    FileUpload::make('camera_capture_video')
                                        ->label('🎥 Registra un video')
                                        ->helperText('Sostituisce il video sopra. Massimo 100 MB.')
                                        ->disk('public')
                                        ->acceptedFileTypes(['video/mp4', 'video/quicktime', 'video/webm'])
                                        ->maxSize(102400)
                                        ->extraInputAttributes(['capture' => 'environment'])
                                        ->directory('products/videos')
                                        ->visibility('public')
                                        ->live()
                                        ->dehydrated(false)
                                        ->afterStateUpdated(function ($state, callable $set) {
                                            if (blank($state)) {
                                                return;
                                            }

                                            $set('video', $state);
                                            $set('camera_capture_video', null);
                                        }),
                                ]),
                        ]),

                    Section::make('Varianti (colori, profumi, formati…)')
                        ->compact()
                        ->description('Facoltativo. Se il prodotto esiste in più versioni (es. rossetto rosso, verde, giallo) aggiungi una riga per versione: la disponibilità si gestisce su ciascuna, e il cliente sceglie la variante prima di ordinare.')
                        ->schema([
                            TextInput::make('variant_label')
                                ->label('Cosa cambia tra le varianti?')
                                ->placeholder('Es. Colore, Profumo, Tonalità')
                                ->helperText('È il titolo della scelta che vede il cliente. Se lasci vuoto compare "Variante".')
                                ->maxLength(50),
                            Repeater::make('variants')
                                ->hiddenLabel()
                                ->relationship()
                                ->orderColumn('sort_order')
                                ->reorderable()
                                ->collapsible()
                                ->columns(6)
                                ->addActionLabel('Aggiungi variante')
                                ->defaultItems(0)
                                ->itemLabel(fn (array $state): ?string => filled($state['name'] ?? null)
                                    ? $state['name'].' · '.((int) ($state['stock'] ?? 0) > 0 ? (int) $state['stock'].' pz' : 'esaurito')
                                        .(($state['is_active'] ?? true) ? '' : ' · nascosta')
                                    : 'Nuova variante')
                                ->schema([
                                    TextInput::make('name')
                                        ->label('Nome')
                                        ->placeholder('Es. Rosso ciliegia')
                                        ->required()
                                        ->distinct()
                                        ->maxLength(255)
                                        ->live(onBlur: true)
                                        ->columnSpan(3),
                                    TextInput::make('stock')
                                        ->label('Quantità')
                                        ->required()
                                        ->numeric()
                                        ->default(0)
                                        ->minValue(0)
                                        ->suffix('pz')
                                        ->live(onBlur: true)
                                        ->columnSpan(1),
                                    TextInput::make('price')
                                        ->label('Prezzo suo (facoltativo)')
                                        ->numeric()
                                        ->prefix('€')
                                        ->step(0.01)
                                        ->minValue(0)
                                        ->placeholder('Come il prodotto')
                                        ->columnSpan(2),
                                    FileUpload::make('image')
                                        ->label('Foto della variante (facoltativa)')
                                        ->helperText('Quando il cliente sceglie questa variante, la foto cambia.')
                                        ->disk('public')
                                        ->image()
                                        ->imageEditor()
                                        ->imageResizeMode('contain')
                                        ->imageResizeTargetWidth('1600')
                                        ->imageResizeTargetHeight('1600')
                                        ->imageResizeUpscale(false)
                                        ->directory('products/variants')
                                        ->visibility('public')
                                        ->columnSpan(4),
                                    Toggle::make('is_active')
                                        ->label('Visibile nel negozio')
                                        ->helperText('Spegnila per nasconderla senza eliminarla.')
                                        ->default(true)
                                        ->live()
                                        ->columnSpan(2),
                                ]),
                        ]),
                ])->columnSpan(['lg' => 2]),

                Group::make([
                    Section::make('Prezzo e disponibilità')
                        ->compact()
                        ->schema([
                            TextInput::make('price')
                                ->label('Prezzo')
                                ->required()
                                ->numeric()
                                ->prefix('€')
                                ->step(0.01)
                                ->minValue(0),
                            TextInput::make('stock')
                                ->label('Quantità disponibile')
                                ->required()
                                ->numeric()
                                ->default(0)
                                ->minValue(0)
                                ->suffix('pz')
                                // Con le varianti la quantità è la somma delle loro: si calcola da sola.
                                ->disabled(fn (Get $get) => collect($get('variants') ?? [])->isNotEmpty())
                                ->dehydrated(fn (Get $get) => collect($get('variants') ?? [])->isEmpty())
                                ->helperText(fn (Get $get) => collect($get('variants') ?? [])->isNotEmpty()
                                    ? 'Calcolata dalle varianti: gestisci le quantità lì.'
                                    : null),
                        ]),

                    Section::make('Pubblicazione')
                        ->compact()
                        ->schema([
                            Toggle::make('is_active')
                                ->label('Prodotto attivo')
                                ->helperText('Visibile nel negozio')
                                ->default(true),
                            TextInput::make('slug')
                                ->label('Slug (URL)')
                                ->disabled()
                                ->dehydrated()
                                ->maxLength(255)
                                ->helperText('Generato automaticamente, sempre unico.'),
                        ]),
                ])->columnSpan(['lg' => 1]),
            ]);
    }
}
