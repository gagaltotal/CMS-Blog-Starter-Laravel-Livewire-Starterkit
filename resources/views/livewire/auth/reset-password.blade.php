<div>
    <h1 class="font-display text-2xl font-semibold text-ink">Set a new password</h1>

    <form wire:submit="resetPassword" class="mt-6 space-y-5">
        <div>
            <x-forms.label for="email">Email</x-forms.label>
            <x-forms.input wire:model="email" id="email" type="email" autocomplete="username" required autofocus />
            <x-forms.error for="email" />
        </div>

        <div>
            <x-forms.label for="password">New password</x-forms.label>
            <x-forms.input wire:model="password" id="password" type="password" autocomplete="new-password" required />
            <x-forms.error for="password" />
        </div>

        <div>
            <x-forms.label for="password_confirmation">Confirm new password</x-forms.label>
            <x-forms.input wire:model="password_confirmation" id="password_confirmation" type="password" autocomplete="new-password" required />
            <x-forms.error for="password_confirmation" />
        </div>

        <x-forms.button type="submit" class="w-full" wire:loading.attr="disabled" wire:target="resetPassword">
            Reset password
        </x-forms.button>
    </form>
</div>
