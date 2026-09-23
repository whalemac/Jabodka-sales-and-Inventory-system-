<x-layouts.app title="Dashboard">

    <div class="mb-6">
        <h1 class="text-2xl font-bold text-ink">Super Admin Dashboard</h1>
        <p class="text-sm text-gray-500 mt-0.5">{{ now()->format('l, F j, Y') }}</p>
    </div>

    <div class="grid grid-cols-2 gap-4 mb-8 max-w-lg">
        <x-kpi-card
            label="Total Users"
            value="{{ $userCount }}"
            sub="Admin &amp; Staff accounts"
            color="navy"
            href="{{ route('super.users.index') }}"
            :icon="'<svg class=\"w-6 h-6\" fill=\"none\" stroke=\"currentColor\" viewBox=\"0 0 24 24\"><path stroke-linecap=\"round\" stroke-linejoin=\"round\" stroke-width=\"2\" d=\"M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z\"/></svg>'"
        />
        <x-kpi-card
            label="Active Subscriptions"
            value="{{ $activeSubs }}"
            sub="Currently active plans"
            color="{{ $activeSubs > 0 ? 'green' : 'white' }}"
            href="{{ route('super.subscriptions.index') }}"
            :icon="'<svg class=\"w-6 h-6\" fill=\"none\" stroke=\"currentColor\" viewBox=\"0 0 24 24\"><path stroke-linecap=\"round\" stroke-linejoin=\"round\" stroke-width=\"2\" d=\"M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z\"/></svg>'"
        />
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 max-w-lg">
        <a href="{{ route('super.users.index') }}"
           class="flex items-center gap-4 bg-white rounded-2xl shadow-sm border border-gray-100 p-5 hover:border-navy hover:shadow-md transition group">
            <div class="w-12 h-12 rounded-xl bg-navy/10 group-hover:bg-navy flex items-center justify-center transition shrink-0">
                <svg class="w-6 h-6 text-navy group-hover:text-white transition" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                </svg>
            </div>
            <div>
                <p class="font-semibold text-ink text-sm">User Management</p>
                <p class="text-xs text-gray-500 mt-0.5">Create and manage Admin &amp; Staff accounts</p>
            </div>
        </a>

        <a href="{{ route('super.subscriptions.index') }}"
           class="flex items-center gap-4 bg-white rounded-2xl shadow-sm border border-gray-100 p-5 hover:border-navy hover:shadow-md transition group">
            <div class="w-12 h-12 rounded-xl bg-navy/10 group-hover:bg-navy flex items-center justify-center transition shrink-0">
                <svg class="w-6 h-6 text-navy group-hover:text-white transition" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                </svg>
            </div>
            <div>
                <p class="font-semibold text-ink text-sm">Subscriptions</p>
                <p class="text-xs text-gray-500 mt-0.5">Manage plan status and T&amp;C acceptance</p>
            </div>
        </a>
    </div>

</x-layouts.app>
