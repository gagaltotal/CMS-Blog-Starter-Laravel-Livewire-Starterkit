@props(['title' => null, 'metaDescription' => null])
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    @include('partials.head')
    <meta name="robots" content="noindex, nofollow">
</head>
<body class="h-full bg-paper-alt text-ink font-sans antialiased" x-data="{ sidebarOpen: false }">

    <div class="flex h-full">
        <!-- Mobile overlay -->
        <div
            x-show="sidebarOpen"
            x-transition.opacity
            @click="sidebarOpen = false"
            class="fixed inset-0 z-30 bg-ink/40 lg:hidden"
            style="display: none;"
        ></div>

        <!-- Sidebar -->
        <aside
            class="fixed inset-y-0 left-0 z-40 flex w-64 flex-col bg-ink transition-transform duration-200 ease-in-out lg:static lg:translate-x-0"
            :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
        >
            <div class="flex h-16 shrink-0 items-center gap-2 px-6">
                <a href="{{ route('admin.dashboard') }}" wire:navigate class="font-display text-lg font-semibold text-paper">
                    {{ \App\Models\Setting::siteName() }}
                </a>
            </div>

            <nav class="flex-1 space-y-1 overflow-y-auto px-3 py-4">
                <x-layout.admin-nav-link :href="route('admin.dashboard')" :active="request()->routeIs('admin.dashboard')">
                    Dashboard
                </x-layout.admin-nav-link>

                @canany(['manage own posts', 'manage all posts'])
                    <x-layout.admin-nav-link :href="route('admin.posts.index')" :active="request()->routeIs('admin.posts.*')">
                        Posts
                    </x-layout.admin-nav-link>
                @endcanany

                @can('manage categories')
                    <x-layout.admin-nav-link :href="route('admin.categories.index')" :active="request()->routeIs('admin.categories.*')">
                        Categories
                    </x-layout.admin-nav-link>
                @endcan

                @can('manage users')
                    <x-layout.admin-nav-link :href="route('admin.users.index')" :active="request()->routeIs('admin.users.*')">
                        Users
                    </x-layout.admin-nav-link>
                @endcan

                @can('manage settings')
                    <x-layout.admin-nav-link :href="route('admin.settings')" :active="request()->routeIs('admin.settings')">
                        Settings
                    </x-layout.admin-nav-link>
                @endcan
            </nav>

            <div class="border-t border-white/10 p-4">
                <div class="flex items-center gap-3 px-2">
                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-white/10 font-display text-sm font-semibold text-paper">
                        {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-medium text-paper">{{ auth()->user()->name }}</p>
                        <p class="truncate text-xs text-paper/60">{{ auth()->user()->getRoleNames()->first() ?? 'No role' }}</p>
                    </div>
                </div>
                <div class="mt-3 flex items-center gap-3 px-2 text-sm">
                    <a href="{{ route('profile.edit') }}" wire:navigate class="text-paper/70 hover:text-paper">Account</a>
                    <span class="text-paper/30">&middot;</span>
                    <a href="{{ route('home') }}" wire:navigate class="text-paper/70 hover:text-paper">View site</a>
                    <span class="text-paper/30">&middot;</span>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="text-paper/70 hover:text-paper">Log out</button>
                    </form>
                </div>
            </div>
        </aside>

        <!-- Main column -->
        <div class="flex min-w-0 flex-1 flex-col">
            <header class="flex h-16 shrink-0 items-center gap-4 border-b border-line bg-paper px-4 lg:px-8">
                <button @click="sidebarOpen = true" class="text-ink-muted hover:text-ink lg:hidden" aria-label="Open menu">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5M3.75 17.25h16.5" />
                    </svg>
                </button>

                <div class="flex-1"></div>

                @if (session('status'))
                    <p class="rounded-md bg-success-soft px-3 py-1.5 text-sm text-success">{{ session('status') }}</p>
                @endif
            </header>

            <main class="flex-1 overflow-y-auto px-4 py-8 lg:px-8">
                <div class="mx-auto max-w-6xl">
                    {{ $slot }}
                </div>
            </main>

            <footer class="px-6 py-8 text-sm text-ink-faint text-center">
                <div class="flex justify-center items-center">
                    <p>
                        &copy; {{ now()->year }} {{ \App\Models\Setting::siteName() }}.
                        {{ \App\Models\Setting::get('footer_text', 'All rights reserved.') }}
                    </p>
                </div>
            </footer>

        </div>
    </div>

    @livewireScripts
</body>
</html>
