<?php

namespace App\Filament\Resources\Communications;

use App\Filament\Resources\Communications\Pages\CreateCommunication;
use App\Filament\Resources\Communications\Pages\ListCommunications;
use App\Filament\Resources\Communications\Schemas\CommunicationForm;
use App\Filament\Resources\Communications\Tables\CommunicationsTable;
use App\Models\Communication;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class CommunicationResource extends Resource
{
    protected static ?string $model = Communication::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMegaphone;

    protected static ?string $navigationLabel = 'Comunicazioni';

    protected static ?string $modelLabel = 'Comunicazione';

    protected static ?string $pluralModelLabel = 'Comunicazioni';

    protected static ?string $recordTitleAttribute = 'subject';

    protected static ?int $navigationSort = 60;

    public static function form(Schema $schema): Schema
    {
        return CommunicationForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CommunicationsTable::configure($table);
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
            'index' => ListCommunications::route('/'),
            'create' => CreateCommunication::route('/create'),
        ];
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
