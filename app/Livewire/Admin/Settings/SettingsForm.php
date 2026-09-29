<?php

namespace App\Livewire\Admin\Settings;

use App\Models\Setting;
use App\Services\SecurityLog;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layout.admin')]
#[Title('Settings')]
class SettingsForm extends Component
{
    public string $siteName = '';

    public string $tagline = '';

    public string $description = '';

    public string $footerText = '';

    public bool $saved = false;

    public function mount(): void
    {
        // Spatie registers a Gate::before hook, so permission strings work
        // directly with authorize().
        $this->authorize('manage settings');

        $this->siteName = Setting::siteName();
        $this->tagline = (string) Setting::get('site_tagline');
        $this->description = (string) Setting::get('site_description');
        $this->footerText = (string) Setting::get('footer_text');
    }

    public function save(): void
    {
        // Re-checked in the action itself, not just in mount().
        $this->authorize('manage settings');

        $validated = $this->validate([
            'siteName' => ['required', 'string', 'max:60'],
            'tagline' => ['nullable', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:255'],
            'footerText' => ['nullable', 'string', 'max:160'],
        ]);

        Setting::set('site_name', $validated['siteName']);
        Setting::set('site_tagline', $validated['tagline'] ?: null);
        Setting::set('site_description', $validated['description'] ?: null);
        Setting::set('footer_text', $validated['footerText'] ?: null);

        SecurityLog::info('settings_updated');

        $this->saved = true;
    }

    public function updated(): void
    {
        $this->saved = false;
    }

    public function render()
    {
        return view('livewire.admin.settings.settings-form');
    }
}
