<div>
    <h1 class="font-display text-2xl font-semibold text-ink">Create an account</h1>
    <p class="mt-1 text-sm text-ink-muted">
        New accounts start with no dashboard access — an administrator grants a role afterwards.
    </p>

    <form wire:submit="register" class="mt-6 space-y-5">
        <div>
            <x-forms.label for="name">Name</x-forms.label>
            <x-forms.input wire:model="name" id="name" type="text" autocomplete="name" required autofocus />
            <x-forms.error for="name" />
        </div>

        <div>
            <x-forms.label for="email">Email</x-forms.label>
            <x-forms.input wire:model="email" id="email" type="email" autocomplete="username" required />
            <x-forms.error for="email" />
        </div>

        <div>
            <x-forms.label for="password">Password</x-forms.label>
            <x-forms.input wire:model="password" id="password" type="password" autocomplete="new-password" required />
            <x-forms.error for="password" />
            <p class="mt-1.5 text-xs text-ink-faint">At least 10 characters, mixing upper/lowercase and a number.</p>
        </div>

        <div>
            <x-forms.label for="password_confirmation">Confirm password</x-forms.label>
            <x-forms.input wire:model="password_confirmation" id="password_confirmation" type="password" autocomplete="new-password" required />
            <x-forms.error for="password_confirmation" />
        </div>

        <x-forms.button type="submit" class="w-full" wire:loading.attr="disabled" wire:target="register">
            Create account
        </x-forms.button>
    </form>

    <p class="mt-6 text-center text-sm text-ink-muted">
        Already have an account?
        <a href="{{ route('login') }}" wire:navigate class="font-medium text-accent hover:underline">Log in</a>
    </p>
</div>
