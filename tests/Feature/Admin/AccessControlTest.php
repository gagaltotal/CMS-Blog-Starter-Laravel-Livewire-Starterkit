<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccessControlTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_login(): void
    {
        foreach (['admin.dashboard', 'admin.posts.index', 'admin.categories.index', 'admin.users.index', 'admin.settings'] as $route) {
            $this->get(route($route))->assertRedirect(route('login'));
        }
    }

    public function test_accounts_without_a_role_cannot_enter_the_admin_area(): void
    {
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
        $user = User::factory()->create();

        foreach (['admin.dashboard', 'admin.posts.index', 'admin.categories.index', 'admin.users.index', 'admin.settings'] as $route) {
            $this->actingAs($user)->get(route($route))->assertForbidden();
        }
    }

    public function test_unverified_email_is_sent_to_the_verification_notice(): void
    {
        $user = $this->userWithRole('admin', ['email_verified_at' => null]);

        $this->actingAs($user)
            ->get(route('admin.dashboard'))
            ->assertRedirect(route('verification.notice'));
    }

    public function test_author_permissions(): void
    {
        $author = $this->userWithRole('author');

        $this->actingAs($author)->get(route('admin.dashboard'))->assertOk();
        $this->actingAs($author)->get(route('admin.posts.index'))->assertOk();
        $this->actingAs($author)->get(route('admin.posts.create'))->assertOk();

        $this->actingAs($author)->get(route('admin.categories.index'))->assertForbidden();
        $this->actingAs($author)->get(route('admin.users.index'))->assertForbidden();
        $this->actingAs($author)->get(route('admin.settings'))->assertForbidden();
    }

    public function test_editor_permissions(): void
    {
        $editor = $this->userWithRole('editor');

        $this->actingAs($editor)->get(route('admin.posts.index'))->assertOk();
        $this->actingAs($editor)->get(route('admin.categories.index'))->assertOk();

        $this->actingAs($editor)->get(route('admin.users.index'))->assertForbidden();
        $this->actingAs($editor)->get(route('admin.settings'))->assertForbidden();
    }

    public function test_admin_can_open_everything(): void
    {
        $admin = $this->userWithRole('admin');

        foreach (['admin.dashboard', 'admin.posts.index', 'admin.categories.index', 'admin.users.index', 'admin.settings'] as $route) {
            $this->actingAs($admin)->get(route($route))->assertOk();
        }
    }
}
