<x-layouts.app title="Subscriptions">

    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-ink">Subscriptions</h1>
            <p class="text-sm text-gray-500 mt-0.5">Plan status and T&amp;C acceptance</p>
        </div>
        {{-- Add subscription inline modal --}}
        <button x-data x-on:click="$dispatch('open-modal', 'add-subscription')"
                class="inline-flex items-center gap-2 bg-navy hover:bg-navy-dark text-white text-sm font-semibold px-4 py-2.5 rounded-xl transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Add Subscription
        </button>
    </div>

    {{-- Subscription list --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        @if($subscriptions->isEmpty())
            <div class="px-6 py-12 text-center text-sm text-gray-400">No subscriptions yet.</div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-sand border-b border-gray-100">
                            <th class="text-left px-5 py-3 font-semibold text-gray-600">User</th>
                            <th class="text-left px-5 py-3 font-semibold text-gray-600">Plan</th>
                            <th class="text-left px-5 py-3 font-semibold text-gray-600">Status</th>
                            <th class="text-left px-5 py-3 font-semibold text-gray-600">Period</th>
                            <th class="text-left px-5 py-3 font-semibold text-gray-600">T&amp;C</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50" x-data="{ editing: null }">
                        @foreach($subscriptions as $sub)
                        <tr class="hover:bg-sand/50 transition">
                            <td class="px-5 py-3.5 font-medium text-ink">{{ $sub->user->username }}</td>
                            <td class="px-5 py-3.5 text-gray-600">{{ $sub->plan_type }}</td>
                            <td class="px-5 py-3.5">
                                @php
                                    $sc = match($sub->status) {
                                        'active'    => 'bg-green-100 text-green-700',
                                        'expired'   => 'bg-red-100 text-red-700',
                                        'cancelled' => 'bg-gray-100 text-gray-600',
                                        default     => 'bg-gray-100 text-gray-600',
                                    };
                                @endphp
                                <span class="inline-block px-2.5 py-0.5 rounded-full text-xs font-semibold {{ $sc }}">
                                    {{ ucfirst($sub->status) }}
                                </span>
                            </td>
                            <td class="px-5 py-3.5 text-gray-500 text-xs">
                                {{ $sub->start_date?->format('M j, Y') ?? '—' }}
                                @if($sub->end_date) – {{ $sub->end_date->format('M j, Y') }} @endif
                            </td>
                            <td class="px-5 py-3.5">
                                @if($sub->terms_accepted)
                                    <svg class="w-4 h-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                    </svg>
                                @else
                                    <span class="text-gray-400 text-xs">Not accepted</span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    {{-- Add subscription modal --}}
    <div x-data="{ open: false }"
         x-on:open-modal.window="if ($event.detail === 'add-subscription') open = true"
         x-show="open" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/40" @click="open = false"></div>
        <div class="relative bg-white rounded-2xl shadow-xl w-full max-w-md p-6" x-transition>
            <h2 class="text-lg font-bold text-ink mb-5">Add Subscription</h2>
            <form method="POST" action="{{ route('super.subscriptions.store') }}">
                @csrf
                <div class="mb-4">
                    <label class="block text-sm font-semibold text-ink mb-1.5">User (Admin)</label>
                    <select name="user_id" required class="w-full h-11 px-4 rounded-xl border-2 border-gray-200 bg-white text-sm focus:outline-none focus:border-navy">
                        <option value="">Select user…</option>
                        @foreach($admins as $admin)
                            <option value="{{ $admin->id }}">{{ $admin->username }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-semibold text-ink mb-1.5">Plan Type</label>
                    <input name="plan_type" type="text" required placeholder="e.g. Monthly, Annual"
                           class="w-full h-11 px-4 rounded-xl border-2 border-gray-200 bg-white text-sm focus:outline-none focus:border-navy">
                </div>
                <div class="grid grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-semibold text-ink mb-1.5">Status</label>
                        <select name="status" required class="w-full h-11 px-4 rounded-xl border-2 border-gray-200 bg-white text-sm focus:outline-none focus:border-navy">
                            <option value="active">Active</option>
                            <option value="expired">Expired</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>
                    <div class="flex items-center gap-2 pt-7">
                        <input type="checkbox" name="terms_accepted" id="ta" value="1" class="w-4 h-4 rounded border-gray-300 text-navy">
                        <label for="ta" class="text-sm text-gray-600">T&amp;C Accepted</label>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4 mb-6">
                    <div>
                        <label class="block text-sm font-semibold text-ink mb-1.5">Start Date</label>
                        <input name="start_date" type="date" class="w-full h-11 px-4 rounded-xl border-2 border-gray-200 bg-white text-sm focus:outline-none focus:border-navy">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-ink mb-1.5">End Date</label>
                        <input name="end_date" type="date" class="w-full h-11 px-4 rounded-xl border-2 border-gray-200 bg-white text-sm focus:outline-none focus:border-navy">
                    </div>
                </div>
                <div class="flex gap-3">
                    <button type="submit" class="flex-1 h-11 bg-navy hover:bg-navy-dark text-white font-semibold text-sm rounded-xl transition">Save</button>
                    <button type="button" @click="open = false" class="flex-1 h-11 bg-gray-100 hover:bg-gray-200 text-ink text-sm font-medium rounded-xl transition">Cancel</button>
                </div>
            </form>
        </div>
    </div>

</x-layouts.app>
