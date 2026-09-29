<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\View\View;

class BlogController extends Controller
{
    public function show(Post $post): View
    {
        // A draft (or a post scheduled in the future) is not publicly
        // reachable no matter who requests it — there is deliberately no
        // "preview as author" bypass here, keeping this controller free of
        // any per-user branching to reason about. Authors/editors preview
        // their own drafts from the admin post list instead.
        abort_unless($post->isPublished(), 404);

        $post->loadMissing(['category', 'user', 'tags']);

        // A lightweight, best-effort counter — not meant to be a precise
        // analytics figure, so no locking/transaction overhead here.
        $post->increment('views_count');

        $related = Post::query()
            ->published()
            ->with(['category', 'user'])
            ->when($post->category_id, fn ($query) => $query->where('category_id', $post->category_id))
            ->whereKeyNot($post->id)
            ->latest('published_at')
            ->take(3)
            ->get();

        return view('blog.show', [
            'post' => $post,
            'related' => $related,
        ]);
    }
}
