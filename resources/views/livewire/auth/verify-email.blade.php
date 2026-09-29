<div class="text-center">
    <h1 class="font-display text-2xl font-semibold text-ink">Verify your email</h1>
    <p class="mt-2 text-sm text-ink-muted">
        Thanks for signing up! Before getting started, click the link we just emailed you to confirm your address.
    </p>

    @if ($resent)
        <p class="mt-4 rounded-md bg-success-soft px-3.5 py-2.5 text-sm text-success">
            A new verification link has been sent to your email address.
        </p>
    @endif

    @error('resend')
        <p class="mt-4 rounded-md bg-danger-soft px-3.5 py-2.5 text-sm text-danger">{{ $message }}</p>
    @enderror

    <p class="mt-2 text-xs text-ink-faint">
        Running locally with <code>MAIL_MAILER=log</code>? Check <code>storage/logs/laravel.log</code> for the link.
    </p>

    <div class="mt-6 flex flex-col items-center gap-3">
        <x-forms.button wire:click="sendVerification" wire:loading.attr="disabled" wire:target="sendVerification">
            Resend verification email
        </x-forms.button>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="text-sm text-ink-muted hover:text-ink">Log out</button>
        </form>
    </div>
</div>
