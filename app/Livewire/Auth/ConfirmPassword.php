<?php

namespace App\Livewire\Auth;

use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * A lightweight re-authentication step ("sudo mode") shown before very
 * sensitive actions. This starter doesn't gate anything behind it out of
 * the box, but the route/middleware ('password.confirm') is wired up and
 * ready — see routes/auth.php — for whenever you add something (e.g.
 * deleting an account, changing a payment method) that should require it.
 */
#[Layout('components.layout.guest')]
class ConfirmPassword extends Component
{
    public string $password = '';

    public function confirmPassword(): void
    {
        $this->validate(['password' => ['required', 'string']]);

        if (! Auth::guard('web')->validate([
            'email' => Auth::user()->email,
            'password' => $this->password,
        ])) {
            throw ValidationException::withMessages([
                'password' => __('This password does not match our records.'),
            ]);
        }

        session(['auth.password_confirmed_at' => time()]);

        $intended = session()->pull('url.intended', route(Auth::user()->homeRoute(), absolute: false));

        $this->redirect($intended, navigate: true);
    }

    public function render()
    {
        return view('livewire.auth.confirm-password');
    }
}
