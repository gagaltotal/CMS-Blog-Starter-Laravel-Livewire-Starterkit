<?php

namespace App\Livewire\Auth;

use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layout.guest')]
class ForgotPassword extends Component
{
    public string $email = '';

    public ?string $status = null;

    public function sendPasswordResetLink(): void
    {
        $this->validate(['email' => ['required', 'string', 'email']]);

        // Laravel's password broker is itself rate-limited (see
        // config/auth.php `passwords.users.throttle`) and always takes the
        // same amount of time / returns a generic status whether or not
        // the address exists, which is what stops this form being used to
        // enumerate registered email addresses.
        $status = Password::sendResetLink($this->only('email'));

        if ($status !== Password::RESET_LINK_SENT) {
            throw ValidationException::withMessages(['email' => __($status)]);
        }

        $this->status = __($status);
        $this->reset('email');
    }

    public function render()
    {
        return view('livewire.auth.forgot-password');
    }
}
