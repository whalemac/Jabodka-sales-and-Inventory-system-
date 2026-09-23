<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ isset($title) ? $title . ' — ' : '' }}JABODKA-SIMS</title>

    {{-- Google Fonts --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Source+Sans+3:wght@400;500;600;700&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @stack('styles')
</head>
<body class="bg-sand text-ink antialiased min-h-screen flex flex-col" x-data>

    {{-- ===================== TOPBAR ===================== --}}
    <header class="bg-navy shadow-md sticky top-0 z-50" x-data="{ mobileOpen: false }">
        <div class="max-w-screen-xl mx-auto px-4 sm:px-6">
            <div class="flex items-center justify-between h-14 md:h-16">

                {{-- Logo / Wordmark --}}
                <a href="{{ route('dashboard') }}" class="flex items-center gap-2 shrink-0">
                    <span class="text-white font-bold text-lg md:text-xl tracking-wide leading-none">
                        JABODKA<span class="text-accent">-</span>SIMS
                    </span>
                </a>

                {{-- Desktop: Quick-action buttons (center/right, role-aware) --}}
                <div class="hidden md:flex items-center gap-2">
                    @auth
                        @if(auth()->user()->isAdmin())
                            <a href="{{ route('admin.walk-in.create') }}"
                               class="inline-flex items-center gap-1.5 bg-accent hover:bg-accent-dark text-white text-sm font-semibold px-4 py-2 rounded-lg transition">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                New Sale
                            </a>
                            <a href="{{ route('admin.online.create') }}"
                               class="inline-flex items-center gap-1.5 bg-white/10 hover:bg-white/20 text-white text-sm font-medium px-4 py-2 rounded-lg transition">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h18l-2 13H5L3 3zm0 0l-.5-2H1M9 21a1 1 0 100-2 1 1 0 000 2zm10 0a1 1 0 100-2 1 1 0 000 2z"/></svg>
                                Online Order
                            </a>
                            <a href="{{ route('admin.stock.index') }}"
                               class="inline-flex items-center gap-1.5 bg-white/10 hover:bg-white/20 text-white text-sm font-medium px-4 py-2 rounded-lg transition">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7H4a2 2 0 00-2 2v6a2 2 0 002 2h16a2 2 0 002-2V9a2 2 0 00-2-2zM4 7V5a2 2 0 012-2h12a2 2 0 012 2v2"/></svg>
                                Stock
                            </a>
                        @endif
                        @if(auth()->user()->isStaff())
                            <a href="{{ route('staff.production.create') }}"
                               class="inline-flex items-center gap-1.5 bg-accent hover:bg-accent-dark text-white text-sm font-semibold px-4 py-2 rounded-lg transition">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                Log Production
                            </a>
                        @endif
                    @endauth
                </div>

                {{-- Right: Nav tabs + user menu --}}
                <div class="flex items-center gap-2">

                    {{-- Desktop nav module tabs --}}
                    @auth
                    @php
                        $navBase   = 'text-white/70 hover:text-white text-sm font-medium px-3 py-1.5 rounded-lg hover:bg-white/10 transition whitespace-nowrap';
                        $navActive = 'text-white bg-white/20 text-sm font-medium px-3 py-1.5 rounded-lg whitespace-nowrap';
                    @endphp
                    <nav class="hidden lg:flex items-center gap-0.5 mr-2">
                        @if(auth()->user()->isAdmin())
                            <a href="{{ route('dashboard') }}" class="{{ request()->routeIs('dashboard') ? $navActive : $navBase }}">Dashboard</a>
                            <a href="{{ route('admin.walk-in.index') }}" class="{{ request()->routeIs('admin.walk-in.*') ? $navActive : $navBase }}">Walk-In</a>
                            <a href="{{ route('admin.online.index') }}" class="{{ request()->routeIs('admin.online.*') ? $navActive : $navBase }}">Online</a>
                            <a href="{{ route('admin.products.index') }}" class="{{ request()->routeIs('admin.products.*') ? $navActive : $navBase }}">Products</a>
                            <a href="{{ route('admin.stock.index') }}" class="{{ request()->routeIs('admin.stock.*') ? $navActive : $navBase }}">Stock</a>
                            <a href="{{ route('admin.consignment.index') }}" class="{{ request()->routeIs('admin.consignment.*') ? $navActive : $navBase }}">Consignment</a>
                            <a href="{{ route('admin.returns.index') }}" class="{{ request()->routeIs('admin.returns.*') ? $navActive : $navBase }}">Returns</a>
                            <a href="{{ route('admin.reports.index') }}" class="{{ request()->routeIs('admin.reports.*') ? $navActive : $navBase }}">Reports</a>
                        @elseif(auth()->user()->isSuperAdmin())
                            <a href="{{ route('super.users.index') }}" class="{{ request()->routeIs('super.users.*') ? $navActive : $navBase }}">Users</a>
                            <a href="{{ route('super.subscriptions.index') }}" class="{{ request()->routeIs('super.subscriptions.*') ? $navActive : $navBase }}">Subscriptions</a>
                        @elseif(auth()->user()->isStaff())
                            <a href="{{ route('staff.production.index') }}" class="{{ request()->routeIs('staff.production.*') ? $navActive : $navBase }}">Production Log</a>
                            <a href="{{ route('staff.materials.index') }}" class="{{ request()->routeIs('staff.materials.*') ? $navActive : $navBase }}">Raw Materials</a>
                        @endif
                    </nav>
                    @endauth

                    {{-- User menu dropdown --}}
                    @auth
                    <div class="relative" x-data="{ open: false }">
                        <button @click="open = !open" @click.outside="open = false"
                                class="flex items-center gap-2 text-white text-sm font-medium bg-white/10 hover:bg-white/20 px-3 py-2 rounded-lg transition">
                            <span class="hidden sm:inline max-w-[120px] truncate">{{ auth()->user()->username }}</span>
                            <span class="hidden sm:inline text-white/50 text-xs">{{ auth()->user()->roleLabel() }}</span>
                            <svg class="w-4 h-4 text-white/70 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>
                        <div x-show="open" x-transition x-cloak
                             class="absolute right-0 mt-2 w-48 bg-white rounded-xl shadow-xl border border-gray-100 py-1 z-50">
                            <div class="px-4 py-2 border-b border-gray-100">
                                <p class="text-sm font-semibold text-ink truncate">{{ auth()->user()->username }}</p>
                                <p class="text-xs text-gray-500">{{ auth()->user()->roleLabel() }}</p>
                            </div>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit"
                                        class="w-full text-left px-4 py-2.5 text-sm text-red-600 hover:bg-red-50 transition">
                                    Sign Out
                                </button>
                            </form>
                        </div>
                    </div>
                    @endauth

                    {{-- Mobile hamburger --}}
                    @auth
                    <button @click="mobileOpen = !mobileOpen"
                            class="lg:hidden p-2 text-white/80 hover:text-white rounded-lg hover:bg-white/10 transition">
                        <svg x-show="!mobileOpen" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                        </svg>
                        <svg x-show="mobileOpen" x-cloak class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                    @endauth
                </div>
            </div>
        </div>

        {{-- Mobile nav drawer --}}
        @auth
        <div x-show="mobileOpen" x-cloak x-transition:enter="transition ease-out duration-150"
             x-transition:enter-start="opacity-0 -translate-y-1"
             x-transition:enter-end="opacity-100 translate-y-0"
             class="lg:hidden border-t border-white/10 bg-navy-dark px-4 pb-4 pt-2 space-y-1">

            @if(auth()->user()->isAdmin())
                <x-mobile-nav-link href="{{ route('dashboard') }}" :active="request()->routeIs('dashboard')">Dashboard</x-mobile-nav-link>
                <x-mobile-nav-link href="{{ route('admin.walk-in.create') }}" :active="false">
                    <span class="inline-flex items-center gap-1.5">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        New Sale
                    </span>
                </x-mobile-nav-link>
                <x-mobile-nav-link href="{{ route('admin.walk-in.index') }}" :active="request()->routeIs('admin.walk-in.*')">Walk-In Orders</x-mobile-nav-link>
                <x-mobile-nav-link href="{{ route('admin.online.index') }}" :active="request()->routeIs('admin.online.*')">Online Orders</x-mobile-nav-link>
                <x-mobile-nav-link href="{{ route('admin.products.index') }}" :active="request()->routeIs('admin.products.*')">Products</x-mobile-nav-link>
                <x-mobile-nav-link href="{{ route('admin.stock.index') }}" :active="request()->routeIs('admin.stock.*')">Stock Management</x-mobile-nav-link>
                <x-mobile-nav-link href="{{ route('admin.consignment.index') }}" :active="request()->routeIs('admin.consignment.*')">Consignment</x-mobile-nav-link>
                <x-mobile-nav-link href="{{ route('admin.returns.index') }}" :active="request()->routeIs('admin.returns.*')">Returns & Replacements</x-mobile-nav-link>
                <x-mobile-nav-link href="{{ route('admin.reports.index') }}" :active="request()->routeIs('admin.reports.*')">Reports</x-mobile-nav-link>
            @elseif(auth()->user()->isSuperAdmin())
                <x-mobile-nav-link href="{{ route('super.users.index') }}" :active="request()->routeIs('super.users.*')">User Management</x-mobile-nav-link>
                <x-mobile-nav-link href="{{ route('super.subscriptions.index') }}" :active="request()->routeIs('super.subscriptions.*')">Subscriptions</x-mobile-nav-link>
            @elseif(auth()->user()->isStaff())
                <x-mobile-nav-link href="{{ route('staff.production.index') }}" :active="request()->routeIs('staff.production.*')">Production Log</x-mobile-nav-link>
                <x-mobile-nav-link href="{{ route('staff.production.create') }}" :active="false">
                    <span class="inline-flex items-center gap-1.5">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        Log Production
                    </span>
                </x-mobile-nav-link>
                <x-mobile-nav-link href="{{ route('staff.materials.index') }}" :active="request()->routeIs('staff.materials.*')">Raw Materials</x-mobile-nav-link>
            @endif
        </div>
        @endauth
    </header>

    {{-- ===================== FLASH MESSAGES ===================== --}}
    @if(session('status') || session('error'))
    <div class="max-w-screen-xl mx-auto w-full px-4 sm:px-6 pt-4" x-data="{ show: true }" x-show="show" x-cloak
         x-init="setTimeout(() => show = false, 5000)">
        @if(session('status'))
        <div class="flex items-center gap-3 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-xl text-sm font-medium">
            <svg class="w-5 h-5 text-green-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
            {{ session('status') }}
            <button @click="show = false" class="ml-auto text-green-600 hover:text-green-800">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        @endif
        @if(session('error'))
        <div class="flex items-center gap-3 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-xl text-sm font-medium">
            <svg class="w-5 h-5 text-red-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            {{ session('error') }}
            <button @click="show = false" class="ml-auto text-red-600 hover:text-red-800">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        @endif
    </div>
    @endif

    {{-- ===================== PAGE CONTENT ===================== --}}
    <main class="flex-1 max-w-screen-xl mx-auto w-full px-4 sm:px-6 py-6">
        {{ $slot }}
    </main>

    {{-- ===================== FOOTER ===================== --}}
    <footer class="border-t border-gray-200 mt-auto">
        <div class="max-w-screen-xl mx-auto px-4 sm:px-6 py-3">
            <p class="text-center text-xs text-gray-400">
                JABODKA-SIMS &mdash; Jabodka Outdoor &copy; {{ date('Y') }}
            </p>
        </div>
    </footer>

    @stack('scripts')
</body>
</html>


