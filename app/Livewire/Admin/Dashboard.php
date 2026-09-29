<?php

namespace App\Livewire\Admin;

use App\Enums\PostStatus;
use App\Models\Category;
use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layout.admin')]
#[Title('Dashboard')]
class Dashboard extends Component
{
    public function render()
    {
        $user = Auth::user();

        // Numbers are scoped to what this user is allowed to see: someone
        // who may only manage their own posts never gets site-wide totals
        // (which would leak other authors' draft counts, etc.).
        $scopedPosts = fn (): Builder => Post::query()
            ->when(! $user->can('manage all posts'), fn (Builder $q) => $q->where('user_id', $user->id));

        $stats = [
            'posts' => $scopedPosts()->count(),
            'published' => $scopedPosts()->where('status', PostStatus::Published)->count(),
            'drafts' => $scopedPosts()->where('status', PostStatus::Draft)->count(),
            'views' => (int) $scopedPosts()->sum('views_count'),
        ];

        if ($user->can('manage categories')) {
            $stats['categories'] = Category::query()->count();
        }

        if ($user->can('manage users')) {
            $stats['users'] = User::query()->count();
        }

        $recent = $scopedPosts()
            ->with(['category', 'user'])
            ->latest('updated_at')
            ->take(6)
            ->get();

        return view('livewire.admin.dashboard', [
            'stats' => $stats,
            'recent' => $recent,
            'canCreate' => $user->can('create', Post::class),
        ]);
    }
}
