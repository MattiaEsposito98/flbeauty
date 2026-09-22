<?php

namespace App\Filament\Resources\Products\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informazioni prodotto')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->label('Nome')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (string $state, callable $set) => $set('slug', Str::slug($state)))
                            ->columnSpan(1),
                        TextInput::make('slug')
                            ->label('Slug (URL)')
                            ->disabled()
                            ->dehydrated()
                            ->maxLength(255)
                            ->helperText('Generato automaticamente dal nome. In caso di doppioni viene reso unico da solo (es. matita-nera-2).')
                            ->columnSpan(1),
                        Select::make('category_id')
                            ->label('Categoria')
                            ->relationship('category', 'name', fn ($query) => $query->where('is_active', true))
                            ->searchable()
                            ->preload()
                            ->createOptionForm([
                                TextInput::make('name')
                                    ->label('Nome')
                                    ->required()
                                    ->maxLength(255)
                                    ->unique(table: \App\Models\Category::class)
                                    ->validationMessages([
                                        'unique' => 'Esiste già una categoria con questo nome.',
                                    ]),
                            ])
                            ->columnSpanFull(),
                        Textarea::make('description')
                            ->label('Descrizione')
                            ->rows(4)
                            ->columnSpanFull(),
                    ]),

                Section::make('Prezzo e disponibilità')
                    ->columns(3)
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
                            ->minValue(0),
                        Toggle::make('is_active')
                            ->label('Attivo')
                            ->helperText('Visibile nel negozio')
                            ->default(true)
                            ->inline(false),
                    ]),

                Section::make('Immagini e video')
                    ->schema([
                        FileUpload::make('images')
                            ->label('Immagini prodotto')
                            ->helperText('La prima immagine caricata sarà usata come copertina. Trascina per riordinare. Da telefono o tablet puoi anche scattare la foto al momento.')
                            ->image()
                            ->imageEditor()
                            ->multiple()
                            ->reorderable()
                            ->maxFiles(8)
                            ->directory('products/images')
                            ->visibility('public')
                            ->extraInputAttributes(['capture' => 'environment'])
                            ->columnSpanFull(),
                        FileUpload::make('video')
                            ->label('Video prodotto')
                            ->helperText('Facoltativo, es. un breve video dimostrativo del prodotto. Da telefono o tablet puoi anche registrarlo al momento.')
                            ->acceptedFileTypes(['video/mp4', 'video/quicktime', 'video/webm'])
                            ->directory('products/videos')
                            ->visibility('public')
                            ->extraInputAttributes(['capture' => 'environment'])
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
