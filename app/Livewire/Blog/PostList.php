<?php

namespace App\Livewire\Blog;

use App\Models\Category;
use App\Models\Post;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Public blog index (/blog) and per-category listing
 * (/blog/category/{slug}). Only ever queries published posts, and the
 * search term is bound as a query parameter (see Post::scopeSearch), so
 * neither the search box nor the URL can be used for SQL injection.
 */
#[Layout('components.layout.app')]
class PostList extends Component
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    /**
     * Locked so it can only come from the URL (via mount), never be
     * swapped by a crafted Livewire request.
     */
    #[Locked]
    public ?int $categoryId = null;

    public function mount(?string $category = null): void
    {
        if ($category !== null) {
            $this->categoryId = Category::query()->where('slug', $category)->firstOrFail()->id;
        }
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $category = $this->categoryId !== null
            ? Category::query()->find($this->categoryId)
            : null;

        $posts = Post::query()
            ->published()
            ->with(['category', 'user'])
            ->when($category, fn (Builder $q) => $q->where('category_id', $category->id))
            ->search($this->search)
            ->latest('published_at')
            ->paginate(config('cms.posts_per_page'));

        $categories = Category::query()
            ->withCount(['posts' => fn (Builder $q) => $q->published()])
            ->having('posts_count', '>', 0)
            ->orderBy('name')
            ->get();

        return view('livewire.blog.post-list', [
            'posts' => $posts,
            'category' => $category,
            'categories' => $categories,
        ])->title($category?->name ?? 'Blog');
    }
}
