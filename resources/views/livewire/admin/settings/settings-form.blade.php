<div class="max-w-2xl">
    <h1 class="font-display text-3xl font-semibold text-ink">Settings</h1>
    <p class="mt-1 text-sm text-ink-muted">Site-wide text shown on the public pages.</p>

    <form wire:submit="save" class="mt-8 space-y-6 rounded-lg border border-line bg-paper p-6">
        <div>
            <x-forms.label for="siteName">Site name</x-forms.label>
            <x-forms.input wire:model="siteName" id="siteName" type="text" maxlength="60" required />
            <x-forms.error for="siteName" />
        </div>

        <div>
            <x-forms.label for="tagline">Tagline</x-forms.label>
            <x-forms.input wire:model="tagline" id="tagline" type="text" maxlength="120" placeholder="Shown as the headline on the home page" />
            <x-forms.error for="tagline" />
        </div>

        <div>
            <x-forms.label for="description">Description</x-forms.label>
            <x-forms.textarea wire:model="description" id="description" rows="3" maxlength="255" placeholder="A sentence or two under the headline, also used as the default meta description" />
            <x-forms.error for="description" />
        </div>

        <div>
            <x-forms.label for="footerText">Footer text</x-forms.label>
            <x-forms.input wire:model="footerText" id="footerText" type="text" maxlength="160" />
            <x-forms.error for="footerText" />
        </div>

        <div class="flex items-center gap-4">
            <x-forms.button type="submit" wire:loading.attr="disabled" wire:target="save">Save settings</x-forms.button>

            @if ($saved)
                <p class="text-sm text-success" role="status">Settings saved.</p>
            @endif
        </div>
    </form>
</div>
