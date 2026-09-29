<?php

namespace Tests\Feature;

use App\Livewire\Admin\Settings\SettingsForm;
use App\Livewire\Profile\Edit as ProfileEdit;
use App\Models\Post;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class ProfileAndSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_admins_can_change_site_settings(): void
    {
        Livewire::actingAs($this->userWithRole('editor'))
            ->test(SettingsForm::class)
            ->assertForbidden();

        Livewire::actingAs($this->userWithRole('admin'))
            ->test(SettingsForm::class)
            ->set('siteName', 'My Great Blog')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('My Great Blog', Setting::siteName());
    }

    public function test_changing_the_password_requires_the_current_one(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)->test(ProfileEdit::class)
            ->set('current_password', 'not-my-password')
            ->set('password', 'NewStr0ngPass!')
            ->set('password_confirmation', 'NewStr0ngPass!')
            ->call('updatePassword')
            ->assertHasErrors('current_password');

        $this->assertTrue(Hash::check('password', $user->fresh()->password));
    }

    public function test_deleting_an_account_requires_the_password(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)->test(ProfileEdit::class)
            ->set('delete_password', 'wrong')
            ->call('deleteAccount')
            ->assertHasErrors('delete_password');

        $this->assertModelExists($user);
    }

    public function test_an_account_that_owns_posts_cannot_delete_itself(): void
    {
        $author = $this->userWithRole('author');
        Post::factory()->for($author, 'user')->create();

        Livewire::actingAs($author)->test(ProfileEdit::class)
            ->set('delete_password', 'password')
            ->call('deleteAccount')
            ->assertHasErrors('delete_password');

        $this->assertModelExists($author);
    }

    public function test_an_account_without_posts_can_delete_itself(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)->test(ProfileEdit::class)
            ->set('delete_password', 'password')
            ->call('deleteAccount');

        $this->assertModelMissing($user);
    }
}
