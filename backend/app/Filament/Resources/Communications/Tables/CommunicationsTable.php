<?php

namespace App\Filament\Resources\Communications\Tables;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CommunicationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('subject')
                    ->label('Oggetto')
                    ->weight('bold')
                    ->searchable(),
                TextColumn::make('recipients_count')
                    ->label('Destinatari')
                    ->badge()
                    ->color('info')
                    ->alignCenter(),
                TextColumn::make('sentBy.name')
                    ->label('Inviata da')
                    ->placeholder('—'),
                TextColumn::make('sent_at')
                    ->label('Inviata il')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->recordActions([
                //
            ])
            ->toolbarActions([
                //
            ])
            ->defaultSort('sent_at', 'desc')
            ->striped()
            ->emptyStateHeading('Nessuna comunicazione inviata')
            ->emptyStateDescription('Qui comparirà lo storico delle email di offerte/novità inviate ai clienti.');
    }
}
