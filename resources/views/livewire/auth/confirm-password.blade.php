<div>
    <h1 class="font-display text-2xl font-semibold text-ink">Confirm password</h1>
    <p class="mt-1 text-sm text-ink-muted">
        This is a sensitive action — please confirm your password before continuing.
    </p>

    <form wire:submit="confirmPassword" class="mt-6 space-y-5">
        <div>
            <x-forms.label for="password">Password</x-forms.label>
            <x-forms.input wire:model="password" id="password" type="password" autocomplete="current-password" required autofocus />
            <x-forms.error for="password" />
        </div>

        <x-forms.button type="submit" class="w-full" wire:loading.attr="disabled" wire:target="confirmPassword">
            Confirm
        </x-forms.button>
    </form>
</div>
