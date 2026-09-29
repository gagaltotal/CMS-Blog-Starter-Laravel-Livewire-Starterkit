@props(['href', 'active' => false])

<a
    href="{{ $href }}"
    wire:navigate
    {{ $attributes->class([
        'flex items-center rounded-md px-3 py-2 text-sm font-medium transition-colors',
        'bg-accent text-paper' => $active,
        'text-paper/70 hover:bg-white/5 hover:text-paper' => ! $active,
    ]) }}
>
    {{ $slot }}
</a>
