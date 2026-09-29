<x-layout.app :title="$post->meta_title ?: $post->title" :meta-description="$post->meta_description ?: $post->excerpt">

    <article class="mx-auto max-w-5xl px-6 py-12 sm:py-16">
        <header class="mx-auto max-w-[68ch]">
            @if ($post->category)
                <a href="{{ route('blog.category', ['category' => $post->category->slug]) }}" wire:navigate
                   class="text-sm font-medium text-accent hover:underline">{{ $post->category->name }}</a>
            @endif

            <h1 class="mt-2 font-display text-4xl font-semibold leading-tight tracking-tight text-ink sm:text-5xl">{{ $post->title }}</h1>

            <p class="mt-5 text-ink-muted">
                By <span class="text-ink">{{ $post->user->name }}</span> on
                <time datetime="{{ $post->published_at->toIso8601String() }}">{{ $post->published_at->format('F j, Y') }}</time>,
                {{ $post->reading_time }} min read
            </p>
        </header>

        @if ($post->featured_image_url)
            <img src="{{ $post->featured_image_url }}" alt="{{ $post->title }}"
                 class="mx-auto mt-10 aspect-[16/9] w-full max-w-4xl rounded-lg object-cover">
        @endif

        {{--
            The ONLY place in the app where HTML is output unescaped. It is
            safe because rendered_body goes through MarkdownRenderer, which
            strips raw HTML from the Markdown source and neutralises
            javascript:/data: links. Do not add other {!! !!} outputs
            without the same guarantee.
        --}}
        <div class="prose-post mx-auto mt-10">
            {!! $post->rendered_body !!}
        </div>

        @if ($post->tags->isNotEmpty())
            <ul class="mx-auto mt-10 flex max-w-[68ch] flex-wrap gap-2" aria-label="Tags">
                @foreach ($post->tags as $tag)
                    <li class="rounded-md bg-paper-alt px-3 py-1 text-sm text-ink-muted">{{ $tag->name }}</li>
                @endforeach
            </ul>
        @endif
    </article>

    @if ($related->isNotEmpty())
        <section class="border-t border-line bg-paper-alt/60">
            <div class="mx-auto max-w-5xl px-6 py-14">
                <h2 class="font-display text-2xl font-semibold text-ink">Keep reading</h2>
                <div class="mt-8 divide-y divide-line">
                    @foreach ($related as $item)
                        <x-post-row :post="$item" />
                    @endforeach
                </div>
            </div>
        </section>
    @endif
</x-layout.app>
