@props(['post'])

{{--
    One post in an editorial-style list. Expects `category` and `user` to be
    eager-loaded by the caller (lazy loading is disabled outside production
    to catch N+1 queries early).
--}}
<article {{ $attributes->class('py-8 first:pt-0 last:pb-0') }}>
    <div class="flex items-start gap-6">
        <div class="min-w-0 flex-1">
            @if ($post->category)
                <a href="{{ route('blog.category', ['category' => $post->category->slug]) }}" wire:navigate
                   class="text-sm font-medium text-accent hover:underline">{{ $post->category->name }}</a>
            @endif

            <h3 class="mt-1 font-display text-2xl font-semibold leading-snug text-ink">
                <a href="{{ route('blog.show', ['post' => $post->slug]) }}" wire:navigate class="hover:text-accent">{{ $post->title }}</a>
            </h3>

            @if ($post->excerpt)
                <p class="mt-2 line-clamp-2 text-ink-muted">{{ $post->excerpt }}</p>
            @endif

            <p class="mt-3 text-sm text-ink-faint">
                {{ $post->user->name }} on
                <time datetime="{{ $post->published_at->toIso8601String() }}">{{ $post->published_at->format('M j, Y') }}</time>,
                {{ $post->reading_time }} min read
            </p>
        </div>

        @if ($post->featured_image_url)
            <a href="{{ route('blog.show', ['post' => $post->slug]) }}" wire:navigate class="hidden shrink-0 sm:block" tabindex="-1" aria-hidden="true">
                <img src="{{ $post->featured_image_url }}" alt="" loading="lazy" class="h-28 w-40 rounded-md object-cover">
            </a>
        @endif
    </div>
</article>
