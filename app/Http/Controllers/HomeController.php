<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Post;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __invoke(): View
    {
        $featured = Post::query()
            ->published()
            ->with(['category', 'user'])
            ->latest('published_at')
            ->first();

        $latest = Post::query()
            ->published()
            ->with(['category', 'user'])
            ->when($featured, fn ($query) => $query->whereKeyNot($featured->id))
            ->latest('published_at')
            ->take(6)
            ->get();

        $categories = Category::query()
            ->withCount(['posts' => fn ($query) => $query->published()])
            ->having('posts_count', '>', 0)
            ->orderByDesc('posts_count')
            ->take(6)
            ->get();

        return view('home', [
            'featured' => $featured,
            'latest' => $latest,
            'categories' => $categories,
        ]);
    }
}
