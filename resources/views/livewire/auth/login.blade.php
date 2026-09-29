<div>
    <h1 class="font-display text-2xl font-semibold text-ink">Welcome back</h1>
    <p class="mt-1 text-sm text-ink-muted">Log in to manage your content.</p>

    @if (session('status'))
        <p class="mt-4 rounded-md bg-success-soft px-3.5 py-2.5 text-sm text-success">{{ session('status') }}</p>
    @endif

    <form wire:submit="login" class="mt-6 space-y-5">
        <div>
            <x-forms.label for="email">Email</x-forms.label>
            <x-forms.input wire:model="email" id="email" type="email" autocomplete="username" required autofocus />
            <x-forms.error for="email" />
        </div>

        <div>
            <x-forms.label for="password">Password</x-forms.label>
            <x-forms.input wire:model="password" id="password" type="password" autocomplete="current-password" required />
            <x-forms.error for="password" />
        </div>

        <div class="flex items-center justify-between">
            <label class="flex items-center gap-2 text-sm text-ink-muted">
                <input wire:model="remember" type="checkbox" class="rounded border-line text-accent focus:ring-accent">
                Remember me
            </label>

            <a href="{{ route('password.request') }}" wire:navigate class="text-sm text-accent hover:underline">
                Forgot password?
            </a>
        </div>

        <x-forms.button type="submit" class="w-full" wire:loading.attr="disabled" wire:target="login">
            <span wire:loading.remove wire:target="login">Log in</span>
            <span wire:loading wire:target="login">Logging in…</span>
        </x-forms.button>
    </form>

    <p class="mt-6 text-center text-sm text-ink-muted">
        Don't have an account?
        <a href="{{ route('register') }}" wire:navigate class="font-medium text-accent hover:underline">Sign up</a>
    </p>
</div>
