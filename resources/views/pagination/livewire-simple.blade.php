{{--
    Custom paginator view for Livewire components (used as
    `$items->links('pagination.livewire-simple')`).

    Written by hand instead of relying on the framework's bundled
    paginator views because Tailwind v4 does not scan vendor/, so the
    utility classes those views use would never be generated.
--}}
@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Pagination" class="flex flex-wrap items-center justify-between gap-3 px-1 py-4">
        <p class="text-sm text-ink-muted">
            Showing <span class="font-medium text-ink">{{ $paginator->firstItem() }}</span>
            to <span class="font-medium text-ink">{{ $paginator->lastItem() }}</span>
            of <span class="font-medium text-ink">{{ $paginator->total() }}</span>
        </p>

        <div class="flex items-center gap-2">
            <button type="button"
                    wire:click="previousPage('{{ $paginator->getPageName() }}')"
                    wire:loading.attr="disabled"
                    @disabled($paginator->onFirstPage())
                    class="rounded-md border border-line bg-white px-3.5 py-2 text-sm font-medium text-ink transition-colors hover:border-ink disabled:cursor-not-allowed disabled:opacity-40 disabled:hover:border-line">
                Previous
            </button>

            <span class="px-2 text-sm text-ink-muted">Page {{ $paginator->currentPage() }} of {{ $paginator->lastPage() }}</span>

            <button type="button"
                    wire:click="nextPage('{{ $paginator->getPageName() }}')"
                    wire:loading.attr="disabled"
                    @disabled(! $paginator->hasMorePages())
                    class="rounded-md border border-line bg-white px-3.5 py-2 text-sm font-medium text-ink transition-colors hover:border-ink disabled:cursor-not-allowed disabled:opacity-40 disabled:hover:border-line">
                Next
            </button>
        </div>
    </nav>
@endif
