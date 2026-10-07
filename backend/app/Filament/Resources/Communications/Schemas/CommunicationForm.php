<?php

namespace App\Filament\Resources\Communications\Schemas;

use App\Models\Communication;
use App\Models\User;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
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
                            ->live()
                            ->required(),
                        Radio::make('audience')
                            ->label('A chi inviarla')
                            ->options([
                                Communication::AUDIENCE_ALL => 'Tutti i clienti',
                                Communication::AUDIENCE_SELECTED => 'Solo i clienti che scelgo io',
                            ])
                            ->default(Communication::AUDIENCE_ALL)
                            ->live()
                            ->visible(fn (Get $get) => $get('type') === Communication::TYPE_SERVICE)
                            ->required(fn (Get $get) => $get('type') === Communication::TYPE_SERVICE),
                        Select::make('user_ids')
                            ->label('Clienti destinatari')
                            ->helperText('Cerca per nome, username o email. Gli avvisi di servizio arrivano a questi clienti anche se non hanno dato il consenso alle offerte; la comunicazione non deve contenere promozioni.')
                            ->multiple()
                            ->searchable()
                            ->preload()
                            ->options(fn () => User::query()
                                ->where('is_admin', false)
                                ->whereNotNull('email')
                                ->orderBy('name')
                                ->get()
                                ->mapWithKeys(fn (User $user) => [$user->id => "{$user->name} (@{$user->username}) — {$user->email}"])
                                ->all())
                            ->visible(fn (Get $get) => $get('type') === Communication::TYPE_SERVICE
                                && $get('audience') === Communication::AUDIENCE_SELECTED)
                            ->required(fn (Get $get) => $get('type') === Communication::TYPE_SERVICE
                                && $get('audience') === Communication::AUDIENCE_SELECTED),
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
