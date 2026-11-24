<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Filament\Support\Translatable;
use App\Models\Homepage;
use App\Models\Product;
use BackedEnum;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Model;

class ManageHomepage extends SingletonSettingsPage
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedHome;

    protected static ?int $navigationSort = 10;

    private ?Homepage $homepage = null;

    public static function getNavigationLabel(): string
    {
        return __('admin.homepage.title');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('admin.nav.site');
    }

    public function getTitle(): string
    {
        return __('admin.homepage.title');
    }

    protected function getRecord(): Model
    {
        return $this->homepage ??= Homepage::current();
    }

    protected function getFormComponents(): array
    {
        return [
            Section::make(__('admin.homepage.hero'))
                ->description(__('admin.homepage.hero_help'))
                ->schema([
                    Select::make('hero_product_id')
                        ->label(__('admin.homepage.hero_product'))
                        ->options(fn (): array => Product::query()
                            ->published()
                            ->get()
                            ->mapWithKeys(fn (Product $product): array => [$product->id => $product->name])
                            ->all())
                        ->searchable(),

                    Translatable::make('headline', __('admin.homepage.headline'), fn (string $path) => TextInput::make($path)
                        ->maxLength(90), required: true),

                    Translatable::make('subheadline', __('admin.homepage.subheadline'), fn (string $path) => Textarea::make($path)
                        ->rows(2)
                        ->maxLength(220)),

                    Translatable::make('cta_label', __('admin.homepage.cta_label'), fn (string $path) => TextInput::make($path)
                        ->maxLength(40)),

                    TextInput::make('cta_url')
                        ->label(__('admin.homepage.cta_url'))
                        ->helperText(__('admin.homepage.cta_url_help'))
                        ->maxLength(255)
                        ->rules(['regex:/^(\/|https?:\/\/)\S*$/']),
                ]),
        ];
    }
}
