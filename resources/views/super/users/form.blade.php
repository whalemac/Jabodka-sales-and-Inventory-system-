<x-layouts.app title="{{ $user->exists ? 'Edit Account' : 'Add Account' }}">

    <div class="mb-6">
        <a href="{{ route('super.users.index') }}" class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-navy mb-3">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            Back to Users
        </a>
        <h1 class="text-2xl font-bold text-ink">{{ $user->exists ? 'Edit Account' : 'Add Account' }}</h1>
    </div>

    <div class="max-w-md">
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
            <form method="POST"
                  action="{{ $user->exists ? route('super.users.update', $user) : route('super.users.store') }}">
                @csrf
                @if($user->exists)
                    @method('PUT')
                @endif

                {{-- Username --}}
                <div class="mb-5">
                    <label class="block text-sm font-semibold text-ink mb-1.5" for="username">Username</label>
                    <input id="username" name="username" type="text" required
                           value="{{ old('username', $user->username) }}"
                           class="w-full h-11 px-4 rounded-xl border-2 bg-white text-sm transition focus:outline-none focus:border-navy
                                  {{ $errors->has('username') ? 'border-red-400' : 'border-gray-200' }}">
                    @error('username')
                        <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Password --}}
                <div class="mb-5">
                    <label class="block text-sm font-semibold text-ink mb-1.5" for="password">
                        Password
                        @if($user->exists)
                            <span class="text-gray-400 font-normal">(leave blank to keep current)</span>
                        @endif
                    </label>
                    <input id="password" name="password" type="password"
                           {{ $user->exists ? '' : 'required' }}
                           class="w-full h-11 px-4 rounded-xl border-2 bg-white text-sm transition focus:outline-none focus:border-navy
                                  {{ $errors->has('password') ? 'border-red-400' : 'border-gray-200' }}"
                           placeholder="{{ $user->exists ? '••••••••' : 'Min. 8 characters' }}">
                    @error('password')
                        <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Role --}}
                <div class="mb-7">
                    <label class="block text-sm font-semibold text-ink mb-1.5" for="role">Role</label>
                    <select id="role" name="role" required
                            class="w-full h-11 px-4 rounded-xl border-2 bg-white text-sm transition focus:outline-none focus:border-navy
                                   {{ $errors->has('role') ? 'border-red-400' : 'border-gray-200' }}">
                        <option value="">Select role…</option>
                        <option value="admin"  {{ old('role', $user->role) === 'admin'  ? 'selected' : '' }}>Owner (Admin)</option>
                        <option value="staff"  {{ old('role', $user->role) === 'staff'  ? 'selected' : '' }}>Staff</option>
                    </select>
                    @error('role')
                        <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex gap-3">
                    <button type="submit"
                            class="flex-1 h-11 bg-navy hover:bg-navy-dark text-white font-semibold text-sm rounded-xl transition">
                        {{ $user->exists ? 'Save Changes' : 'Create Account' }}
                    </button>
                    <a href="{{ route('super.users.index') }}"
                       class="flex-1 h-11 flex items-center justify-center bg-gray-100 hover:bg-gray-200 text-ink text-sm font-medium rounded-xl transition">
                        Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>

</x-layouts.app>
