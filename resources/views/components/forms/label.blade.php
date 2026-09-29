@props(['value' => null])

<label {{ $attributes->merge(['class' => 'mb-1.5 block text-sm font-medium text-ink']) }}>
    {{ $value ?? $slot }}
</label>
