<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class PostSeeder extends Seeder
{
    public function run(): void
    {
        $editor = $this->demoUser('editor@example.com', 'Erin the Editor', 'EditorPass123!', 'editor');
        $author = $this->demoUser('author@example.com', 'Alex the Author', 'AuthorPass123!', 'author');

        $this->command?->info('Demo accounts: editor@example.com / EditorPass123!, author@example.com / AuthorPass123!');

        $categories = Category::all();

        if ($categories->isEmpty()) {
            $this->command?->warn('No categories found — run CategorySeeder first. Skipping post seeding.');

            return;
        }

        $tagNames = ['laravel', 'php', 'tailwind', 'livewire', 'productivity', 'design-systems', 'security', 'sql'];
        $tags = collect($tagNames)->map(
            fn (string $name) => Tag::query()->firstOrCreate(['slug' => Str::slug($name)], ['name' => $name])
        );

        $authors = [$editor, $author];

        // ->for() explicitly attaches an EXISTING model to the relationship
        // instead of letting the factory's own definition spin up a brand
        // new random User/Category for every post — that would otherwise
        // happen silently since Post's definition() uses nested factories
        // as its fallback for user_id/category_id.
        for ($index = 0; $index < 18; $index++) {
            $post = Post::factory()
                ->for($categories[$index % $categories->count()], 'category')
                ->for($authors[$index % count($authors)], 'user')
                ->create();

            $post->tags()->sync($tags->random(rand(1, 3))->pluck('id'));
        }

        // A couple of drafts so the admin list demonstrates both states
        // immediately, all authored by the demo Author account.
        for ($index = 0; $index < 3; $index++) {
            Post::factory()
                ->draft()
                ->for($categories[$index % $categories->count()], 'category')
                ->for($author, 'user')
                ->create();
        }

        $this->command?->info('Seeded 18 published posts and 3 drafts.');
    }

    /**
     * Creates a demo account. forceFill because email_verified_at and
     * is_active are intentionally not mass-assignable on User.
     */
    private function demoUser(string $email, string $name, string $password, string $role): User
    {
        $user = User::query()->firstOrNew(['email' => $email]);

        if (! $user->exists) {
            $user->forceFill([
                'name' => $name,
                'password' => Hash::make($password),
                'email_verified_at' => now(),
                'is_active' => true,
            ])->save();
        }

        $user->syncRoles([$role]);

        return $user;
    }
}
