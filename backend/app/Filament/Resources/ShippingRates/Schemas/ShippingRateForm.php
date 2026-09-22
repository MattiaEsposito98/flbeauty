<?php

namespace App\Filament\Resources\ShippingRates\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ShippingRateForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Metodo di spedizione')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->label('Nome')
                            ->required()
                            ->maxLength(255)
                            ->helperText('Es. il nome della regione, oppure "Punto di ritiro"'),
                        Select::make('type')
                            ->label('Tipo')
                            ->options([
                                'regione' => 'Spedizione regionale',
                                'ritiro' => 'Punto di ritiro',
                            ])
                            ->default('regione')
                            ->required(),
                        TextInput::make('price')
                            ->label('Prezzo')
                            ->required()
                            ->numeric()
                            ->prefix('€')
                            ->step(0.01)
                            ->minValue(0),
                        Toggle::make('is_active')
                            ->label('Attivo')
                            ->helperText('Selezionabile dal cliente in fase di ordine')
                            ->default(true)
                            ->inline(false),
                    ]),
            ]);
    }
}
