<?php

namespace App\Filament\Resources\Communications\Schemas;

use App\Models\Communication;
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
                    ->description(fn () => 'Verrà inviata via email a '.Communication::recipientEmails()->count().' destinatari (clienti registrati e clienti che hanno effettuato un ordine). L\'invio parte subito dopo il salvataggio.')
                    ->schema([
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
