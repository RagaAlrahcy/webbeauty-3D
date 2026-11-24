<?php

declare(strict_types=1);

namespace App\Filament\Resources\Pages;

use App\Enums\PageKey;
use App\Filament\Resources\Pages\Pages\CreatePage;
use App\Filament\Resources\Pages\Pages\EditPage;
use App\Filament\Resources\Pages\Pages\ListPages;
use App\Filament\Support\Translatable;
use App\Models\Page;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/** Fixed content pages; each PageKey can exist once and has its own public URL. */
class PageResource extends Resource
{
    protected static ?string $model = Page::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static ?int $navigationSort = 5;

    public static function getModelLabel(): string
    {
        return __('admin.page.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.page.plural');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('admin.nav.content');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()
                ->columnSpanFull()
                ->schema([
                    Select::make('key')
                        ->label(__('admin.page.key'))
                        ->helperText(__('admin.page.key_help'))
                        ->options(PageKey::class)
                        ->required()
                        ->unique(ignoreRecord: true),

                    Translatable::make('title', __('admin.page.title'), fn (string $path) => TextInput::make($path)
                        ->maxLength(120), required: true),

                    Translatable::make('intro', __('admin.page.intro'), fn (string $path) => Textarea::make($path)
                        ->rows(2)
                        ->maxLength(300)),

                    Translatable::make('content', __('admin.page.content'), fn (string $path) => RichEditor::make($path)
                        ->toolbarButtons([['bold', 'italic', 'underline', 'link'], ['h2', 'h3', 'bulletList', 'orderedList', 'blockquote'], ['undo', 'redo']]), columns: 1),

                    Translatable::make('meta_description', __('admin.page.meta_description'), fn (string $path) => Textarea::make($path)
                        ->rows(2)
                        ->maxLength(160)),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('key')
                    ->label(__('admin.page.key'))
                    ->badge(),
                TextColumn::make('title')
                    ->label(__('admin.page.title')),
                TextColumn::make('updated_at')
                    ->label(__('admin.updated_at'))
                    ->since(),
            ])
            ->recordActions([
                Action::make('view')
                    ->label(__('admin.product.view_on_site'))
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(fn (Page $record): string => route($record->key->routeName()))
                    ->openUrlInNewTab(),
                EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPages::route('/'),
            'create' => CreatePage::route('/create'),
            'edit' => EditPage::route('/{record}/edit'),
        ];
    }
}
