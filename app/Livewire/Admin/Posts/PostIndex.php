<?php

namespace App\Livewire\Admin\Posts;

use App\Enums\PostStatus;
use App\Models\Category;
use App\Models\Post;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layout.admin')]
#[Title('Posts')]
class PostIndex extends Component
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url]
    public string $status = '';

    #[Url]
    public string $category = '';

    /**
     * Locked: this can only ever be set by confirmDelete() on the server,
     * never by a crafted browser request. Without #[Locked], anyone could
     * send an update that swaps this id for a different post's id between
     * the "confirm" click and the actual delete.
     */
    #[Locked]
    public ?int $confirmingDeleteId = null;

    #[Locked]
    public ?string $notice = null;

    public function updatingSearch(): void
    {
        $this->resetPage();
        $this->notice = null;
    }

    public function updatingStatus(): void
    {
        $this->resetPage();
        $this->notice = null;
    }

    public function updatingCategory(): void
    {
        $this->resetPage();
        $this->notice = null;
    }

    public function confirmDelete(int $postId): void
    {
        $post = Post::query()->findOrFail($postId);

        $this->authorize('delete', $post);

        $this->confirmingDeleteId = $post->id;
    }

    public function cancelDelete(): void
    {
        $this->confirmingDeleteId = null;
    }

    public function delete(): void
    {
        abort_if($this->confirmingDeleteId === null, 422);

        $post = Post::query()->findOrFail($this->confirmingDeleteId);

        // Authorised again here, in the action itself: a check made when
        // the confirm dialog opened says nothing about who is calling this
        // method now. Every state-changing Livewire action re-checks.
        $this->authorize('delete', $post);

        if ($post->featured_image) {
            Storage::disk('public')->delete($post->featured_image);
        }

        $post->delete();

        $this->confirmingDeleteId = null;
        $this->notice = 'Post deleted.';
    }

    public function togglePublish(int $postId): void
    {
        $post = Post::query()->findOrFail($postId);

        $this->authorize('update', $post);
        $this->authorize('publish', $post);

        if ($post->status === PostStatus::Published) {
            $post->status = PostStatus::Draft;
            $this->notice = 'Post moved back to draft.';
        } else {
            $post->status = PostStatus::Published;
            $this->notice = 'Post published.';
        }

        $post->save();
    }

    public function render()
    {
        $user = Auth::user();

        $statusFilter = PostStatus::tryFrom($this->status);

        $posts = Post::query()
            ->with(['category', 'user'])
            ->when(! $user->can('manage all posts'), fn (Builder $q) => $q->where('user_id', $user->id))
            ->search($this->search)
            ->when($statusFilter, fn (Builder $q) => $q->where('status', $statusFilter))
            ->when($this->category !== '', fn (Builder $q) => $q->where('category_id', (int) $this->category))
            ->latest('updated_at')
            ->paginate(config('cms.admin_posts_per_page'));

        return view('livewire.admin.posts.post-index', [
            'posts' => $posts,
            'categories' => Category::query()->orderBy('name')->get(['id', 'name']),
            'canPublish' => $user->can('publish posts'),
        ]);
    }
}
