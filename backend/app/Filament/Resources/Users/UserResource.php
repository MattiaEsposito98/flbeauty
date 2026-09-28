<?php

namespace App\Filament\Resources\Users;

use App\Filament\Resources\Users\Pages\ListUsers;
use App\Filament\Resources\Users\Pages\ViewUser;
use App\Filament\Resources\Users\Schemas\UserInfolist;
use App\Filament\Resources\Users\Tables\UsersTable;
use App\Filament\Resources\Users\Widgets\CookieBannerStats;
use App\Filament\Resources\Users\Widgets\TopCitiesChart;
use App\Filament\Resources\Users\Widgets\UserActivityChart;
use App\Filament\Resources\Users\Widgets\UsersOverview;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Clienti registrati dal sito, in sola lettura: elenco, scheda e statistiche.
 * Gli account dello staff (is_admin) non compaiono.
 */
class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static ?string $navigationLabel = 'Utenti';

    protected static ?string $modelLabel = 'Utente';

    protected static ?string $pluralModelLabel = 'Utenti';

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?int $navigationSort = 35;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('is_admin', false)
            ->with('defaultAddress.comune')
            ->withCount('orders')
            ->withSum(['orders as spent' => fn (Builder $q) => $q->where('status', '!=', 'annullato')], 'total');
    }

    public static function infolist(Schema $schema): Schema
    {
        return UserInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return UsersTable::configure($table);
    }

    public static function getWidgets(): array
    {
        return [
            UsersOverview::class,
            UserActivityChart::class,
            TopCitiesChart::class,
            CookieBannerStats::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUsers::route('/'),
            'view' => ViewUser::route('/{record}'),
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
