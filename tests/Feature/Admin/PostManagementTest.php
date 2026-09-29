<?php

namespace Tests\Feature\Admin;

use App\Enums\PostStatus;
use App\Livewire\Admin\Posts\PostForm;
use App\Livewire\Admin\Posts\PostIndex;
use App\Models\Category;
use App\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class PostManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_author_can_create_a_post_and_becomes_its_owner(): void
    {
        $author = $this->userWithRole('author');
        $category = Category::factory()->create();

        Livewire::actingAs($author)->test(PostForm::class)
            ->set('title', 'My first post')
            ->set('body', 'This body is comfortably longer than ten characters.')
            ->set('categoryId', (string) $category->id)
            ->set('tagList', 'php, laravel, PHP')
            ->call('save')
            ->assertHasNoErrors();

        $post = Post::query()->where('title', 'My first post')->firstOrFail();

        $this->assertSame($author->id, $post->user_id);
        $this->assertSame('my-first-post', $post->slug);
        $this->assertSame(['laravel', 'php'], $post->tags()->pluck('slug')->sort()->values()->all());
    }

    public function test_author_cannot_publish_even_if_the_request_is_tampered_with(): void
    {
        $author = $this->userWithRole('author');

        // The UI hides the status control from authors, but a crafted
        // request can still set the property. The server must ignore it.
        Livewire::actingAs($author)->test(PostForm::class)
            ->set('title', 'Sneaky post')
            ->set('body', 'This body is comfortably longer than ten characters.')
            ->set('status', 'published')
            ->call('save')
            ->assertHasNoErrors();

        $post = Post::query()->where('title', 'Sneaky post')->firstOrFail();

        $this->assertSame(PostStatus::Draft, $post->status);
        $this->assertNull($post->published_at);
    }

    public function test_editor_can_publish(): void
    {
        $editor = $this->userWithRole('editor');

        Livewire::actingAs($editor)->test(PostForm::class)
            ->set('title', 'Editor post')
            ->set('body', 'This body is comfortably longer than ten characters.')
            ->set('status', 'published')
            ->call('save')
            ->assertHasNoErrors();

        $post = Post::query()->where('title', 'Editor post')->firstOrFail();

        $this->assertSame(PostStatus::Published, $post->status);
        $this->assertNotNull($post->published_at);
    }

    public function test_author_cannot_open_or_edit_someone_elses_post(): void
    {
        $author = $this->userWithRole('author');
        $other = $this->userWithRole('author');
        $theirPost = Post::factory()->for($other, 'user')->create();

        $this->actingAs($author)
            ->get(route('admin.posts.edit', ['post' => $theirPost->id]))
            ->assertForbidden();

        Livewire::actingAs($author)->test(PostForm::class, ['post' => $theirPost->id])
            ->assertForbidden();
    }

    public function test_author_cannot_delete_someone_elses_post_via_a_direct_livewire_call(): void
    {
        $author = $this->userWithRole('author');
        $other = $this->userWithRole('author');
        $theirPost = Post::factory()->for($other, 'user')->create();

        Livewire::actingAs($author)->test(PostIndex::class)
            ->call('confirmDelete', $theirPost->id)
            ->assertForbidden();

        $this->assertModelExists($theirPost);
    }

    public function test_author_only_sees_their_own_posts_in_the_list(): void
    {
        $author = $this->userWithRole('author');
        $other = $this->userWithRole('author');
        $mine = Post::factory()->for($author, 'user')->create(['title' => 'Written by me']);
        Post::factory()->for($other, 'user')->create(['title' => 'Written by someone else']);

        Livewire::actingAs($author)->test(PostIndex::class)
            ->assertSee($mine->title)
            ->assertDontSee('Written by someone else');
    }

    public function test_the_edited_post_id_is_locked_against_tampering(): void
    {
        $author = $this->userWithRole('author');
        $mine = Post::factory()->for($author, 'user')->create();
        $theirs = Post::factory()->for($this->userWithRole('author'), 'user')->create();

        $component = Livewire::actingAs($author)->test(PostForm::class, ['post' => $mine->id]);

        try {
            $component->set('postId', $theirs->id);
            $this->fail('Changing the locked postId property should have been rejected.');
        } catch (\Throwable $e) {
            $this->assertStringContainsString('locked', strtolower($e->getMessage()));
        }
    }

    public function test_only_editors_and_admins_can_toggle_publication_from_the_list(): void
    {
        $author = $this->userWithRole('author');
        $post = Post::factory()->draft()->for($author, 'user')->create();

        Livewire::actingAs($author)->test(PostIndex::class)
            ->call('togglePublish', $post->id)
            ->assertForbidden();

        $this->assertSame(PostStatus::Draft, $post->fresh()->status);

        $editor = $this->userWithRole('editor');

        Livewire::actingAs($editor)->test(PostIndex::class)
            ->call('togglePublish', $post->id);

        $this->assertSame(PostStatus::Published, $post->fresh()->status);
    }

    public function test_executable_and_non_image_uploads_are_rejected(): void
    {
        Storage::fake('local');
        $author = $this->userWithRole('author');

        $component = Livewire::actingAs($author)->test(PostForm::class);

        // A real PHP web shell.
        $component->set('newImage', UploadedFile::fake()->createWithContent('shell.php', '<?php system($_GET["c"]); ?>'))
            ->assertHasErrors('newImage');

        // The same shell wearing an image extension and image MIME type.
        $component->set('newImage', UploadedFile::fake()->createWithContent('shell.jpg', '<?php system($_GET["c"]); ?>'))
            ->assertHasErrors('newImage');

        // An SVG carrying a script: SVG is deliberately not an allowed type.
        $svg = '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>';
        $component->set('newImage', UploadedFile::fake()->createWithContent('logo.svg', $svg))
            ->assertHasErrors('newImage');

        // HTML pretending to be a JPEG.
        $component->set('newImage', UploadedFile::fake()->createWithContent('page.jpg', '<html><script>alert(1)</script></html>'))
            ->assertHasErrors('newImage');
    }

    public function test_a_real_image_is_accepted_and_stored_under_a_random_name(): void
    {
        if (! extension_loaded('gd')) {
            $this->markTestSkipped('The GD extension is required to generate a fake image.');
        }

        Storage::fake('local');
        Storage::fake('public');
        $editor = $this->userWithRole('editor');

        Livewire::actingAs($editor)->test(PostForm::class)
            ->set('title', 'With image')
            ->set('body', 'This body is comfortably longer than ten characters.')
            ->set('newImage', UploadedFile::fake()->image('../../evil-name.jpg', 600, 400))
            ->call('save')
            ->assertHasNoErrors();

        $post = Post::query()->where('title', 'With image')->firstOrFail();

        $this->assertNotNull($post->featured_image);
        $this->assertStringStartsWith('posts/', $post->featured_image);
        $this->assertStringNotContainsString('evil-name', $post->featured_image);
        $this->assertStringNotContainsString('..', $post->featured_image);
        Storage::disk('public')->assertExists($post->featured_image);
    }
}
