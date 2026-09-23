<?php

namespace App\Filament\Resources\Products\Schemas;

use App\Models\Category;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
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
                                ->image()
                                ->imageEditor()
                                ->multiple()
                                ->reorderable()
                                ->maxFiles(8)
                                ->directory('products/images')
                                ->visibility('public')
                                ->panelLayout('grid')
                                ->columnSpanFull(),
                            FileUpload::make('video')
                                ->label('Video prodotto (facoltativo)')
                                ->acceptedFileTypes(['video/mp4', 'video/quicktime', 'video/webm'])
                                ->directory('products/videos')
                                ->visibility('public')
                                ->columnSpanFull(),
                            Grid::make(2)
                                ->extraAttributes(['class' => 'fl-mobile-only'])
                                ->schema([
                                    FileUpload::make('camera_capture')
                                        ->label('📷 Scatta una foto')
                                        ->helperText('Si aggiunge alla galleria sopra.')
                                        ->image()
                                        ->imageEditor()
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
                                        ->helperText('Sostituisce il video sopra.')
                                        ->acceptedFileTypes(['video/mp4', 'video/quicktime', 'video/webm'])
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
                                ->suffix('pz'),
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
