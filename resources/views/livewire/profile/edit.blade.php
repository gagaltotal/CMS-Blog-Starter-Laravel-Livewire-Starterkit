<div class="mx-auto max-w-2xl px-6 py-12">
    <h1 class="font-display text-3xl font-semibold text-ink">Your account</h1>
    <p class="mt-1 text-sm text-ink-muted">Update your details, change your password, or delete your account.</p>

    {{-- Profile information --}}
    <section class="mt-10 rounded-lg border border-line bg-white/60 p-6">
        <h2 class="font-display text-lg font-semibold">Profile information</h2>

        @if (session('profile-status'))
            <p class="mt-3 rounded-md bg-success-soft px-3.5 py-2.5 text-sm text-success">{{ session('profile-status') }}</p>
        @endif

        <form wire:submit="updateProfileInformation" class="mt-5 space-y-5">
            <div>
                <x-forms.label for="name">Name</x-forms.label>
                <x-forms.input wire:model="name" id="name" type="text" autocomplete="name" required />
                <x-forms.error for="name" />
            </div>

            <div>
                <x-forms.label for="email">Email</x-forms.label>
                <x-forms.input wire:model="email" id="email" type="email" autocomplete="username" required />
                <x-forms.error for="email" />
                @if (! auth()->user()->hasVerifiedEmail())
                    <p class="mt-1.5 text-xs text-warning">Your email address is not verified yet.</p>
                @endif
            </div>

            <x-forms.button type="submit" wire:loading.attr="disabled" wire:target="updateProfileInformation">Save</x-forms.button>
        </form>
    </section>

    {{-- Password --}}
    <section class="mt-6 rounded-lg border border-line bg-white/60 p-6">
        <h2 class="font-display text-lg font-semibold">Change password</h2>
        <p class="mt-1 text-sm text-ink-muted">Changing your password signs you out of every other device.</p>

        @if (session('password-status'))
            <p class="mt-3 rounded-md bg-success-soft px-3.5 py-2.5 text-sm text-success">{{ session('password-status') }}</p>
        @endif

        <form wire:submit="updatePassword" class="mt-5 space-y-5">
            <div>
                <x-forms.label for="current_password">Current password</x-forms.label>
                <x-forms.input wire:model="current_password" id="current_password" type="password" autocomplete="current-password" />
                <x-forms.error for="current_password" />
            </div>

            <div>
                <x-forms.label for="new_password">New password</x-forms.label>
                <x-forms.input wire:model="password" id="new_password" type="password" autocomplete="new-password" />
                <x-forms.error for="password" />
            </div>

            <div>
                <x-forms.label for="new_password_confirmation">Confirm new password</x-forms.label>
                <x-forms.input wire:model="password_confirmation" id="new_password_confirmation" type="password" autocomplete="new-password" />
                <x-forms.error for="password_confirmation" />
            </div>

            <x-forms.button type="submit" wire:loading.attr="disabled" wire:target="updatePassword">Update password</x-forms.button>
        </form>
    </section>

    {{-- Delete account --}}
    <section class="mt-6 rounded-lg border border-danger/30 bg-danger-soft/50 p-6" x-data="{ confirming: false }">
        <h2 class="font-display text-lg font-semibold text-danger">Delete account</h2>
        <p class="mt-1 text-sm text-ink-muted">
            This permanently removes your account. Accounts that still own posts can't be deleted — ask an administrator to reassign them first.
        </p>

        <div class="mt-4" x-show="! confirming">
            <x-forms.button type="button" variant="danger" @click="confirming = true">Delete account</x-forms.button>
        </div>

        <form wire:submit="deleteAccount" class="mt-4 space-y-4" x-show="confirming" style="display: none;">
            <div>
                <x-forms.label for="delete_password">Confirm with your password</x-forms.label>
                <x-forms.input wire:model="delete_password" id="delete_password" type="password" autocomplete="current-password" />
                <x-forms.error for="delete_password" />
            </div>

            <div class="flex items-center gap-3">
                <x-forms.button type="submit" variant="danger" wire:loading.attr="disabled" wire:target="deleteAccount">Permanently delete</x-forms.button>
                <x-forms.button type="button" variant="secondary" @click="confirming = false">Cancel</x-forms.button>
            </div>
        </form>
    </section>
</div>
