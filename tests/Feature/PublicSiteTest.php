<?php

namespace Tests\Feature;

use App\Livewire\Blog\PostList;
use App\Models\Category;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PublicSiteTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_page_lists_published_posts_but_not_drafts(): void
    {
        $author = User::factory()->create();
        Post::factory()->for($author, 'user')->create(['title' => 'A published story']);
        Post::factory()->draft()->for($author, 'user')->create(['title' => 'A secret draft']);

        $this->get('/')
            ->assertOk()
            ->assertSee('A published story')
            ->assertDontSee('A secret draft');
    }

    public function test_a_draft_is_not_reachable_by_url_even_when_logged_in_as_its_author(): void
    {
        $author = $this->userWithRole('author');
        $draft = Post::factory()->draft()->for($author, 'user')->create();

        $this->get(route('blog.show', ['post' => $draft->slug]))->assertNotFound();
        $this->actingAs($author)->get(route('blog.show', ['post' => $draft->slug]))->assertNotFound();
    }

    public function test_a_future_dated_post_is_not_public_yet(): void
    {
        $post = Post::factory()->for(User::factory()->create(), 'user')
            ->create(['published_at' => now()->addDay()]);

        $this->get(route('blog.show', ['post' => $post->slug]))->assertNotFound();
    }

    public function test_reading_a_post_counts_a_view(): void
    {
        $post = Post::factory()->for(User::factory()->create(), 'user')->create(['views_count' => 4]);

        $this->get(route('blog.show', ['post' => $post->slug]))->assertOk()->assertSee($post->title);

        $this->assertSame(5, $post->fresh()->views_count);
    }

    public function test_blog_index_search_filters_results(): void
    {
        $author = User::factory()->create();
        Post::factory()->for($author, 'user')->create(['title' => 'Understanding Livewire']);
        Post::factory()->for($author, 'user')->create(['title' => 'Notes on gardening']);

        Livewire::test(PostList::class)
            ->set('search', 'Livewire')
            ->assertSee('Understanding Livewire')
            ->assertDontSee('Notes on gardening');
    }

    public function test_category_page_only_shows_that_categorys_posts(): void
    {
        $author = User::factory()->create();
        $tech = Category::factory()->create(['name' => 'Tech']);
        $life = Category::factory()->create(['name' => 'Life']);
        Post::factory()->for($author, 'user')->for($tech, 'category')->create(['title' => 'Tech post']);
        Post::factory()->for($author, 'user')->for($life, 'category')->create(['title' => 'Life post']);

        $this->get(route('blog.category', ['category' => $tech->slug]))
            ->assertOk()
            ->assertSee('Tech post')
            ->assertDontSee('Life post');

        $this->get(route('blog.category', ['category' => 'no-such-category']))->assertNotFound();
    }

    public function test_sql_injection_attempts_in_search_are_treated_as_plain_text(): void
    {
        Post::factory()->for(User::factory()->create(), 'user')->create(['title' => 'Harmless post']);

        foreach (["' OR '1'='1", "'; DROP TABLE posts; --", '%', '\\', '" UNION SELECT * FROM users --'] as $payload) {
            Livewire::test(PostList::class)
                ->set('search', $payload)
                ->assertHasNoErrors();
        }

        // The table is intact and the row still exists.
        $this->assertDatabaseCount('posts', 1);
    }

    public function test_stored_xss_in_a_post_body_is_neutralised_on_the_public_page(): void
    {
        $post = Post::factory()->for(User::factory()->create(), 'user')->create([
            'title' => 'Innocent title',
            'body' => "Hello <script>alert('pwn')</script>\n\n"
                ."[click me](javascript:alert(1))\n\n"
                .'<img src=x onerror=alert(2)>'."\n\n"
                .'<iframe src="https://evil.example"></iframe>',
        ]);

        $html = $this->get(route('blog.show', ['post' => $post->slug]))->assertOk()->getContent();

        $this->assertStringNotContainsString("<script>alert('pwn')", $html);
        $this->assertStringNotContainsString('javascript:alert', $html);
        $this->assertStringNotContainsString('onerror', $html);
        $this->assertStringNotContainsString('<iframe', $html);
    }

    public function test_stored_xss_in_a_post_title_is_escaped(): void
    {
        $post = Post::factory()->for(User::factory()->create(), 'user')->create([
            'title' => '<script>alert(1)</script>',
        ]);

        $this->get(route('blog.show', ['post' => $post->slug]))
            ->assertOk()
            ->assertDontSee('<script>alert(1)</script>', false)
            ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false);
    }
}
