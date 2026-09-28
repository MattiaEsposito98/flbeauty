<?php

namespace App\Filament\Resources\Users\Tables;

use App\Models\Comune;
use App\Models\User;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nome')
                    ->weight('bold')
                    ->description(fn (User $record) => '@'.$record->username)
                    ->searchable(['name', 'username', 'email'])
                    ->sortable(),
                TextColumn::make('email')
                    ->label('Email')
                    ->copyable()
                    ->copyMessage('Email copiata')
                    ->sortable(),
                TextColumn::make('defaultAddress.comune.name')
                    ->label('Città')
                    ->description(fn (User $record) => $record->defaultAddress?->province)
                    ->placeholder('—'),
                IconColumn::make('email_verified_at')
                    ->label('Email verificata')
                    ->state(fn (User $record) => $record->hasVerifiedEmail())
                    ->boolean()
                    ->alignCenter(),
                IconColumn::make('marketing_consent')
                    ->label('Offerte via email')
                    ->boolean()
                    ->trueIcon('heroicon-o-envelope')
                    ->falseIcon('heroicon-o-minus')
                    ->trueColor('primary')
                    ->falseColor('gray')
                    ->alignCenter(),
                TextColumn::make('orders_count')
                    ->label('Ordini')
                    ->badge()
                    ->color(fn (int $state) => $state > 0 ? 'success' : 'gray')
                    ->sortable()
                    ->alignCenter(),
                TextColumn::make('spent')
                    ->label('Speso')
                    ->money('EUR')
                    ->placeholder('—')
                    ->sortable()
                    ->alignEnd(),
                TextColumn::make('login_count')
                    ->label('Accessi')
                    ->sortable()
                    ->alignCenter(),
                TextColumn::make('last_login_at')
                    ->label('Ultimo accesso')
                    ->since()
                    ->dateTimeTooltip('d/m/Y H:i')
                    ->placeholder('Mai')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Registrato il')
                    ->date('d/m/Y')
                    ->sortable()
                    ->toggleable(),
            ])
            ->defaultSort('name')
            ->filters([
                TernaryFilter::make('verified')
                    ->label('Email verificata')
                    ->trueLabel('Solo verificate')
                    ->falseLabel('Solo da verificare')
                    ->queries(
                        true: fn (Builder $q) => $q->whereNotNull('email_verified_at'),
                        false: fn (Builder $q) => $q->whereNull('email_verified_at'),
                    ),
                TernaryFilter::make('marketing_consent')
                    ->label('Offerte via email')
                    ->trueLabel('Hanno dato il consenso')
                    ->falseLabel('Senza consenso'),
                TernaryFilter::make('has_orders')
                    ->label('Ordini')
                    ->trueLabel('Hanno ordinato')
                    ->falseLabel('Mai ordinato')
                    ->queries(
                        true: fn (Builder $q) => $q->has('orders'),
                        false: fn (Builder $q) => $q->doesntHave('orders'),
                    ),
                SelectFilter::make('city')
                    ->label('Città')
                    ->searchable()
                    ->options(fn () => Comune::query()
                        ->whereHas('addresses', fn (Builder $q) => $q->where('is_default', true))
                        ->orderBy('name')
                        ->pluck('name', 'id'))
                    ->query(fn (Builder $q, array $data) => $q->when(
                        $data['value'] ?? null,
                        fn (Builder $q, $comuneId) => $q->whereHas('defaultAddress', fn (Builder $a) => $a->where('comune_id', $comuneId)),
                    )),
            ])
            ->recordActions([
                ViewAction::make(),
            ])
            ->recordUrl(fn (User $record) => route('filament.admin.resources.users.view', $record))
            ->striped()
            ->emptyStateHeading('Nessun utente registrato')
            ->emptyStateDescription('Qui compariranno i clienti che si registrano sul sito.');
    }
}
