@props(['title' => null, 'metaDescription' => null])
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    @include('partials.head')
</head>
<body class="flex h-full min-h-screen flex-col bg-paper text-ink font-sans antialiased">

    <header class="border-b border-line">
        <div class="mx-auto flex max-w-5xl items-center justify-between px-6 py-5">
            <a href="{{ route('home') }}" wire:navigate class="font-display text-xl font-semibold tracking-tight text-ink">
                {{ \App\Models\Setting::siteName() }}
            </a>

            <nav class="flex items-center gap-6 text-sm">
                <a href="{{ route('home') }}" wire:navigate class="text-ink-muted hover:text-ink {{ request()->routeIs('home') ? 'text-ink font-medium' : '' }}">Home</a>
                <a href="{{ route('blog.index') }}" wire:navigate class="text-ink-muted hover:text-ink {{ request()->routeIs('blog.*') ? 'text-ink font-medium' : '' }}">Blog</a>

                @auth
                    @can('view dashboard')
                        <a href="{{ route('admin.dashboard') }}" wire:navigate class="rounded-md bg-accent px-4 py-2 font-medium text-paper hover:bg-accent-dark transition-colors">
                            Dashboard
                        </a>
                    @else
                        <a href="{{ route('profile.edit') }}" wire:navigate class="rounded-md border border-line px-4 py-2 font-medium text-ink hover:border-ink transition-colors">
                            Account
                        </a>
                    @endcan
                @else
                    <a href="{{ route('login') }}" wire:navigate class="rounded-md border border-line px-4 py-2 font-medium text-ink hover:border-ink transition-colors">
                        Log in
                    </a>
                @endauth
            </nav>
        </div>
    </header>

    <main class="flex-1">
        {{ $slot }}
    </main>

    <footer class="mt-auto border-t border-line">
        <div class="mx-auto max-w-5xl px-6 py-8 text-sm text-ink-faint sm:py-10">
            <div class="flex flex-col items-center gap-3 text-center sm:flex-row sm:items-center sm:justify-between sm:gap-4 sm:text-left">
                <p class="text-balance">&copy; {{ now()->year }} {{ \App\Models\Setting::siteName() }}. {{ \App\Models\Setting::get('footer_text', 'All rights reserved.') }}</p>
                <div class="flex flex-wrap items-center justify-center gap-5">
                    <a href="{{ route('home') }}" wire:navigate class="hover:text-ink-muted">Home</a>
                    <a href="{{ route('blog.index') }}" wire:navigate class="hover:text-ink-muted">Blog</a>
                </div>
            </div>
        </div>
    </footer>

    @livewireScripts
</body>
</html>
