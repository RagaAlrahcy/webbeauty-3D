<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Filament\Resources\Products\Schemas\ProductForm;
use App\Filament\Support\Translatable;
use App\Models\SiteSetting;
use BackedEnum;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class ManageSiteSettings extends SingletonSettingsPage
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static ?int $navigationSort = 20;

    private ?SiteSetting $settings = null;

    public static function getNavigationLabel(): string
    {
        return __('admin.settings.title');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('admin.nav.site');
    }

    public function getTitle(): string
    {
        return __('admin.settings.title');
    }

    protected function getRecord(): Model
    {
        if ($this->settings === null) {
            // Read the row directly (not the cached copy) so edits start from fresh data.
            $this->settings = SiteSetting::query()->first() ?? SiteSetting::current();
        }

        return $this->settings;
    }

    public function save(): void
    {
        parent::save();
        Cache::forget(SiteSetting::CACHE_KEY);
    }

    protected function getFormComponents(): array
    {
        return [
            Section::make(__('admin.settings.brand'))
                ->columns(2)
                ->schema([
                    TextInput::make('brand_name')
                        ->label(__('admin.settings.brand_name'))
                        ->required()
                        ->maxLength(60),

                    SpatieMediaLibraryFileUpload::make('logo')
                        ->label(__('admin.settings.logo'))
                        ->collection('logo')
                        ->image()
                        ->acceptedFileTypes(['image/png', 'image/webp', 'image/svg+xml'])
                        ->maxSize(1024),

                    SpatieMediaLibraryFileUpload::make('favicon')
                        ->label(__('admin.settings.favicon'))
                        ->helperText(__('admin.settings.favicon_help'))
                        ->collection('favicon')
                        ->image()
                        ->acceptedFileTypes(['image/png', 'image/jpeg', 'image/webp'])
                        ->maxSize(1024),

                    Translatable::make('tagline', __('admin.settings.tagline'), fn (string $path) => TextInput::make($path)
                        ->maxLength(120)),

                    ColorPicker::make('primary_color')
                        ->label(__('admin.settings.primary_color'))
                        ->helperText(__('admin.settings.primary_color_help'))
                        ->hex()
                        ->required()
                        ->rules([ProductForm::HEX_RULE]),

                    ColorPicker::make('accent_color')
                        ->label(__('admin.settings.accent_color'))
                        ->hex()
                        ->required()
                        ->rules([ProductForm::HEX_RULE]),
                ]),

            Section::make(__('admin.settings.contact'))
                ->columns(2)
                ->schema([
                    TextInput::make('whatsapp_number')
                        ->label(__('admin.settings.whatsapp_number'))
                        ->helperText(__('admin.settings.whatsapp_help'))
                        ->tel()
                        ->maxLength(20),
                    TextInput::make('marketplace_url')
                        ->label(__('admin.settings.marketplace_url'))
                        ->url()
                        ->maxLength(255),
                    TextInput::make('instagram_url')
                        ->label('Instagram')
                        ->url()
                        ->maxLength(255),
                    TextInput::make('tiktok_url')
                        ->label('TikTok')
                        ->url()
                        ->maxLength(255),
                    TextInput::make('email')
                        ->label(__('admin.settings.email'))
                        ->email()
                        ->maxLength(255),
                    TextInput::make('phone')
                        ->label(__('admin.settings.phone'))
                        ->tel()
                        ->maxLength(30),
                    Textarea::make('address')
                        ->label(__('admin.settings.address'))
                        ->rows(2)
                        ->columnSpanFull(),
                ]),

            Section::make(__('admin.settings.tracking'))
                ->description(__('admin.settings.tracking_help'))
                ->columns(2)
                ->collapsible()
                ->schema([
                    TextInput::make('ga4_measurement_id')
                        ->label(__('admin.settings.ga4_measurement_id'))
                        ->helperText(__('admin.settings.ga4_help'))
                        ->placeholder('G-XXXXXXXXXX')
                        ->maxLength(20)
                        ->rules(['regex:'.SiteSetting::GA4_ID_PATTERN]),
                    TextInput::make('meta_pixel_id')
                        ->label(__('admin.settings.meta_pixel_id'))
                        ->helperText(__('admin.settings.meta_pixel_help'))
                        ->maxLength(20)
                        ->rules(['regex:'.SiteSetting::META_PIXEL_ID_PATTERN]),
                ]),
        ];
    }
}
