<x-app-layout>
    @php($announcementRoutePrefix = $announcementRoutePrefix ?? (request()->routeIs('office.*') ? 'office' : 'admin'))
    @php
        $requiresAcknowledgement = $requiresAcknowledgement ?? $announcement->requiresAcknowledgement();
    @endphp
<x-slot name="header">
        <div class="mt-16 p-5 rounded-2xl kw-page-header flex flex-col md:flex-row md:justify-between md:items-center gap-4">
            <div>
                <h2 class="font-semibold text-xl text-white leading-tight">
                    Notice Tracking
                </h2>
                <p class="text-sm text-white/80 mt-1">{{ $announcement->title }}</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route($announcementRoutePrefix . '.announcements.tracking.export', $announcement) }}"
                    class="inline-flex items-center px-4 py-2 bg-white text-slate-800 rounded-md font-semibold text-xs uppercase tracking-widest hover:bg-slate-100">
                    Export CSV
                </a>
                <a href="{{ route($announcementRoutePrefix . '.announcements.index') }}"
                    class="inline-flex items-center px-4 py-2 bg-white/15 border border-white/25 rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-white/25">
                    Back
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-12 kw-soft-section min-h-screen">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            {{-- TRACKING_REMINDER_EXPORT_ACTIONS --}}
            <div class="bg-white kw-panel p-4">
                <div class="flex flex-wrap items-center gap-2">
                    @if (Route::has('admin.announcements.tracking.export'))
                        <a href="{{ route($announcementRoutePrefix . '.announcements.tracking.export', $announcement) }}"
                            class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700">
                            Export CSV
                        </a>
                    @endif

                    @if (Route::has('admin.announcements.tracking.reminder'))
                        <form method="POST" action="{{ route($announcementRoutePrefix . '.announcements.tracking.reminder', $announcement) }}"
                            onsubmit="return confirm('Send reminder to unread parents with app tokens?');">
                            @csrf
                            <input type="hidden" name="target" value="unread">
                            <button type="submit"
                                class="inline-flex items-center px-4 py-2 bg-orange-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-orange-700">
                                Remind Unread
                            </button>
                        </form>

                        @if ($requiresAcknowledgement)
                            <form method="POST" action="{{ route($announcementRoutePrefix . '.announcements.tracking.reminder', $announcement) }}"
                                onsubmit="return confirm('Send reminder to parents who have not acknowledged this notice?');">
                                @csrf
                                <input type="hidden" name="target" value="pending_acknowledgement">
                                <button type="submit"
                                    class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700">
                                    Remind Pending Ack.
                                </button>
                            </form>
                        @endif
                    @endif
                </div>

                <p class="text-xs text-gray-500 mt-2">
                    Reminder notifications are sent only to selected parents who currently have an app token.
                </p>
            </div>
            @php
                $requiresAcknowledgement = method_exists($announcement, 'requiresAcknowledgement')
                    ? $announcement->requiresAcknowledgement()
                    : in_array($announcement->type, ['urgent'], true);
            @endphp

            <div
                class="grid grid-cols-1 md:grid-cols-3 {{ $requiresAcknowledgement ? 'lg:grid-cols-7' : 'lg:grid-cols-5' }} gap-4">
                <div class="bg-white kw-panel p-4">
                    <div class="text-xs text-gray-500 uppercase">Targeted</div>
                    <div class="text-2xl font-bold text-gray-900">{{ $stats['targeted'] }}</div>
                </div>
                <div class="bg-white kw-panel p-4">
                    <div class="text-xs text-gray-500 uppercase">Read</div>
                    <div class="text-2xl font-bold text-green-700">{{ $stats['read'] }}</div>
                </div>
                <div class="bg-white kw-panel p-4">
                    <div class="text-xs text-gray-500 uppercase">Unread</div>
                    <div class="text-2xl font-bold text-red-700">{{ $stats['unread'] }}</div>
                </div>
                @if ($requiresAcknowledgement)
                    <div class="bg-white kw-panel p-4">
                        <div class="text-xs text-gray-500 uppercase">Acknowledged</div>
                        <div class="text-2xl font-bold text-blue-700">{{ $stats['acknowledged'] }}</div>
                    </div>
                    <div class="bg-white kw-panel p-4">
                        <div class="text-xs text-gray-500 uppercase">Pending Ack.</div>
                        <div class="text-2xl font-bold text-orange-700">{{ $stats['pending_acknowledgement'] }}</div>
                    </div>
                @else
                    <div class="bg-white kw-panel p-4 md:col-span-1 lg:col-span-2">
                        <div class="text-xs text-gray-500 uppercase">Acknowledgement</div>
                        <div class="text-lg font-semibold text-gray-700 mt-1">Not Required</div>
                    </div>
                @endif
                <div class="bg-white kw-panel p-4">
                    <div class="text-xs text-gray-500 uppercase">With App Token</div>
                    <div class="text-2xl font-bold text-purple-700">{{ $stats['with_token'] }}</div>
                </div>
                <div class="bg-white kw-panel p-4">
                    <div class="text-xs text-gray-500 uppercase">No App Token</div>
                    <div class="text-2xl font-bold text-gray-700">{{ $stats['without_token'] }}</div>
                </div>
            </div>


            {{-- Delivery Summary: safe admin-only visibility block --}}
            <div class="bg-white kw-panel p-6">
                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-2 mb-4">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">Delivery Summary</h3>
                        <p class="text-sm text-gray-500">
                            Shows the delivery reach based on the targeted parents and available app device tokens.
                        </p>
                    </div>

                    @if (! empty($announcement->push_sent_at))
                        <span class="inline-flex px-3 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                            Push Sent
                        </span>
                    @else
                        <span class="inline-flex px-3 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-700">
                            Push Not Sent / Not Recorded
                        </span>
                    @endif
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
                    <div class="border rounded-lg p-4">
                        <div class="text-xs text-gray-500 uppercase">Targeted Parents</div>
                        <div class="text-2xl font-bold text-gray-900">{{ $stats['targeted'] ?? 0 }}</div>
                    </div>

                    <div class="border rounded-lg p-4">
                        <div class="text-xs text-gray-500 uppercase">With App Token</div>
                        <div class="text-2xl font-bold text-purple-700">{{ $stats['with_token'] ?? 0 }}</div>
                    </div>

                    <div class="border rounded-lg p-4">
                        <div class="text-xs text-gray-500 uppercase">No App Token</div>
                        <div class="text-2xl font-bold text-gray-700">{{ $stats['without_token'] ?? 0 }}</div>
                    </div>

                    <div class="border rounded-lg p-4">
                        <div class="text-xs text-gray-500 uppercase">Push Eligible</div>
                        <div class="text-2xl font-bold text-blue-700">{{ $stats['with_token'] ?? 0 }}</div>
                        <div class="text-xs text-gray-400 mt-1">Parents with registered devices</div>
                    </div>

                    <div class="border rounded-lg p-4">
                        <div class="text-xs text-gray-500 uppercase">Last Push Sent</div>
                        <div class="text-sm font-semibold text-gray-900 mt-2">
                            {{ ! empty($announcement->push_sent_at) ? $announcement->push_sent_at->format('Y-m-d H:i') : 'Not recorded' }}
                        </div>
                    </div>
                </div>

                <div class="mt-4 text-xs text-gray-500 bg-gray-50 rounded-md p-3">
                    Push failure counts are not shown yet because individual Firebase send results are not stored. This summary safely shows who could receive push based on app token availability.
                </div>
            </div>

            <div class="bg-white kw-panel p-4">
                <div class="flex flex-wrap gap-2">
                    @php
                        $filters = [
                            'all' => 'All',
                            'read' => 'Read',
                            'unread' => 'Unread',
                            'no_token' => 'No App Token',
                        ];

                        if ($requiresAcknowledgement) {
                            $filters = [
                                'all' => 'All',
                                'read' => 'Read',
                                'unread' => 'Unread',
                                'acknowledged' => 'Acknowledged',
                                'pending_acknowledgement' => 'Pending Acknowledgement',
                                'no_token' => 'No App Token',
                            ];
                        }
                    @endphp

                    @foreach ($filters as $key => $label)
                        <a href="{{ route($announcementRoutePrefix . '.announcements.tracking', [$announcement, 'filter' => $key]) }}"
                            class="px-3 py-2 rounded-md text-sm {{ $filter === $key ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                            {{ $label }}
                        </a>
                    @endforeach
                </div>
            </div>

            <div class="bg-white kw-panel overflow-hidden">
                <div class="p-6 bg-white border-b border-gray-200">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Parent
                                    </th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Contact
                                    </th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                                        Student(s)</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Read
                                    </th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                                        Acknowledged</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">App
                                        Token</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse ($rows as $row)
                                    <tr>
                                        <td class="px-4 py-3 text-sm">
                                            <div class="font-medium text-gray-900">{{ $row['name'] }}</div>
                                            <div class="text-xs text-gray-400">Parent ID: {{ $row['parent_id'] }}</div>
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-600">
                                            <div>{{ $row['email'] ?: 'No email' }}</div>
                                            <div>{{ $row['phone'] ?: 'No phone' }}</div>
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-600 max-w-xs">
                                            {{ $row['students'] ?: 'No linked students' }}
                                        </td>
                                        <td class="px-4 py-3 text-sm">
                                            @if ($row['read'])
                                                <span
                                                    class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                                                    Read
                                                </span>
                                                <div class="text-xs text-gray-400 mt-1">{{ $row['read_at'] }}</div>
                                            @else
                                                <span
                                                    class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">
                                                    Unread
                                                </span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-sm">
                                            @if (!$requiresAcknowledgement)
                                                <span
                                                    class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-700">
                                                    Not Required
                                                </span>
                                            @elseif ($row['acknowledged'])
                                                <span
                                                    class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">
                                                    Acknowledged
                                                </span>
                                                <div class="text-xs text-gray-400 mt-1">{{ $row['acknowledged_at'] }}
                                                </div>
                                            @else
                                                <span
                                                    class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-orange-100 text-orange-800">
                                                    Pending
                                                </span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-sm">
                                            @if ($row['has_token'])
                                                <span
                                                    class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-purple-100 text-purple-800">
                                                    Available
                                                </span>
                                            @else
                                                <span
                                                    class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800">
                                                    No token
                                                </span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="px-4 py-6 text-center text-sm text-gray-500">
                                            No parents match this filter.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            {{-- ANNOUNCEMENT_ACTIVITY_LOG_PANEL --}}
            <div class="bg-white kw-panel overflow-hidden">
                <div class="p-6 bg-white border-b border-gray-200">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900">Activity Log</h3>
                            <p class="text-sm text-gray-500">Admin and system actions for this announcement.</p>
                        </div>
                    </div>

                    @php
                        $activityRows = \App\Models\AnnouncementActivity::with('performedBy')
                            ->where('announcement_id', $announcement->id)
                            ->latest()
                            ->limit(30)
                            ->get();
                    @endphp

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date/Time</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Action</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Details</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Performed By</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse ($activityRows as $activity)
                                    <tr>
                                        <td class="px-4 py-3 text-sm text-gray-600 whitespace-nowrap">
                                            {{ $activity->created_at?->format('Y-m-d H:i') }}
                                        </td>
                                        <td class="px-4 py-3 text-sm">
                                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-indigo-100 text-indigo-800">
                                                {{ $activity->action }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-600">
                                            {{ $activity->details ?: '—' }}
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-600 whitespace-nowrap">
                                            @if ($activity->performedBy)
                                                {{ $activity->performedBy->name ?? $activity->performedBy->email }}
                                            @else
                                                {{ ucfirst($activity->actor_type ?? 'system') }}
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="px-4 py-6 text-center text-sm text-gray-500">
                                            No activity recorded yet.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
