<?php

namespace App\Filament\Resources\EmailLogs\Tables;

use App\Models\EmailLog;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class EmailLogsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')
                    ->label('Quando')
                    ->dateTime('d/m/Y H:i:s')
                    ->timezone('Europe/Rome')
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Esito')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => $state === EmailLog::STATUS_FAILED ? 'Errore' : 'Inviata')
                    ->color(fn (string $state) => $state === EmailLog::STATUS_FAILED ? 'danger' : 'success'),
                TextColumn::make('kind')
                    ->label('Tipo')
                    ->formatStateUsing(fn (?string $state) => EmailLog::kindLabel($state)),
                TextColumn::make('recipient')
                    ->label('Destinatario')
                    ->searchable()
                    ->copyable()
                    ->placeholder('—'),
                TextColumn::make('subject')
                    ->label('Oggetto')
                    ->limit(50)
                    ->tooltip(fn (EmailLog $record) => $record->subject)
                    ->searchable()
                    ->placeholder('—'),
                TextColumn::make('error')
                    ->label('Errore')
                    ->limit(60)
                    ->tooltip(fn (EmailLog $record) => $record->error)
                    ->color('danger')
                    ->placeholder('—'),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Esito')
                    ->options(['inviata' => 'Inviata', 'errore' => 'Errore']),
                SelectFilter::make('kind')
                    ->label('Tipo')
                    ->options(fn () => EmailLog::query()->whereNotNull('kind')->distinct()->pluck('kind')
                        ->mapWithKeys(fn (string $kind) => [$kind => EmailLog::kindLabel($kind)])->all()),
            ])
            ->defaultSort('created_at', 'desc')
            ->poll('30s')
            ->striped()
            ->emptyStateHeading('Nessuna email registrata')
            ->emptyStateDescription('Qui compariranno le email inviate dal sito (verifica account, ordini, comunicazioni).');
    }
}
