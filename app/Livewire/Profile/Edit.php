<?php

namespace App\Livewire\Profile;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * One self-service account page for every authenticated user, regardless
 * of CMS role — even an account with no role (a fresh self-registration)
 * can still update its own details. Identity is always taken from
 * Auth::user(), never from a client-supplied property, so none of the
 * public properties below can be tampered with to edit someone else.
 */
#[Layout('components.layout.app')]
class Edit extends Component
{
    public string $name = '';

    public string $email = '';

    public string $current_password = '';

    public string $password = '';

    public string $password_confirmation = '';

    public string $delete_password = '';

    public function mount(): void
    {
        $user = Auth::user();

        $this->name = $user->name;
        $this->email = $user->email;
    }

    public function updateProfileInformation(): void
    {
        $user = Auth::user();

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required', 'string', 'lowercase', 'email', 'max:255',
                Rule::unique(User::class)->ignore($user->id),
            ],
        ]);

        $user->fill($validated);

        $emailChanged = $user->isDirty('email');

        if ($emailChanged) {
            // A changed address is unverified until proven — and since the
            // admin area requires a verified email, this also means an
            // account can't be silently re-pointed at an address the
            // owner doesn't control and keep its access.
            $user->email_verified_at = null;
        }

        $user->save();

        if ($emailChanged) {
            $user->sendEmailVerificationNotification();
        }

        session()->flash('profile-status', 'Profile updated.');
    }

    public function updatePassword(): void
    {
        try {
            $validated = $this->validate([
                'current_password' => ['required', 'string', 'current_password'],
                'password' => ['required', 'string', Password::defaults(), 'confirmed'],
            ]);
        } catch (ValidationException $e) {
            $this->reset('current_password', 'password', 'password_confirmation');

            throw $e;
        }

        Auth::user()->update([
            'password' => Hash::make($validated['password']),
        ]);

        // Invalidate every *other* device/browser session for this account,
        // so a stolen session cookie stops working the moment the owner
        // changes their password.
        Auth::logoutOtherDevices($validated['password']);

        $this->reset('current_password', 'password', 'password_confirmation');

        session()->flash('password-status', 'Password updated.');
    }

    public function deleteAccount(): void
    {
        $this->validate([
            'delete_password' => ['required', 'string', 'current_password'],
        ]);

        $user = Auth::user();

        // posts.user_id is RESTRICT (see the posts migration): deleting an
        // author who still has content would either fail with a raw
        // database error or, worse, destroy that content. Refuse up front
        // with a clear message instead — an admin can reassign or remove
        // the posts first.
        if ($user->posts()->exists()) {
            $this->addError(
                'delete_password',
                'This account still owns posts, so it cannot be deleted. Ask an administrator to reassign or remove them first.'
            );

            return;
        }

        Auth::logout();

        $user->delete();

        session()->invalidate();
        session()->regenerateToken();

        $this->redirect('/', navigate: true);
    }

    public function render()
    {
        return view('livewire.profile.edit');
    }
}
