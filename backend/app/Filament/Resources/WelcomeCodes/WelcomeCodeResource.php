<?php

namespace App\Filament\Resources\WelcomeCodes;

use App\Filament\Resources\Discounts\DiscountResource;
use App\Filament\Resources\Orders\OrderResource;
use App\Filament\Resources\WelcomeCodes\Pages\ListWelcomeCodes;
use App\Models\Discount;
use App\Models\Order;
use BackedEnum;
use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Codici di benvenuto (uno per cliente, mandato dopo la verifica dell'email): chi lo ha ricevuto,
 * chi lo ha usato, quali stanno per scadere. Sola lettura: nascono da soli, non si creano a mano.
 */
class WelcomeCodeResource extends Resource
{
    protected static ?string $model = Discount::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedGift;

    protected static ?string $navigationLabel = 'Codici di benvenuto';

    protected static ?string $modelLabel = 'Codice di benvenuto';

    protected static ?string $pluralModelLabel = 'Codici di benvenuto';

    protected static ?string $slug = 'codici-di-benvenuto';

    // Non ha una voce nel menu: sta dentro «Sconti», come seconda scheda (vedi DiscountResource::subNavigation()).
    protected static bool $shouldRegisterNavigation = false;

    protected static ?SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    public static function getEloquentQuery(): Builder
    {
        $notCancelled = fn (Builder $q) => $q->where('status', '!=', Order::STATUS_CANCELLED);

        return parent::getEloquentQuery()
            ->welcome()
            ->with('user')
            ->withExists(['orders as is_used' => $notCancelled])
            ->withMin(['orders as used_at' => $notCancelled], 'created_at')
            ->withMin(['orders as used_order_id' => $notCancelled], 'id');
    }

    /** Filament metterebbe le maiuscole a ogni parola («Codici Di Benvenuto»). */
    public static function getTitleCasePluralModelLabel(): string
    {
        return 'Codici di benvenuto';
    }

    /** Stato del codice, come lo vede l'admin. */
    public static function status(Discount $record): string
    {
        if ($record->is_used) {
            return 'Usato';
        }

        if ($record->ends_at && $record->ends_at->isPast()) {
            return 'Scaduto';
        }

        if ($record->ends_at && $record->ends_at->lte(now()->addDays(Discount::EXPIRING_DAYS))) {
            return 'In scadenza';
        }

        return 'Da usare';
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')
                    ->label('Cliente')
                    ->placeholder('Account eliminato')
                    ->description(fn (Discount $record) => $record->user?->email)
                    ->searchable(query: fn (Builder $query, string $search) => $query->where(fn (Builder $q) => $q
                        ->where('code', 'like', "%{$search}%")
                        ->orWhereHas('user', fn (Builder $u) => $u->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%"))))
                    ->weight('bold'),
                TextColumn::make('code')
                    ->label('Codice')
                    ->copyable()
                    ->copyMessage('Codice copiato')
                    ->fontFamily('mono'),
                TextColumn::make('status')
                    ->label('Stato')
                    ->state(fn (Discount $record) => self::status($record))
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'Usato' => 'success',
                        'In scadenza' => 'warning',
                        'Scaduto' => 'gray',
                        default => 'info',
                    }),
                TextColumn::make('emailed_at')
                    ->label('Email inviata')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('Non inviata')
                    ->color(fn ($state) => $state ? null : 'danger')
                    ->sortable(),
                TextColumn::make('ends_at')
                    ->label('Scade il')
                    ->dateTime('d/m/Y')
                    ->description(fn (Discount $record) => match (true) {
                        (bool) $record->is_used => null,
                        (bool) $record->ends_at?->isPast() => 'scaduto '.$record->ends_at->diffForHumans(),
                        default => 'tra '.$record->ends_at?->diffForHumans(null, true),
                    })
                    ->sortable(),
                TextColumn::make('used_at')
                    ->label('Usato il')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('—')
                    ->sortable(),
                TextColumn::make('used_order_id')
                    ->label('Ordine')
                    ->formatStateUsing(fn ($state) => $state ? '#'.str_pad((string) $state, 5, '0', STR_PAD_LEFT) : '—')
                    ->url(fn (Discount $record) => $record->used_order_id
                        ? OrderResource::getUrl('edit', ['record' => $record->used_order_id])
                        : null),
            ])
            ->defaultSort('created_at', 'desc')
            ->striped()
            ->emptyStateHeading('Nessun codice di benvenuto')
            ->emptyStateDescription('Il codice viene creato da solo quando un cliente verifica la sua email.');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListWelcomeCodes::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }
}
