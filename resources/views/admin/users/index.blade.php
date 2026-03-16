<x-app-layout>
    <x-slot name="header">
        <div
            class="mt-16 p-4 bg-gradient-to-r from-slate-700 to-slate-900 rounded-lg shadow-lg flex items-center justify-between">
            <h2 class="font-semibold text-2xl text-white leading-tight">
                User Management
            </h2>

            <a href="{{ route('admin.users.create') }}"
                class="inline-flex items-center px-4 py-2 bg-white text-slate-800 rounded-md text-sm font-semibold hover:bg-slate-100">
                Create User
            </a>
        </div>
    </x-slot>

    <div id="navbar-spacer"></div>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if (session('success'))
                <div class="bg-green-50 border border-green-200 text-green-800 p-4 rounded-lg">
                    {{ session('success') }}
                </div>
            @endif

            <div class="bg-white shadow-sm rounded-lg p-6">
                <form method="GET" action="{{ route('admin.users.index') }}">
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                            <input type="text" name="search" value="{{ request('search') }}"
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                placeholder="Name or email">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Role</label>
                            <select name="role"
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">All roles</option>
                                @foreach ($roles as $role)
                                    <option value="{{ $role }}"
                                        {{ request('role') === $role ? 'selected' : '' }}>
                                        {{ ucwords(str_replace('_', ' ', $role)) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                            <select name="status"
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">All statuses</option>
                                <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active
                                </option>
                                <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>
                                    Inactive</option>
                            </select>
                        </div>

                        <div class="flex items-end gap-3">
                            <button type="submit"
                                class="px-4 py-2 bg-indigo-600 text-white rounded-md font-semibold hover:bg-indigo-700">
                                Filter
                            </button>

                            <a href="{{ route('admin.users.index') }}"
                                class="px-4 py-2 bg-gray-100 text-gray-700 rounded-md font-semibold hover:bg-gray-200">
                                Reset
                            </a>
                        </div>
                    </div>
                </form>
            </div>

            <div class="bg-white shadow-sm rounded-lg overflow-hidden">
                <div class="p-6 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">Users</h3>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Email</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Role</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-gray-200 bg-white">
                            @forelse ($users as $user)
                                <tr>
                                    <td class="px-6 py-4 text-sm font-medium text-gray-900">
                                        {{ $user->name }}
                                    </td>

                                    <td class="px-6 py-4 text-sm text-gray-600">
                                        {{ $user->email }}
                                    </td>

                                    <td class="px-6 py-4 text-sm">
                                        @php
                                            $badgeClass = match ($user->role) {
                                                'admin' => 'bg-red-100 text-red-800',
                                                'headmaster' => 'bg-purple-100 text-purple-800',
                                                'teacher' => 'bg-blue-100 text-blue-800',
                                                'librarian' => 'bg-emerald-100 text-emerald-800',
                                                'accounts_officer' => 'bg-amber-100 text-amber-800',
                                                default => 'bg-gray-100 text-gray-800',
                                            };
                                        @endphp

                                        <span class="px-2 py-1 rounded-full text-xs font-semibold {{ $badgeClass }}">
                                            {{ ucwords(str_replace('_', ' ', $user->role)) }}
                                        </span>
                                    </td>

                                    <td class="px-6 py-4 text-sm">
                                        @if ($user->status === 'active')
                                            <span
                                                class="px-2 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-800">
                                                Active
                                            </span>
                                        @else
                                            <span
                                                class="px-2 py-1 rounded-full text-xs font-semibold bg-gray-100 text-gray-800">
                                                Inactive
                                            </span>
                                        @endif
                                    </td>

                                    <td class="px-6 py-4 text-sm">
                                        <div class="flex flex-wrap gap-2">
                                            <a href="{{ route('admin.users.edit', $user) }}"
                                                class="px-3 py-1 bg-indigo-100 text-indigo-700 rounded-md hover:bg-indigo-200">
                                                Edit
                                            </a>

                                            <form method="POST"
                                                action="{{ route('admin.users.reset-password', $user) }}">
                                                @csrf
                                                <button type="submit"
                                                    class="px-3 py-1 bg-yellow-100 text-yellow-800 rounded-md hover:bg-yellow-200">
                                                    Reset Password
                                                </button>
                                            </form>

                                            @if ($user->status === 'active')
                                                <form method="POST"
                                                    action="{{ route('admin.users.deactivate', $user) }}">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button type="submit"
                                                        class="px-3 py-1 bg-red-100 text-red-700 rounded-md hover:bg-red-200">
                                                        Deactivate
                                                    </button>
                                                </form>
                                            @else
                                                <form method="POST"
                                                    action="{{ route('admin.users.activate', $user) }}">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button type="submit"
                                                        class="px-3 py-1 bg-green-100 text-green-700 rounded-md hover:bg-green-200">
                                                        Activate
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-6 py-8 text-center text-gray-500">
                                        No users found.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="p-6 border-t border-gray-200">
                    {{ $users->links() }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
