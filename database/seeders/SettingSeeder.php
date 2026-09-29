<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        $defaults = [
            'site_name' => (string) config('app.name'),
            'site_tagline' => 'Notes on building for the web.',
            'site_description' => 'A small, fast blog about software, design and working well. Built with Laravel, Livewire and Tailwind CSS.',
            'footer_text' => 'Built with Laravel.',
        ];

        // Only fill in what's missing, so re-running the seeder never
        // overwrites values an admin has since edited in Admin > Settings.
        foreach ($defaults as $key => $value) {
            if (! Setting::query()->whereKey($key)->exists()) {
                Setting::set($key, $value);
            }
        }
    }
}
