<x-app-layout>
    @php($messageRoutePrefix = $messageRoutePrefix ?? (request()->routeIs('office.*') ? 'office' : 'admin'))
    <x-slot name="header">
        <div class="mt-16 p-5 rounded-2xl kw-page-header flex items-center justify-between gap-4">
            <h2 class="font-bold text-2xl text-white">Messages</h2>
            @if (($unreadCount ?? 0) > 0)
                <span class="inline-flex items-center rounded-full bg-white px-3 py-1 text-xs font-semibold text-slate-800">
                    {{ $unreadCount }} unread
                </span>
            @endif
        </div>
    </x-slot>

    <div class="py-12 kw-soft-section min-h-screen">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">

            @if (session('success'))
                <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg text-sm text-green-800">
                    {{ session('success') }}
                </div>
            @endif

            <div class="bg-white kw-panel overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Parent</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Subject</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Last Activity</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Status</th>
                            <th class="px-6 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse ($messages as $msg)
                            <tr class="{{ !$msg->is_read_by_admin ? 'bg-indigo-50' : '' }} hover:bg-gray-50 transition">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">
                                        {{ $msg->parent->user->name ?? 'Unknown Parent' }}
                                    </div>
                                    <div class="text-xs text-gray-400">{{ $msg->parent->user->email ?? '' }}</div>
                                </td>
                                <td class="px-6 py-4">
                                    <p
                                        class="text-sm {{ !$msg->is_read_by_admin ? 'font-semibold text-gray-900' : 'text-gray-700' }} truncate max-w-xs">
                                        {{ $msg->subject }}
                                    </p>
                                    <p class="text-xs text-gray-400 truncate max-w-xs mt-0.5">
                                        {{ Str::limit($msg->body, 60) }}
                                    </p>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ ($msg->last_reply_at ?? $msg->created_at)->format('M j, Y g:i A') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if (!$msg->is_read_by_admin)
                                        <span
                                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-800">
                                            Unread
                                        </span>
                                    @else
                                        <span
                                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600">
                                            Read
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <a href="{{ route($messageRoutePrefix . '.messages.show', $msg) }}"
                                        class="text-indigo-600 hover:text-indigo-900">View thread →</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-12 text-center text-sm text-gray-400">
                                    No messages from parents yet.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-4">{{ $messages->links() }}</div>
        </div>
    </div>
</x-app-layout>
