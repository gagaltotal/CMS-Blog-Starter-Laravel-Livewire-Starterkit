<div>
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="font-display text-3xl font-semibold text-ink">Categories</h1>
            <p class="mt-1 text-sm text-ink-muted">Group posts so readers can browse by topic.</p>
        </div>

        @can('create', \App\Models\Category::class)
            <x-forms.button type="button" wire:click="create">New category</x-forms.button>
        @endcan
    </div>

    @if ($notice)
        <p class="mt-6 rounded-md bg-success-soft px-4 py-2.5 text-sm text-success" role="status">{{ $notice }}</p>
    @endif

    <div class="mt-6 overflow-hidden rounded-lg border border-line bg-paper">
        <div class="overflow-x-auto">
            <table class="min-w-full text-left text-sm">
                <thead class="border-b border-line bg-paper-alt text-ink-muted">
                    <tr>
                        <th scope="col" class="px-5 py-3 font-medium">Name</th>
                        <th scope="col" class="hidden px-5 py-3 font-medium sm:table-cell">Slug</th>
                        <th scope="col" class="px-5 py-3 font-medium">Posts</th>
                        <th scope="col" class="px-5 py-3 text-right font-medium">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line">
                    @forelse ($categories as $category)
                        <tr wire:key="category-{{ $category->id }}" class="hover:bg-paper-alt/60">
                            <td class="px-5 py-4">
                                <p class="font-medium text-ink">{{ $category->name }}</p>
                                @if ($category->description)
                                    <p class="mt-0.5 max-w-md truncate text-ink-muted">{{ $category->description }}</p>
                                @endif
                            </td>
                            <td class="hidden px-5 py-4 text-ink-muted sm:table-cell">{{ $category->slug }}</td>
                            <td class="px-5 py-4 text-ink-muted">{{ $category->posts_count }}</td>
                            <td class="px-5 py-4">
                                <div class="flex items-center justify-end gap-4 whitespace-nowrap">
                                    @can('update', $category)
                                        <button type="button" wire:click="edit({{ $category->id }})" class="font-medium text-accent hover:underline">Edit</button>
                                    @endcan
                                    @can('delete', $category)
                                        <button type="button" wire:click="confirmDelete({{ $category->id }})" class="text-danger hover:underline">Delete</button>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-5 py-14 text-center text-ink-muted">No categories yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Create / edit modal --}}
    @if ($showForm)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4" role="dialog" aria-modal="true" aria-labelledby="category-form-title"
             x-data @keydown.escape.window="$wire.cancel()">
            <div class="absolute inset-0 bg-ink/50" wire:click="cancel"></div>

            <form wire:submit="save" class="relative w-full max-w-md rounded-lg bg-paper p-6 shadow-xl">
                <h3 id="category-form-title" class="font-display text-lg font-semibold text-ink">
                    {{ $editingId ? 'Edit category' : 'New category' }}
                </h3>

                <div class="mt-5 space-y-5">
                    <div>
                        <x-forms.label for="cat-name">Name</x-forms.label>
                        <x-forms.input wire:model="name" id="cat-name" type="text" maxlength="60" required autofocus />
                        <x-forms.error for="name" />
                    </div>

                    <div>
                        <x-forms.label for="cat-description">Description</x-forms.label>
                        <x-forms.textarea wire:model="description" id="cat-description" rows="3" maxlength="255" placeholder="Optional" />
                        <x-forms.error for="description" />
                    </div>
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <x-forms.button type="button" variant="secondary" wire:click="cancel">Cancel</x-forms.button>
                    <x-forms.button type="submit" wire:loading.attr="disabled" wire:target="save">Save category</x-forms.button>
                </div>
            </form>
        </div>
    @endif

    {{-- Delete confirmation --}}
    @if ($confirmingDeleteId)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4" role="dialog" aria-modal="true" aria-labelledby="cat-delete-title"
             x-data @keydown.escape.window="$wire.cancelDelete()">
            <div class="absolute inset-0 bg-ink/50" wire:click="cancelDelete"></div>
            <div class="relative w-full max-w-md rounded-lg bg-paper p-6 shadow-xl">
                <h3 id="cat-delete-title" class="font-display text-lg font-semibold text-ink">Delete this category?</h3>
                <p class="mt-2 text-sm text-ink-muted">Posts filed under it are kept and become uncategorized.</p>
                <div class="mt-6 flex justify-end gap-3">
                    <x-forms.button type="button" variant="secondary" wire:click="cancelDelete">Cancel</x-forms.button>
                    <x-forms.button type="button" variant="danger" wire:click="delete" wire:loading.attr="disabled" wire:target="delete">Delete category</x-forms.button>
                </div>
            </div>
        </div>
    @endif
</div>
