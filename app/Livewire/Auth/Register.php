<?php

namespace App\Livewire\Auth;

use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layout.guest')]
class Register extends Component
{
    public string $name = '';

    public string $email = '';

    public string $password = '';

    public string $password_confirmation = '';

    public function register(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $validated['password'] = Hash::make($validated['password']);

        $user = User::create($validated);

        // No role is assigned here on purpose. A freshly self-registered
        // account has zero permissions in this CMS until an existing admin
        // grants one from Admin > Users — see UserPolicy and the
        // RolesAndPermissionsSeeder for the full model.
        event(new Registered($user));

        Auth::login($user);

        $this->redirect(route($user->homeRoute(), absolute: false), navigate: true);
    }

    public function render()
    {
        return view('livewire.auth.register');
    }
}
