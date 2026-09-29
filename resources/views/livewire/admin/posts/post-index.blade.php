<div>
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="font-display text-3xl font-semibold text-ink">Posts</h1>
            <p class="mt-1 text-sm text-ink-muted">
                {{ auth()->user()->can('manage all posts') ? 'Every post on the site.' : 'The posts you have written.' }}
            </p>
        </div>

        @can('create', \App\Models\Post::class)
            <a href="{{ route('admin.posts.create') }}" wire:navigate
               class="inline-flex items-center rounded-md bg-accent px-4 py-2.5 text-sm font-medium text-paper transition-colors hover:bg-accent-dark">
                New post
            </a>
        @endcan
    </div>

    @if ($notice)
        <p class="mt-6 rounded-md bg-success-soft px-4 py-2.5 text-sm text-success" role="status">{{ $notice }}</p>
    @endif

    {{-- Filters --}}
    <div class="mt-6 grid gap-3 sm:grid-cols-[1fr_auto_auto]">
        <div>
            <label for="search" class="sr-only">Search posts</label>
            <x-forms.input wire:model.live.debounce.300ms="search" id="search" type="search" placeholder="Search by title or excerpt" />
        </div>

        <div>
            <label for="status" class="sr-only">Status</label>
            <x-forms.select wire:model.live="status" id="status" class="sm:w-40">
                <option value="">All statuses</option>
                @foreach (\App\Enums\PostStatus::cases() as $case)
                    <option value="{{ $case->value }}">{{ $case->label() }}</option>
                @endforeach
            </x-forms.select>
        </div>

        <div>
            <label for="category" class="sr-only">Category</label>
            <x-forms.select wire:model.live="category" id="category" class="sm:w-48">
                <option value="">All categories</option>
                @foreach ($categories as $cat)
                    <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                @endforeach
            </x-forms.select>
        </div>
    </div>

    {{-- Table --}}
    <div class="mt-6 overflow-hidden rounded-lg border border-line bg-paper" wire:loading.class="opacity-60" wire:target="search,status,category,previousPage,nextPage">
        <div class="overflow-x-auto">
            <table class="min-w-full text-left text-sm">
                <thead class="border-b border-line bg-paper-alt text-ink-muted">
                    <tr>
                        <th scope="col" class="px-5 py-3 font-medium">Title</th>
                        <th scope="col" class="px-5 py-3 font-medium">Status</th>
                        <th scope="col" class="hidden px-5 py-3 font-medium md:table-cell">Updated</th>
                        <th scope="col" class="px-5 py-3 text-right font-medium">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line">
                    @forelse ($posts as $post)
                        <tr wire:key="post-{{ $post->id }}" class="hover:bg-paper-alt/60">
                            <td class="max-w-md px-5 py-4">
                                <a href="{{ route('admin.posts.edit', ['post' => $post->id]) }}" wire:navigate
                                   class="block truncate font-medium text-ink hover:text-accent">{{ $post->title }}</a>
                                <p class="mt-0.5 truncate text-ink-muted">
                                    {{ $post->category?->name ?? 'Uncategorized' }}, by {{ $post->user->name }}
                                </p>
                            </td>
                            <td class="px-5 py-4">
                                <span class="rounded-full px-2.5 py-1 text-xs font-medium {{ $post->status->badgeClasses() }}">
                                    {{ $post->status->label() }}
                                </span>
                            </td>
                            <td class="hidden whitespace-nowrap px-5 py-4 text-ink-muted md:table-cell">
                                {{ $post->updated_at->diffForHumans() }}
                            </td>
                            <td class="px-5 py-4">
                                <div class="flex items-center justify-end gap-4 whitespace-nowrap">
                                    @can('update', $post)
                                        <a href="{{ route('admin.posts.edit', ['post' => $post->id]) }}" wire:navigate class="font-medium text-accent hover:underline">Edit</a>
                                    @endcan

                                    @if ($post->isPublished())
                                        <a href="{{ route('blog.show', ['post' => $post->slug]) }}" target="_blank" rel="noopener" class="text-ink-muted hover:text-ink">View</a>
                                    @endif

                                    @if ($canPublish)
                                        @can('update', $post)
                                            <button type="button" wire:click="togglePublish({{ $post->id }})" wire:loading.attr="disabled"
                                                    class="text-ink-muted hover:text-ink">
                                                {{ $post->status === \App\Enums\PostStatus::Published ? 'Unpublish' : 'Publish' }}
                                            </button>
                                        @endcan
                                    @endif

                                    @can('delete', $post)
                                        <button type="button" wire:click="confirmDelete({{ $post->id }})" class="text-danger hover:underline">Delete</button>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-5 py-14 text-center text-ink-muted">
                                @if ($search !== '' || $status !== '' || $category !== '')
                                    No posts match those filters.
                                @else
                                    No posts yet.
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{ $posts->links('pagination.livewire-simple') }}

    {{-- Delete confirmation --}}
    @if ($confirmingDeleteId)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4" role="dialog" aria-modal="true" aria-labelledby="delete-title"
             x-data @keydown.escape.window="$wire.cancelDelete()">
            <div class="absolute inset-0 bg-ink/50" wire:click="cancelDelete"></div>
            <div class="relative w-full max-w-md rounded-lg bg-paper p-6 shadow-xl">
                <h3 id="delete-title" class="font-display text-lg font-semibold text-ink">Delete this post?</h3>
                <p class="mt-2 text-sm text-ink-muted">The post and its featured image will be removed permanently. This can't be undone.</p>
                <div class="mt-6 flex justify-end gap-3">
                    <x-forms.button type="button" variant="secondary" wire:click="cancelDelete">Cancel</x-forms.button>
                    <x-forms.button type="button" variant="danger" wire:click="delete" wire:loading.attr="disabled" wire:target="delete">Delete post</x-forms.button>
                </div>
            </div>
        </div>
    @endif
</div>
