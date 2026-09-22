<?php

namespace App\Filament\Resources\Products\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
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
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255)
                            ->helperText('Generato automaticamente dal nome, puoi modificarlo.')
                            ->columnSpan(1),
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

                Section::make('Immagine')
                    ->schema([
                        FileUpload::make('image')
                            ->label('Immagine prodotto')
                            ->image()
                            ->imageEditor()
                            ->directory('products')
                            ->visibility('public')
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
