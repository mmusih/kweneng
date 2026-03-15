<x-app-layout>
    <x-slot name="header">
        <div
            class="mt-16 p-6 bg-gradient-to-r from-slate-700 to-slate-900 rounded-lg shadow-lg flex items-center justify-center">
            <div class="flex items-center justify-between w-full">
                <h2 class="font-semibold text-2xl text-white leading-tight">
                    Activity Logs
                </h2>
                <a href="{{ route('admin.dashboard') }}"
                    class="text-white hover:text-slate-200 text-sm font-medium flex items-center">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    Back to Dashboard
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <form method="GET" action="{{ route('admin.activity-logs.index') }}"
                        class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">User</label>
                            <select name="user_id" class="w-full border-gray-300 rounded-md shadow-sm">
                                <option value="">All Users</option>
                                @foreach ($users as $user)
                                    <option value="{{ $user->id }}"
                                        {{ request('user_id') == $user->id ? 'selected' : '' }}>
                                        {{ $user->name }} ({{ $user->role }})
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Action</label>
                            <input type="text" name="action" value="{{ request('action') }}"
                                class="w-full border-gray-300 rounded-md shadow-sm" placeholder="e.g. term.locked">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Date From</label>
                            <input type="date" name="date_from" value="{{ request('date_from') }}"
                                class="w-full border-gray-300 rounded-md shadow-sm">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Date To</label>
                            <input type="date" name="date_to" value="{{ request('date_to') }}"
                                class="w-full border-gray-300 rounded-md shadow-sm">
                        </div>

                        <div class="md:col-span-4 flex gap-3">
                            <button type="submit"
                                class="inline-flex items-center px-4 py-2 bg-slate-700 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-slate-800">
                                Filter Logs
                            </button>

                            <a href="{{ route('admin.activity-logs.index') }}"
                                class="inline-flex items-center px-4 py-2 bg-gray-200 border border-transparent rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-300">
                                Reset
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-2 text-left">Date / Time</th>
                                <th class="px-4 py-2 text-left">User</th>
                                <th class="px-4 py-2 text-left">Action</th>
                                <th class="px-4 py-2 text-left">Description</th>
                                <th class="px-4 py-2 text-left">Route</th>
                                <th class="px-4 py-2 text-left">IP</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @forelse($logs as $log)
                                <tr>
                                    <td class="px-4 py-2 whitespace-nowrap text-gray-700">
                                        {{ $log->created_at->format('M j, Y H:i:s') }}
                                    </td>
                                    <td class="px-4 py-2">
                                        <div class="font-medium text-gray-900">
                                            {{ $log->user->name ?? 'System / Deleted User' }}
                                        </div>
                                        <div class="text-xs text-gray-500">
                                            {{ $log->user->email ?? '' }}
                                        </div>
                                    </td>
                                    <td class="px-4 py-2 whitespace-nowrap">
                                        <span
                                            class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-slate-100 text-slate-800">
                                            {{ $log->action }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-2 text-gray-700">
                                        {{ $log->description ?? '-' }}
                                    </td>
                                    <td class="px-4 py-2 text-gray-500">
                                        {{ $log->route ?? '-' }}
                                    </td>
                                    <td class="px-4 py-2 text-gray-500">
                                        {{ $log->ip_address ?? '-' }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-4 py-6 text-center text-gray-500">
                                        No activity logs found.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>

                    <div class="mt-4">
                        {{ $logs->links() }}
                    </div>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
