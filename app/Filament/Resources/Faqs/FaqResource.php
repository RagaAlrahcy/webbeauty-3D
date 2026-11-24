<?php

declare(strict_types=1);

namespace App\Filament\Resources\Faqs;

use App\Filament\Resources\Faqs\Pages\ManageFaqs;
use App\Filament\Support\Translatable;
use App\Models\Faq;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class FaqResource extends Resource
{
    protected static ?string $model = Faq::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedQuestionMarkCircle;

    protected static ?int $navigationSort = 3;

    public static function getModelLabel(): string
    {
        return __('admin.faq.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.faq.plural');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('admin.nav.content');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(1)->components([
            Select::make('product_id')
                ->label(__('admin.common.product'))
                ->helperText(__('admin.faq.product_help'))
                ->relationship('product', 'slug')
                ->getOptionLabelFromRecordUsing(fn ($record): string => $record->name)
                ->searchable()
                ->preload(),

            Translatable::make('question', __('admin.faq.question'), fn (string $path) => TextInput::make($path)
                ->maxLength(200), required: true),

            Translatable::make('answer', __('admin.faq.answer'), fn (string $path) => Textarea::make($path)
                ->rows(4)
                ->maxLength(2000), required: true),

            Toggle::make('is_published')
                ->label(__('admin.common.is_published'))
                ->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('product'))
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->columns([
                TextColumn::make('question')
                    ->label(__('admin.faq.question'))
                    ->searchable(query: fn (Builder $query, string $search) => $query
                        ->where('question->id', 'like', "%{$search}%")
                        ->orWhere('question->en', 'like', "%{$search}%"))
                    ->wrap(),
                TextColumn::make('product.name')
                    ->label(__('admin.common.product'))
                    ->placeholder(__('admin.faq.general'))
                    ->badge()
                    ->color('gray'),
                IconColumn::make('is_published')
                    ->label(__('admin.common.is_published'))
                    ->boolean(),
            ])
            ->filters([
                TernaryFilter::make('product_id')
                    ->label(__('admin.faq.general'))
                    ->nullable()
                    ->trueLabel(__('admin.common.product'))
                    ->falseLabel(__('admin.faq.general')),
            ])
            ->recordActions([
                EditAction::make()
                    ->mutateRecordDataUsing(fn (array $data, Model $record): array => Translatable::fill($data, $record)),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageFaqs::route('/'),
        ];
    }
}
