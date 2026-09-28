<?php

namespace App\Filament\Resources\Communications\Schemas;

use App\Models\Communication;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CommunicationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Nuova comunicazione')
                    ->description('L\'invio parte subito dopo il salvataggio. Ogni cliente riceve una email singola.')
                    ->schema([
                        Radio::make('type')
                            ->label('Tipo di comunicazione')
                            ->options(Communication::TYPES)
                            ->descriptions(fn () => [
                                Communication::TYPE_MARKETING => 'Offerte, sconti, nuovi prodotti. Arriva solo ai clienti che hanno dato il consenso: '
                                    .Communication::recipients(Communication::TYPE_MARKETING)->count().' destinatari. Contiene il link per disiscriversi.',
                                Communication::TYPE_SERVICE => 'Solo avvisi importanti su account e ordini (es. modifica delle condizioni di vendita, ritardi nelle spedizioni, chiusura per ferie). Mai offerte: senza consenso è vietato. Arriva a tutti: '
                                    .Communication::recipients(Communication::TYPE_SERVICE)->count().' destinatari.',
                            ])
                            ->default(Communication::TYPE_MARKETING)
                            ->required(),
                        TextInput::make('subject')
                            ->label('Oggetto')
                            ->placeholder('Es. Nuova offerta di stagione!')
                            ->required()
                            ->maxLength(255),
                        RichEditor::make('body')
                            ->label('Contenuto')
                            ->placeholder('Scrivi qui il testo della comunicazione...')
                            ->required()
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
