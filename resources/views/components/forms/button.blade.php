@props(['variant' => 'primary', 'type' => 'submit'])

@php
$variants = [
    'primary' => 'bg-accent text-paper hover:bg-accent-dark focus-visible:outline-accent',
    'secondary' => 'border border-line text-ink hover:border-ink bg-white',
    'danger' => 'bg-danger text-paper hover:bg-danger/90',
    'ghost' => 'text-ink-muted hover:text-ink',
];
$base = 'inline-flex items-center justify-center gap-2 rounded-md px-4 py-2.5 text-sm font-medium transition-colors disabled:cursor-not-allowed disabled:opacity-60';
@endphp

<button type="{{ $type }}" {{ $attributes->merge(['class' => "$base {$variants[$variant]}"]) }}>
    {{ $slot }}
</button>
