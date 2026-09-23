<?php

namespace App\Filament\Resources\Discounts\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
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
                    ->description('Il codice che comunicherai al cliente per applicare lo sconto.')
                    ->columns(2)
                    ->schema([
                        TextInput::make('code')
                            ->label('Codice')
                            ->placeholder('Es. ESTATE10')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(50)
                            ->formatStateUsing(fn (?string $state) => $state ? Str::upper($state) : $state)
                            ->dehydrateStateUsing(fn (?string $state) => $state ? Str::upper($state) : $state)
                            ->validationMessages([
                                'unique' => 'Esiste già uno sconto con questo codice.',
                            ]),
                        Toggle::make('is_active')
                            ->label('Sconto attivo')
                            ->helperText('Se disattivato non è utilizzabile.')
                            ->default(true)
                            ->inline(false),
                        Textarea::make('description')
                            ->label('Descrizione')
                            ->placeholder('Facoltativa, es. "Sconto di benvenuto per nuovi clienti".')
                            ->rows(2)
                            ->columnSpanFull(),
                    ]),

                Section::make('Valore e validità')
                    ->columns(2)
                    ->schema([
                        Select::make('type')
                            ->label('Tipo di sconto')
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
                        DateTimePicker::make('starts_at')
                            ->label('Valido dal')
                            ->native(false)
                            ->displayFormat('d/m/Y H:i')
                            ->helperText('Lascia vuoto per renderlo valido da subito.'),
                        DateTimePicker::make('ends_at')
                            ->label('Valido fino al')
                            ->native(false)
                            ->displayFormat('d/m/Y H:i')
                            ->after('starts_at')
                            ->helperText('Lascia vuoto per non farlo scadere.'),
                    ]),
            ]);
    }
}
