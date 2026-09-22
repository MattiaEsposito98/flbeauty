<?php

namespace App\Filament\Resources\Discounts\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class DiscountForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Codice sconto')
                    ->columns(2)
                    ->schema([
                        TextInput::make('code')
                            ->label('Codice')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(50)
                            ->formatStateUsing(fn (?string $state) => $state ? Str::upper($state) : $state)
                            ->dehydrateStateUsing(fn (?string $state) => $state ? Str::upper($state) : $state)
                            ->helperText('Il codice che l\'admin comunicherà al cliente, es. SUMMER10'),
                        TextInput::make('description')
                            ->label('Descrizione')
                            ->maxLength(255),
                    ]),

                Section::make('Valore dello sconto')
                    ->columns(2)
                    ->schema([
                        Select::make('type')
                            ->label('Tipo')
                            ->options([
                                'percentuale' => 'Percentuale (%)',
                                'fisso' => 'Importo fisso (€)',
                            ])
                            ->default('percentuale')
                            ->required()
                            ->live(),
                        TextInput::make('value')
                            ->label('Valore')
                            ->required()
                            ->numeric()
                            ->minValue(0)
                            ->suffix(fn (callable $get) => $get('type') === 'fisso' ? '€' : '%'),
                    ]),

                Section::make('Validità')
                    ->columns(3)
                    ->schema([
                        DateTimePicker::make('starts_at')
                            ->label('Data inizio')
                            ->native(false),
                        DateTimePicker::make('ends_at')
                            ->label('Data fine')
                            ->native(false)
                            ->after('starts_at'),
                        Toggle::make('is_active')
                            ->label('Attivo')
                            ->default(true)
                            ->inline(false),
                    ]),
            ]);
    }
}
