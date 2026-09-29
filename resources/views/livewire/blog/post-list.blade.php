<div class="mx-auto max-w-5xl px-6 py-14">
    <h1 class="font-display text-4xl font-semibold tracking-tight text-ink">{{ $category?->name ?? 'Blog' }}</h1>
    <p class="mt-2 max-w-2xl text-ink-muted">
        {{ $category?->description ?: 'Everything published so far, newest first.' }}
    </p>

    <div class="mt-8 w-full sm:max-w-sm">
        <label for="blog-search" class="sr-only">Search posts</label>
        <x-forms.input wire:model.live.debounce.400ms="search" id="blog-search" type="search" placeholder="Search posts" />
    </div>

    @if ($categories->isNotEmpty())
        <nav aria-label="Topics" class="mt-6 flex flex-wrap gap-2">
            <a href="{{ route('blog.index') }}" wire:navigate
               class="rounded-full border px-3.5 py-1.5 text-sm transition-colors {{ $category ? 'border-line text-ink-muted hover:border-ink hover:text-ink' : 'border-accent bg-accent text-paper' }}">
                All
            </a>
            @foreach ($categories as $topic)
                <a href="{{ route('blog.category', ['category' => $topic->slug]) }}" wire:navigate wire:key="topic-{{ $topic->id }}"
                   class="rounded-full border px-3.5 py-1.5 text-sm transition-colors {{ $category?->id === $topic->id ? 'border-accent bg-accent text-paper' : 'border-line text-ink-muted hover:border-ink hover:text-ink' }}">
                    {{ $topic->name }}
                </a>
            @endforeach
        </nav>
    @endif

    <div class="mt-10 divide-y divide-line transition-opacity" wire:loading.class="opacity-60" wire:target="search,previousPage,nextPage">
        @forelse ($posts as $post)
            <x-post-row :post="$post" wire:key="post-{{ $post->id }}" />
        @empty
            <p class="py-10 text-ink-muted">
                @if ($search !== '')
                    Nothing matches "{{ $search }}".
                @else
                    No posts here yet.
                @endif
            </p>
        @endforelse
    </div>

    {{ $posts->links('pagination.livewire-simple') }}
</div>
