<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\Users\UserManager;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_non_admins_cannot_use_the_user_manager(): void
    {
        foreach (['editor', 'author'] as $role) {
            Livewire::actingAs($this->userWithRole($role))
                ->test(UserManager::class)
                ->assertForbidden();
        }
    }

    public function test_admin_can_create_a_user_with_a_role(): void
    {
        $admin = $this->userWithRole('admin');

        Livewire::actingAs($admin)->test(UserManager::class)
            ->call('create')
            ->set('name', 'New Editor')
            ->set('email', 'new.editor@example.com')
            ->set('role', 'editor')
            ->set('password', 'Str0ngPassw0rd!')
            ->call('save')
            ->assertHasNoErrors();

        $created = User::query()->where('email', 'new.editor@example.com')->firstOrFail();

        $this->assertTrue($created->hasRole('editor'));
        $this->assertTrue($created->is_active);
        $this->assertTrue($created->hasVerifiedEmail());
        $this->assertNotSame('Str0ngPassw0rd!', $created->password, 'Passwords must be stored hashed.');
    }

    public function test_a_role_that_does_not_exist_cannot_be_assigned(): void
    {
        $admin = $this->userWithRole('admin');

        Livewire::actingAs($admin)->test(UserManager::class)
            ->call('create')
            ->set('name', 'Someone')
            ->set('email', 'someone@example.com')
            ->set('role', 'super-admin')
            ->set('password', 'Str0ngPassw0rd!')
            ->call('save')
            ->assertHasErrors('role');

        $this->assertDatabaseMissing('users', ['email' => 'someone@example.com']);
    }

    public function test_an_admin_cannot_change_their_own_role(): void
    {
        $admin = $this->userWithRole('admin');
        $this->userWithRole('admin'); // a second admin exists, so "last admin" is not the reason

        Livewire::actingAs($admin)->test(UserManager::class)
            ->call('edit', $admin->id)
            ->set('role', 'author')
            ->call('save')
            ->assertForbidden();

        $this->assertTrue($admin->fresh()->hasRole('admin'));
    }

    public function test_an_admin_cannot_deactivate_themselves(): void
    {
        $admin = $this->userWithRole('admin');
        $this->userWithRole('admin');

        Livewire::actingAs($admin)->test(UserManager::class)
            ->call('edit', $admin->id)
            ->set('isActive', false)
            ->call('save')
            ->assertHasErrors('isActive');

        $this->assertTrue($admin->fresh()->is_active);
    }

    /**
     * Defense in depth: today only admins can manage users, so the "last
     * admin" rule can't be reached from the UI. It exists for the day a
     * non-admin role (e.g. a "manager") is granted `manage users`: that
     * role must still be unable to strip or remove the only administrator.
     */
    public function test_the_last_active_admin_is_protected_even_from_other_user_managers(): void
    {
        $onlyAdmin = $this->userWithRole('admin');

        $managerRole = Role::findOrCreate('manager', 'web');
        $managerRole->givePermissionTo(['view dashboard', 'manage users']);

        $manager = User::factory()->create();
        $manager->assignRole($managerRole);

        // Demote
        Livewire::actingAs($manager)->test(UserManager::class)
            ->call('edit', $onlyAdmin->id)
            ->set('role', 'author')
            ->call('save')
            ->assertHasErrors('role');

        $this->assertTrue($onlyAdmin->fresh()->hasRole('admin'));

        // Deactivate
        Livewire::actingAs($manager)->test(UserManager::class)
            ->call('edit', $onlyAdmin->id)
            ->set('isActive', false)
            ->call('save')
            ->assertHasErrors('isActive');

        $this->assertTrue($onlyAdmin->fresh()->is_active);

        // Delete
        Livewire::actingAs($manager)->test(UserManager::class)
            ->call('confirmDelete', $onlyAdmin->id)
            ->assertForbidden();

        $this->assertModelExists($onlyAdmin);
    }

    public function test_a_user_who_owns_posts_cannot_be_deleted(): void
    {
        $admin = $this->userWithRole('admin');
        $author = $this->userWithRole('author');
        Post::factory()->for($author, 'user')->create();

        Livewire::actingAs($admin)->test(UserManager::class)
            ->call('confirmDelete', $author->id)
            ->call('delete');

        $this->assertModelExists($author);
    }

    public function test_deleting_an_account_without_posts_works(): void
    {
        $admin = $this->userWithRole('admin');
        $author = $this->userWithRole('author');

        Livewire::actingAs($admin)->test(UserManager::class)
            ->call('confirmDelete', $author->id)
            ->call('delete');

        $this->assertModelMissing($author);
    }
}
