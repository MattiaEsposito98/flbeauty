<?php

namespace App\Filament\Resources\AccountDeletions;

use App\Filament\Resources\AccountDeletions\Pages\ListAccountDeletions;
use App\Models\AccountDeletion;
use BackedEnum;
use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

/**
 * Registro degli account che il cliente si è eliminato dal sito. Sola lettura: le righe nascono da
 * sole (AccountController::destroy) e si cancellano da sole dopo `shop.deletion_log_months` mesi.
 */
class AccountDeletionResource extends Resource
{
    protected static ?string $model = AccountDeletion::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserMinus;

    protected static ?string $navigationLabel = 'Eliminati';

    protected static ?string $modelLabel = 'Account eliminato';

    protected static ?string $pluralModelLabel = 'Account eliminati';

    protected static ?string $slug = 'account-eliminati';

    // Non ha una voce nel menu: sta dentro «Utenti», come terza scheda (vedi UserResource::subNavigation()).
    protected static bool $shouldRegisterNavigation = false;

    protected static ?SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    /** Filament metterebbe le maiuscole a ogni parola. */
    public static function getTitleCasePluralModelLabel(): string
    {
        return 'Account eliminati';
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')
                    ->label('Eliminato il')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                TextColumn::make('name')
                    ->label('Cliente')
                    ->weight('bold')
                    ->searchable(),
                TextColumn::make('email')
                    ->label('Email')
                    ->copyable()
                    ->searchable(),
                TextColumn::make('registered_at')
                    ->label('Registrato il')
                    ->dateTime('d/m/Y')
                    ->placeholder('—')
                    ->sortable(),
                IconColumn::make('welcome_code_issued')
                    ->label('Aveva il codice di benvenuto')
                    ->boolean(),
            ])
            ->defaultSort('created_at', 'desc')
            ->striped()
            ->emptyStateHeading('Nessun account eliminato')
            ->emptyStateDescription('Qui compaiono i clienti che si eliminano l\'account dal sito.');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAccountDeletions::route('/'),
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
