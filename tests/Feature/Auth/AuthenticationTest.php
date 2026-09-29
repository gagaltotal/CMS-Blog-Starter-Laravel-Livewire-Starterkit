<?php

namespace Tests\Feature\Auth;

use App\Livewire\Auth\Login;
use App\Livewire\Auth\Register;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_renders(): void
    {
        $this->get('/login')->assertOk();
    }

    public function test_user_can_log_in_and_is_sent_to_the_dashboard(): void
    {
        $admin = $this->userWithRole('admin');

        Livewire::test(Login::class)
            ->set('email', $admin->email)
            ->set('password', 'password')
            ->call('login')
            ->assertHasNoErrors()
            ->assertRedirect(route('admin.dashboard'));

        $this->assertAuthenticatedAs($admin);
    }

    public function test_wrong_password_is_rejected(): void
    {
        $user = User::factory()->create();

        Livewire::test(Login::class)
            ->set('email', $user->email)
            ->set('password', 'not-the-password')
            ->call('login')
            ->assertHasErrors('email');

        $this->assertGuest();
    }

    public function test_repeated_failures_lock_the_account_out_even_for_the_right_password(): void
    {
        $user = User::factory()->create();

        for ($i = 0; $i < 5; $i++) {
            Livewire::test(Login::class)
                ->set('email', $user->email)
                ->set('password', 'wrong-'.$i)
                ->call('login');
        }

        // Brute-force protection: the 6th attempt is refused outright, so
        // even the CORRECT password no longer works until the window passes.
        Livewire::test(Login::class)
            ->set('email', $user->email)
            ->set('password', 'password')
            ->call('login')
            ->assertHasErrors('email');

        $this->assertGuest();
    }

    public function test_deactivated_account_is_kicked_out_immediately(): void
    {
        $user = $this->userWithRole('author', ['is_active' => false]);

        $this->actingAs($user)
            ->get(route('admin.dashboard'))
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }

    public function test_logout_ends_the_session(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('logout'))->assertRedirect('/');

        $this->assertGuest();
    }

    public function test_self_registered_accounts_get_no_role_and_no_admin_access(): void
    {
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);

        Livewire::test(Register::class)
            ->set('name', 'Newcomer')
            ->set('email', 'newcomer@example.com')
            ->set('password', 'Str0ngPassw0rd!')
            ->set('password_confirmation', 'Str0ngPassw0rd!')
            ->call('register')
            ->assertHasNoErrors()
            ->assertRedirect(route('profile.edit'));

        $user = User::query()->where('email', 'newcomer@example.com')->firstOrFail();

        $this->assertTrue($user->roles()->doesntExist());
        $this->assertFalse($user->can('view dashboard'));
    }
}
