<div>
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="font-display text-3xl font-semibold text-ink">Hello, {{ auth()->user()->name }}</h1>
            <p class="mt-1 text-sm text-ink-muted">Here is what's going on with your content.</p>
        </div>

        @if ($canCreate)
            <a href="{{ route('admin.posts.create') }}" wire:navigate
               class="inline-flex items-center rounded-md bg-accent px-4 py-2.5 text-sm font-medium text-paper transition-colors hover:bg-accent-dark">
                New post
            </a>
        @endif
    </div>

    {{-- Stats: hairline-divided cells instead of a row of shadowed cards --}}
    <dl class="mt-8 grid grid-cols-2 gap-px overflow-hidden rounded-lg border border-line bg-line lg:grid-cols-4">
        <div class="bg-paper p-5">
            <dt class="text-sm text-ink-muted">Posts</dt>
            <dd class="mt-1 font-display text-3xl font-semibold text-ink">{{ number_format($stats['posts']) }}</dd>
        </div>
        <div class="bg-paper p-5">
            <dt class="text-sm text-ink-muted">Published</dt>
            <dd class="mt-1 font-display text-3xl font-semibold text-accent">{{ number_format($stats['published']) }}</dd>
        </div>
        <div class="bg-paper p-5">
            <dt class="text-sm text-ink-muted">Drafts</dt>
            <dd class="mt-1 font-display text-3xl font-semibold text-warning">{{ number_format($stats['drafts']) }}</dd>
        </div>
        <div class="bg-paper p-5">
            <dt class="text-sm text-ink-muted">Total views</dt>
            <dd class="mt-1 font-display text-3xl font-semibold text-ink">{{ number_format($stats['views']) }}</dd>
        </div>

        @isset($stats['categories'])
            <div class="bg-paper p-5">
                <dt class="text-sm text-ink-muted">Categories</dt>
                <dd class="mt-1 font-display text-3xl font-semibold text-ink">{{ number_format($stats['categories']) }}</dd>
            </div>
        @endisset

        @isset($stats['users'])
            <div class="bg-paper p-5">
                <dt class="text-sm text-ink-muted">Users</dt>
                <dd class="mt-1 font-display text-3xl font-semibold text-ink">{{ number_format($stats['users']) }}</dd>
            </div>
        @endisset
    </dl>

    {{-- Recent activity --}}
    <section class="mt-10">
        <div class="flex items-center justify-between">
            <h2 class="font-display text-xl font-semibold">Recently updated</h2>
            @canany(['manage own posts', 'manage all posts'])
                <a href="{{ route('admin.posts.index') }}" wire:navigate class="text-sm font-medium text-accent hover:underline">View all posts</a>
            @endcanany
        </div>

        <div class="mt-4 overflow-hidden rounded-lg border border-line bg-paper">
            @forelse ($recent as $post)
                <div class="flex items-center justify-between gap-4 border-b border-line px-5 py-4 last:border-b-0">
                    <div class="min-w-0">
                        <a href="{{ route('admin.posts.edit', $post) }}" wire:navigate
                           class="block truncate font-medium text-ink hover:text-accent">{{ $post->title }}</a>
                        <p class="mt-0.5 text-sm text-ink-muted">
                            {{ $post->category?->name ?? 'Uncategorized' }}, by {{ $post->user->name }}, updated {{ $post->updated_at->diffForHumans() }}
                        </p>
                    </div>
                    <span class="shrink-0 rounded-full px-2.5 py-1 text-xs font-medium {{ $post->status->badgeClasses() }}">
                        {{ $post->status->label() }}
                    </span>
                </div>
            @empty
                <div class="px-5 py-10 text-center text-sm text-ink-muted">
                    Nothing here yet.
                    @if ($canCreate)
                        <a href="{{ route('admin.posts.create') }}" wire:navigate class="font-medium text-accent hover:underline">Write your first post.</a>
                    @endif
                </div>
            @endforelse
        </div>
    </section>
</div>
