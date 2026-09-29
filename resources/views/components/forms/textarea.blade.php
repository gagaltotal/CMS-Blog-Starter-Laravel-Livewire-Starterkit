@props(['disabled' => false, 'rows' => 6])

<textarea rows="{{ $rows }}" @disabled($disabled) {{ $attributes->merge(['class' => 'block w-full rounded-md border border-line bg-white px-3.5 py-2.5 text-sm text-ink placeholder:text-ink-faint focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent disabled:cursor-not-allowed disabled:opacity-60']) }}>{{ $slot }}</textarea>
