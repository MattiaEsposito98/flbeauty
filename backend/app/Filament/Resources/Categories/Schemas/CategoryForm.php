<?php

namespace App\Filament\Resources\Categories\Schemas;

use App\Models\Category;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class CategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Categoria')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->label('Nome')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->unique(ignoreRecord: true)
                            ->validationMessages([
                                'unique' => 'Esiste già una categoria con questo nome.',
                            ])
                            ->afterStateUpdated(fn (string $state, callable $set) => $set('slug', Str::slug($state)))
                            ->helperText(function (?string $state, ?Category $record) {
                                if (blank($state)) {
                                    return null;
                                }

                                $exists = Category::where('name', $state)
                                    ->when($record, fn ($query) => $query->where('id', '!=', $record->id))
                                    ->exists();

                                return $exists
                                    ? '⚠️ Esiste già una categoria chiamata "'.$state.'". Usa quella invece di crearne una nuova.'
                                    : null;
                            })
                            ->columnSpan(1),
                        TextInput::make('slug')
                            ->label('Slug (URL)')
                            ->disabled()
                            ->dehydrated()
                            ->maxLength(255)
                            ->helperText('Generato automaticamente dal nome.')
                            ->columnSpan(1),
                        Textarea::make('description')
                            ->label('Descrizione')
                            ->rows(3)
                            ->columnSpanFull(),
                        Toggle::make('is_active')
                            ->label('Attiva')
                            ->helperText('Visibile nel negozio')
                            ->default(true)
                            ->inline(false),
                    ]),
            ]);
    }
}
