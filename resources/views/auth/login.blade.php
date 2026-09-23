<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Sign In — JABODKA-SIMS</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Source+Sans+3:wght@400;500;600;700&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-sand text-ink antialiased min-h-screen" x-data>

    <div class="min-h-screen flex flex-col md:flex-row">

        {{-- ===== LEFT PANEL (navy brand panel) ===== --}}
        <div class="hidden md:flex md:w-2/5 lg:w-1/2 bg-navy flex-col items-center justify-center px-10 py-12 relative overflow-hidden">
            {{-- Decorative background circles --}}
            <div class="absolute -top-24 -left-24 w-80 h-80 rounded-full bg-white/5 pointer-events-none"></div>
            <div class="absolute -bottom-32 -right-16 w-96 h-96 rounded-full bg-white/5 pointer-events-none"></div>

            <div class="relative z-10 text-center">
                <div class="mb-6">
                    <span class="text-4xl lg:text-5xl font-bold text-white tracking-wide">
                        JABODKA<span class="text-accent">-</span>SIMS
                    </span>
                </div>
                <p class="text-white/70 text-lg font-medium mb-2">Sales & Inventory System</p>
                <p class="text-white/50 text-sm leading-relaxed max-w-xs mx-auto">
                    Jabodka Outdoor · Davao City<br>
                    Gear up, sell smart, stay stocked.
                </p>

                <div class="mt-12 grid grid-cols-3 gap-4 text-center">
                    <div class="bg-white/10 rounded-xl p-4">
                        <svg class="w-7 h-7 text-accent mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 3h18l-2 13H5L3 3zm0 0l-.5-2H1M9 21a1 1 0 100-2 1 1 0 000 2zm10 0a1 1 0 100-2 1 1 0 000 2z"/>
                        </svg>
                        <p class="text-white/70 text-xs">Walk-In POS</p>
                    </div>
                    <div class="bg-white/10 rounded-xl p-4">
                        <svg class="w-7 h-7 text-accent mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7H4a2 2 0 00-2 2v6a2 2 0 002 2h16a2 2 0 002-2V9a2 2 0 00-2-2zM4 7V5a2 2 0 012-2h12a2 2 0 012 2v2"/>
                        </svg>
                        <p class="text-white/70 text-xs">Live Inventory</p>
                    </div>
                    <div class="bg-white/10 rounded-xl p-4">
                        <svg class="w-7 h-7 text-accent mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        <p class="text-white/70 text-xs">Reports</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- ===== RIGHT PANEL (login form) ===== --}}
        <div class="flex-1 flex flex-col items-center justify-center px-5 py-10 sm:px-10 md:px-12 lg:px-16">

            {{-- Mobile wordmark (shown only on small screens) --}}
            <div class="md:hidden mb-8 text-center">
                <span class="text-3xl font-bold text-navy tracking-wide">
                    JABODKA<span class="text-accent">-</span>SIMS
                </span>
                <p class="text-gray-500 text-sm mt-1">Sales & Inventory System</p>
            </div>

            <div class="w-full max-w-sm">
                <h1 class="text-2xl font-bold text-ink mb-1">Welcome back</h1>
                <p class="text-gray-500 text-sm mb-8">Sign in to continue to the dashboard</p>

                {{-- Validation errors --}}
                @if($errors->any())
                <div class="mb-5 bg-red-50 border border-red-200 rounded-xl px-4 py-3" x-data x-init="$el.scrollIntoView({ behavior: 'smooth', block: 'center' })">
                    <p class="text-sm font-semibold text-red-700 mb-1">Sign-in failed</p>
                    @foreach($errors->all() as $error)
                        <p class="text-sm text-red-600">{{ $error }}</p>
                    @endforeach
                </div>
                @endif

                <form method="POST" action="{{ route('login') }}" x-data="{ loading: false }" @submit="loading = true">
                    @csrf

                    {{-- Username --}}
                    <div class="mb-5">
                        <label for="username" class="block text-sm font-semibold text-ink mb-1.5">Username</label>
                        <input
                            id="username"
                            name="username"
                            type="text"
                            autocomplete="username"
                            autofocus
                            required
                            value="{{ old('username') }}"
                            class="w-full h-12 px-4 rounded-xl border-2 bg-white text-ink text-sm
                                   transition focus:outline-none focus:border-navy
                                   {{ $errors->has('username') ? 'border-red-400' : 'border-gray-200' }}"
                            placeholder="Enter your username"
                        >
                    </div>

                    {{-- Password --}}
                    <div class="mb-5" x-data="{ show: false }">
                        <label for="password" class="block text-sm font-semibold text-ink mb-1.5">Password</label>
                        <div class="relative">
                            <input
                                id="password"
                                name="password"
                                :type="show ? 'text' : 'password'"
                                autocomplete="current-password"
                                required
                                class="w-full h-12 px-4 pr-12 rounded-xl border-2 bg-white text-ink text-sm
                                       transition focus:outline-none focus:border-navy
                                       {{ $errors->has('password') ? 'border-red-400' : 'border-gray-200' }}"
                                placeholder="Enter your password"
                            >
                            <button type="button" @click="show = !show"
                                    class="absolute inset-y-0 right-0 px-4 flex items-center text-gray-400 hover:text-gray-600">
                                <svg x-show="!show" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                                <svg x-show="show" x-cloak class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                                </svg>
                            </button>
                        </div>
                    </div>

                    {{-- Remember me --}}
                    <div class="flex items-center gap-2 mb-7">
                        <input id="remember" name="remember" type="checkbox"
                               class="w-4 h-4 rounded border-gray-300 text-navy focus:ring-navy cursor-pointer">
                        <label for="remember" class="text-sm text-gray-600 cursor-pointer select-none">Keep me signed in</label>
                    </div>

                    {{-- Submit --}}
                    <button type="submit"
                            :disabled="loading"
                            class="w-full h-12 bg-navy hover:bg-navy-dark text-white font-semibold text-sm rounded-xl
                                   transition focus:outline-none focus:ring-2 focus:ring-navy focus:ring-offset-2
                                   disabled:opacity-60 disabled:cursor-not-allowed flex items-center justify-center gap-2">
                        <svg x-show="loading" x-cloak class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/>
                        </svg>
                        <span x-text="loading ? 'Signing in…' : 'Sign In'">Sign In</span>
                    </button>
                </form>

                <p class="mt-8 text-center text-xs text-gray-400">
                    Account access is managed by your administrator.
                </p>
            </div>
        </div>
    </div>

</body>
</html>
