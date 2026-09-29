<div>
    <h1 class="font-display text-2xl font-semibold text-ink">Forgot your password?</h1>
    <p class="mt-1 text-sm text-ink-muted">
        Enter your email and we'll send you a link to reset it.
    </p>

    @if ($status)
        <p class="mt-4 rounded-md bg-success-soft px-3.5 py-2.5 text-sm text-success">{{ $status }}</p>
    @endif

    <form wire:submit="sendPasswordResetLink" class="mt-6 space-y-5">
        <div>
            <x-forms.label for="email">Email</x-forms.label>
            <x-forms.input wire:model="email" id="email" type="email" autocomplete="username" required autofocus />
            <x-forms.error for="email" />
        </div>

        <x-forms.button type="submit" class="w-full" wire:loading.attr="disabled" wire:target="sendPasswordResetLink">
            Email password reset link
        </x-forms.button>
    </form>

    <p class="mt-6 text-center text-sm text-ink-muted">
        <a href="{{ route('login') }}" wire:navigate class="font-medium text-accent hover:underline">Back to login</a>
    </p>
</div>
