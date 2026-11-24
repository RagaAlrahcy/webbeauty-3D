<?php

namespace Database\Seeders;

use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Model events stay enabled: Media Library generates image conversions from them.
     */
    public function run(): void
    {
        User::query()->firstOrCreate(
            ['email' => 'admin@beauty.test'],
            ['name' => 'Admin', 'password' => 'password', 'is_admin' => true],
        );

        $settings = SiteSetting::query()->first() ?? new SiteSetting;
        $settings->fill([
            'brand_name' => 'Lumière',
            'tagline' => ['id' => 'Perawatan kulit yang lembut & jujur', 'en' => 'Gentle, honest skincare'],
            'primary_color' => '#a35d6a',
            'accent_color' => '#f4e1dc',
            'whatsapp_number' => '081234567890',
            'instagram_url' => 'https://instagram.com/',
            'email' => 'halo@lumiere.test',
            'address' => 'Jakarta, Indonesia',
        ])->save();

        $this->call([DemoProductSeeder::class, DemoContentSeeder::class]);
    }
}
