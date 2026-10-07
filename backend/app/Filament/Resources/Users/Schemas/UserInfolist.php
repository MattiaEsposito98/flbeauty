<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Filament\Resources\Orders\OrderResource;
use App\Models\User;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class UserInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                Section::make('Profilo')
                    ->icon('heroicon-o-user')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('name')->label('Nome e cognome')->weight('bold'),
                        TextEntry::make('username')->label('Username')->prefix('@'),
                        TextEntry::make('email')->label('Email')->copyable()->copyMessage('Email copiata'),
                        TextEntry::make('defaultAddress.phone')->label('Telefono')->placeholder('—')->copyable(),
                        TextEntry::make('email_verified_at')
                            ->label('Email verificata')
                            ->dateTime('d/m/Y H:i')
                            ->placeholder('Non ancora verificata')
                            ->color(fn (User $record) => $record->hasVerifiedEmail() ? null : 'warning'),
                        TextEntry::make('created_at')->label('Registrato il')->dateTime('d/m/Y H:i'),
                    ]),

                Section::make('Account bloccato')
                    ->icon('heroicon-o-no-symbol')
                    ->iconColor('danger')
                    ->columnSpan(['lg' => 3])
                    ->columns(2)
                    ->visible(fn (User $record) => $record->isBlocked())
                    ->schema([
                        TextEntry::make('blocked_at')->label('Bloccato il')->dateTime('d/m/Y H:i')->color('danger'),
                        TextEntry::make('blocked_reason')->label('Motivo')->placeholder('Nessun motivo indicato'),
                    ]),

                Section::make('Attività')
                    ->icon('heroicon-o-chart-bar')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('login_count')->label('Accessi al sito'),
                        TextEntry::make('last_login_at')
                            ->label('Ultimo accesso')
                            ->since()
                            ->dateTimeTooltip('d/m/Y H:i')
                            ->placeholder('Mai'),
                        TextEntry::make('orders_count')->label('Ordini'),
                        TextEntry::make('spent')->label('Totale speso')->money('EUR')->placeholder('0,00 €'),
                    ]),

                Section::make('Privacy e consensi')
                    ->icon('heroicon-o-shield-check')
                    ->schema([
                        TextEntry::make('privacy_accepted_at')
                            ->label('Informativa privacy accettata')
                            ->dateTime('d/m/Y H:i')
                            ->placeholder('Registrato prima dell\'informativa'),
                        TextEntry::make('marketing_consent')
                            ->label('Vuole ricevere offerte via email')
                            ->badge()
                            ->formatStateUsing(fn (bool $state) => $state ? 'Sì' : 'No')
                            ->color(fn (bool $state) => $state ? 'success' : 'gray'),
                        TextEntry::make('marketing_consent_at')
                            ->label('Ultima modifica del consenso')
                            ->dateTime('d/m/Y H:i')
                            ->placeholder('Mai dato'),
                    ]),

                Section::make('Indirizzi')
                    ->icon('heroicon-o-map-pin')
                    ->columnSpan(['lg' => 3])
                    ->schema([
                        RepeatableEntry::make('addresses')
                            ->label('Indirizzi')
                            ->hiddenLabel()
                            ->grid(['md' => 2, 'xl' => 3])
                            ->placeholder('Nessun indirizzo salvato')
                            ->schema([
                                TextEntry::make('label')
                                    ->label('Nome')
                                    ->hiddenLabel()
                                    ->weight('bold')
                                    ->formatStateUsing(fn ($state, $record) => ($state ?: 'Indirizzo').($record->is_default ? ' · principale' : '')),
                                TextEntry::make('address_line')
                                    ->label('Indirizzo')
                                    ->hiddenLabel()
                                    ->formatStateUsing(fn ($state, $record) => $record->recipient_name.' — '.$state.', '
                                        .$record->postal_code.' '.$record->comune?->name.' ('.$record->province.')'),
                                TextEntry::make('phone')->label('Telefono')->hiddenLabel()->icon('heroicon-o-phone')->placeholder('—'),
                            ]),
                    ]),

                Section::make('Ordini')
                    ->icon('heroicon-o-shopping-bag')
                    ->columnSpan(['lg' => 3])
                    ->schema([
                        RepeatableEntry::make('orders')
                            ->label('Ordini')
                            ->hiddenLabel()
                            ->state(fn (User $record) => $record->orders()->latest()->get())
                            ->placeholder('Nessun ordine')
                            ->columns(4)
                            ->schema([
                                TextEntry::make('id')
                                    ->label('Ordine')
                                    ->weight('bold')
                                    ->formatStateUsing(fn (int $state) => '#'.str_pad((string) $state, 5, '0', STR_PAD_LEFT))
                                    ->url(fn ($record) => OrderResource::getUrl('edit', ['record' => $record])),
                                TextEntry::make('created_at')->label('Data')->dateTime('d/m/Y'),
                                TextEntry::make('status')
                                    ->label('Stato')
                                    ->badge()
                                    ->formatStateUsing(fn (string $state) => match ($state) {
                                        'nuovo' => 'In attesa di pagamento',
                                        'in_lavorazione' => 'In lavorazione',
                                        'evaso' => 'Evaso',
                                        'annullato' => 'Annullato',
                                        default => $state,
                                    })
                                    ->color(fn (string $state) => match ($state) {
                                        'nuovo' => 'info',
                                        'in_lavorazione' => 'warning',
                                        'evaso' => 'success',
                                        'annullato' => 'danger',
                                        default => 'gray',
                                    }),
                                TextEntry::make('total')->label('Totale')->money('EUR'),
                            ]),
                    ]),
            ]);
    }
}
