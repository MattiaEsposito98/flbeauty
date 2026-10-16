<?php

namespace App\Filament\Resources\Discounts;

use App\Filament\Resources\Discounts\Pages\CreateDiscount;
use App\Filament\Resources\Discounts\Pages\EditDiscount;
use App\Filament\Resources\Discounts\Pages\ListDiscounts;
use App\Filament\Resources\Discounts\Schemas\DiscountForm;
use App\Filament\Resources\Discounts\Tables\DiscountsTable;
use App\Filament\Resources\WelcomeCodes\WelcomeCodeResource;
use App\Models\Discount;
use BackedEnum;
use Filament\Navigation\NavigationItem;
use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class DiscountResource extends Resource
{
    protected static ?string $model = Discount::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTag;

    protected static ?string $navigationLabel = 'Sconti';

    protected static ?string $modelLabel = 'Sconto';

    protected static ?string $pluralModelLabel = 'Sconti';

    protected static ?string $recordTitleAttribute = 'code';

    protected static ?int $navigationSort = 40;

    protected static ?SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    /**
     * Le due schede in alto di «Sconti»: codici validi per tutti e codici di benvenuto (uno per cliente).
     * I codici di benvenuto non hanno una voce nel menu: stanno qui dentro, per non allungare la barra.
     */
    public static function subNavigation(): array
    {
        $expiring = Discount::welcome()->unused()
            ->whereBetween('ends_at', [now(), now()->addDays(Discount::EXPIRING_DAYS)])
            ->count();

        return [
            NavigationItem::make('Codici sconto')
                ->icon(Heroicon::OutlinedTag)
                ->url(static::getUrl('index'))
                ->isActiveWhen(fn () => request()->routeIs(static::getRouteBaseName().'.*')),
            NavigationItem::make('Codici di benvenuto')
                ->icon(Heroicon::OutlinedGift)
                ->url(WelcomeCodeResource::getUrl('index'))
                ->badge($expiring ?: null, color: 'warning')
                ->badgeTooltip('Codici in scadenza (entro '.Discount::EXPIRING_DAYS.' giorni)')
                ->isActiveWhen(fn () => request()->routeIs(WelcomeCodeResource::getRouteBaseName().'.*')),
        ];
    }

    /** La voce «Sconti» del menu resta accesa anche dentro «Codici di benvenuto». */
    public static function getNavigationItemActiveRoutePattern(): string|array
    {
        return [
            ...(array) parent::getNavigationItemActiveRoutePattern(),
            ...(array) WelcomeCodeResource::getNavigationItemActiveRoutePattern(),
        ];
    }

    /** I codici personali di benvenuto stanno nella loro scheda («Codici di benvenuto»), non in questa lista. */
    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()->standard();
    }

    public static function form(Schema $schema): Schema
    {
        return DiscountForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DiscountsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDiscounts::route('/'),
            'create' => CreateDiscount::route('/create'),
            'edit' => EditDiscount::route('/{record}/edit'),
        ];
    }
}
