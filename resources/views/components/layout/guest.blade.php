@props(['title' => null, 'metaDescription' => null])
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    @include('partials.head')
    <meta name="robots" content="noindex, nofollow">
</head>
<body class="h-full bg-paper text-ink font-sans antialiased">
    <div class="flex min-h-full flex-col">
        <header class="px-6 py-6 sm:px-10">
            <a href="{{ route('home') }}" wire:navigate class="inline-flex items-center gap-2 font-display text-lg font-semibold text-ink">
                {{ \App\Models\Setting::siteName() }}
            </a>
        </header>

        <main class="flex flex-1 items-center justify-center px-6 py-10">
            <div class="w-full max-w-md">
                <div class="rounded-lg border border-line bg-white/60 p-8 shadow-sm">
                    {{ $slot }}
                </div>
            </div>
        </main>

        <footer class="px-6 py-8 text-sm text-ink-faint">
            <div class="flex flex-col items-center gap-2 text-center sm:flex-row sm:justify-between sm:text-left">
                <p>&copy; {{ now()->year }} {{ \App\Models\Setting::siteName() }}. {{ \App\Models\Setting::get('footer_text', 'All rights reserved.') }}</p>
                <a href="{{ route('home') }}" wire:navigate class="hover:text-ink-muted">&larr; Back to {{ \App\Models\Setting::siteName() }}</a>
            </div>
        </footer>
    </div>

    @livewireScripts
</body>
</html>
