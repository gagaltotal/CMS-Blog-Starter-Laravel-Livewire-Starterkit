<x-layout.app :meta-description="\App\Models\Setting::get('site_description')">

    {{-- Intro --}}
    <section class="mx-auto max-w-5xl px-6 pb-16 pt-16 sm:pb-20 sm:pt-24">
        <h1 class="max-w-3xl font-display text-5xl font-semibold leading-[1.08] tracking-tight text-ink sm:text-6xl">
            {{ \App\Models\Setting::get('site_tagline', 'Notes on building for the web.') }}
        </h1>

        @if ($description = \App\Models\Setting::get('site_description'))
            <p class="mt-6 max-w-2xl text-lg leading-relaxed text-ink-muted">{{ $description }}</p>
        @endif

        <div class="mt-9 flex flex-wrap items-center gap-4">
            <a href="{{ route('blog.index') }}" wire:navigate
               class="inline-flex rounded-md bg-accent px-5 py-3 text-sm font-medium text-paper transition-colors hover:bg-accent-dark">
                Read the blog
            </a>
            @guest
                <a href="{{ route('login') }}" wire:navigate class="text-sm font-medium text-ink-muted hover:text-ink">Log in to manage content</a>
            @endguest
        </div>
    </section>

    @if ($featured)
        {{-- Featured (most recent) post --}}
        <section class="border-t border-line bg-paper-alt/60">
            <div class="mx-auto max-w-5xl px-6 py-14">
                <article class="grid gap-10 md:grid-cols-2 md:items-center">
                    @if ($featured->featured_image_url)
                        <a href="{{ route('blog.show', ['post' => $featured->slug]) }}" wire:navigate class="block" tabindex="-1" aria-hidden="true">
                            <img src="{{ $featured->featured_image_url }}" alt="" class="aspect-[4/3] w-full rounded-lg object-cover">
                        </a>
                    @endif

                    <div class="{{ $featured->featured_image_url ? '' : 'max-w-3xl md:col-span-2' }}">
                        @if ($featured->category)
                            <a href="{{ route('blog.category', ['category' => $featured->category->slug]) }}" wire:navigate
                               class="text-sm font-medium text-accent hover:underline">{{ $featured->category->name }}</a>
                        @endif

                        <h2 class="mt-2 font-display text-3xl font-semibold leading-tight tracking-tight text-ink sm:text-4xl">
                            <a href="{{ route('blog.show', ['post' => $featured->slug]) }}" wire:navigate class="hover:text-accent">{{ $featured->title }}</a>
                        </h2>

                        @if ($featured->excerpt)
                            <p class="mt-4 text-lg leading-relaxed text-ink-muted">{{ $featured->excerpt }}</p>
                        @endif

                        <p class="mt-5 text-sm text-ink-faint">
                            {{ $featured->user->name }} on
                            <time datetime="{{ $featured->published_at->toIso8601String() }}">{{ $featured->published_at->format('M j, Y') }}</time>,
                            {{ $featured->reading_time }} min read
                        </p>
                    </div>
                </article>
            </div>
        </section>
    @endif

    {{-- Recent posts --}}
    <section class="border-t border-line">
        <div class="mx-auto max-w-5xl px-6 py-14">
            <div class="flex items-baseline justify-between gap-4">
                <h2 class="font-display text-2xl font-semibold text-ink">Recent posts</h2>
                @if ($latest->isNotEmpty())
                    <a href="{{ route('blog.index') }}" wire:navigate class="text-sm font-medium text-accent hover:underline">All posts</a>
                @endif
            </div>

            @forelse ($latest as $post)
                @if ($loop->first)
                    <div class="mt-8 divide-y divide-line">
                @endif

                <x-post-row :post="$post" />

                @if ($loop->last)
                    </div>
                @endif
            @empty
                <p class="mt-8 text-ink-muted">
                    @if ($featured)
                        That's everything for now.
                    @else
                        Nothing has been published yet.
                        @can('view dashboard')
                            <a href="{{ route('admin.posts.create') }}" wire:navigate class="font-medium text-accent hover:underline">Write the first post.</a>
                        @endcan
                    @endif
                </p>
            @endforelse
        </div>
    </section>

    {{-- Topics --}}
    @if ($categories->isNotEmpty())
        <section class="border-t border-line bg-paper-alt/60">
            <div class="mx-auto max-w-5xl px-6 py-12">
                <h2 class="font-display text-2xl font-semibold text-ink">Browse by topic</h2>
                <ul class="mt-5 flex flex-wrap gap-3">
                    @foreach ($categories as $category)
                        <li>
                            <a href="{{ route('blog.category', ['category' => $category->slug]) }}" wire:navigate
                               class="inline-flex items-baseline gap-2 rounded-md border border-line bg-paper px-4 py-2 text-sm text-ink transition-colors hover:border-ink">
                                {{ $category->name }}
                                <span class="text-ink-faint">{{ $category->posts_count }}</span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
        </section>
    @endif
</x-layout.app>
