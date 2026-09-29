<?php

namespace App\Livewire\Auth;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layout.guest')]
class VerifyEmail extends Component
{
    public bool $resent = false;

    public function sendVerification(): void
    {
        if (Auth::user()->hasVerifiedEmail()) {
            $this->redirect(route(Auth::user()->homeRoute(), absolute: false), navigate: true);

            return;
        }

        $key = 'verify-email-resend:'.Auth::id();

        if (RateLimiter::tooManyAttempts($key, maxAttempts: 3)) {
            $this->addError('resend', __('Please wait a moment before requesting another email.'));

            return;
        }

        RateLimiter::hit($key, decaySeconds: 60);

        Auth::user()->sendEmailVerificationNotification();

        $this->resent = true;
    }

    public function render()
    {
        return view('livewire.auth.verify-email');
    }
}
