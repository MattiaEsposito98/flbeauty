<?php

namespace App\Filament\Resources\EmailLogs;

use App\Filament\Resources\EmailLogs\Pages\ListEmailLogs;
use App\Filament\Resources\EmailLogs\Tables\EmailLogsTable;
use App\Models\EmailLog;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

/** Registro delle email inviate dal sito (sola lettura). */
class EmailLogResource extends Resource
{
    protected static ?string $model = EmailLog::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedEnvelope;

    protected static ?string $navigationLabel = 'Registro email';

    protected static ?string $modelLabel = 'Email';

    protected static ?string $pluralModelLabel = 'Registro email';

    protected static ?int $navigationSort = 65;

    public static function table(Table $table): Table
    {
        return EmailLogsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListEmailLogs::route('/'),
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
