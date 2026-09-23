<x-layouts.app title="User Management">

    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-ink">User Management</h1>
            <p class="text-sm text-gray-500 mt-0.5">Admin and Staff accounts</p>
        </div>
        <a href="{{ route('super.users.create') }}"
           class="inline-flex items-center gap-2 bg-navy hover:bg-navy-dark text-white text-sm font-semibold px-4 py-2.5 rounded-xl transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Add Account
        </a>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        @if($users->isEmpty())
            <div class="px-6 py-12 text-center text-sm text-gray-400">No accounts found.</div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-sand border-b border-gray-100">
                            <th class="text-left px-5 py-3 font-semibold text-gray-600">Username</th>
                            <th class="text-left px-5 py-3 font-semibold text-gray-600">Role</th>
                            <th class="text-left px-5 py-3 font-semibold text-gray-600">Created</th>
                            <th class="px-5 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @foreach($users as $user)
                        <tr class="hover:bg-sand/50 transition">
                            <td class="px-5 py-3.5 font-medium text-ink">{{ $user->username }}</td>
                            <td class="px-5 py-3.5">
                                @php
                                    $roleColor = match($user->role) {
                                        'super_admin' => 'bg-navy/10 text-navy',
                                        'admin'       => 'bg-accent/15 text-accent-dark',
                                        'staff'       => 'bg-green-100 text-green-700',
                                        default       => 'bg-gray-100 text-gray-600',
                                    };
                                @endphp
                                <span class="inline-block px-2.5 py-0.5 rounded-full text-xs font-semibold {{ $roleColor }}">
                                    {{ $user->roleLabel() }}
                                </span>
                            </td>
                            <td class="px-5 py-3.5 text-gray-500">{{ $user->created_at->format('M j, Y') }}</td>
                            <td class="px-5 py-3.5 text-right">
                                @if(!$user->isSuperAdmin())
                                <a href="{{ route('super.users.edit', $user) }}"
                                   class="inline-flex items-center gap-1.5 text-xs text-navy font-medium hover:underline">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                    </svg>
                                    Edit
                                </a>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

</x-layouts.app>
