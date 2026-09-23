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
                    ->description('Il costo verrà sommato al totale dell\'ordine quando il cliente sceglie questo metodo.')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->label('Nome')
                            ->placeholder('Es. Lombardia, oppure Punto di ritiro')
                            ->required()
                            ->maxLength(255),
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
                            ->helperText('Selezionabile dal cliente in fase di ordine.')
                            ->default(true)
                            ->inline(false),
                    ]),
            ]);
    }
}
