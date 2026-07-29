<x-app-layout>
    <x-slot name="header">
        <div class="mt-16 flex flex-col md:flex-row md:items-center md:justify-between gap-3">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Parent Absence Notices
                </h2>
                <p class="text-sm text-gray-500 mt-1">
                    Notices submitted by parents when a student will be absent.
                </p>
            </div>

            <a href="{{ route('admin.dashboard') }}"
                class="inline-flex items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">
                Back to Dashboard
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if (session('success'))
                <div class="bg-green-50 border border-green-200 text-green-800 rounded-lg p-4">
                    {{ session('success') }}
                </div>
            @endif

            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <a href="{{ route('admin.absence-notices.index') }}"
                    class="bg-white shadow-sm rounded-lg p-4 border {{ empty($status) ? 'border-blue-500' : 'border-transparent' }}">
                    <div class="text-xs text-gray-500 uppercase">All</div>
                    <div class="text-2xl font-bold">{{ $counts['all'] }}</div>
                </a>

                <a href="{{ route('admin.absence-notices.index', ['status' => 'pending']) }}"
                    class="bg-white shadow-sm rounded-lg p-4 border {{ $status === 'pending' ? 'border-yellow-500' : 'border-transparent' }}">
                    <div class="text-xs text-gray-500 uppercase">Pending</div>
                    <div class="text-2xl font-bold text-yellow-700">{{ $counts['pending'] }}</div>
                </a>

                <a href="{{ route('admin.absence-notices.index', ['status' => 'seen']) }}"
                    class="bg-white shadow-sm rounded-lg p-4 border {{ $status === 'seen' ? 'border-blue-500' : 'border-transparent' }}">
                    <div class="text-xs text-gray-500 uppercase">Seen</div>
                    <div class="text-2xl font-bold text-blue-700">{{ $counts['seen'] }}</div>
                </a>

                <a href="{{ route('admin.absence-notices.index', ['status' => 'resolved']) }}"
                    class="bg-white shadow-sm rounded-lg p-4 border {{ $status === 'resolved' ? 'border-green-500' : 'border-transparent' }}">
                    <div class="text-xs text-gray-500 uppercase">Resolved</div>
                    <div class="text-2xl font-bold text-green-700">{{ $counts['resolved'] }}</div>
                </a>
            </div>

            <div class="bg-white shadow-sm rounded-lg p-4">
                <form method="GET" action="{{ route('admin.absence-notices.index') }}" class="flex flex-col md:flex-row gap-3">
                    @if ($status)
                        <input type="hidden" name="status" value="{{ $status }}">
                    @endif

                    <input type="text" name="search" value="{{ $search }}"
                        placeholder="Search student, parent, class, reason..."
                        class="flex-1 rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">

                    <button type="submit"
                        class="px-4 py-2 bg-blue-600 text-white rounded-md font-semibold text-sm hover:bg-blue-700">
                        Search
                    </button>

                    <a href="{{ route('admin.absence-notices.index') }}"
                        class="px-4 py-2 bg-gray-100 text-gray-700 rounded-md font-semibold text-sm hover:bg-gray-200 text-center">
                        Clear
                    </a>
                </form>
            </div>

            <div class="bg-white shadow-sm rounded-lg overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left font-semibold text-gray-600">Student</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-600">Parent</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-600">Absent Date</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-600">Return Date</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-600">Reason</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-600">Status</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-600">Submitted</th>
                                <th class="px-4 py-3 text-right font-semibold text-gray-600">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($notices as $notice)
                                <tr>
                                    <td class="px-4 py-3">
                                        <div class="font-semibold text-gray-900">
                                            {{ $notice->student->user->name ?? 'Unnamed student' }}
                                        </div>
                                        <div class="text-xs text-gray-500">
                                            {{ $notice->student->currentClass->name ?? 'No class' }}
                                        </div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div>{{ $notice->parent->user->name ?? 'Unknown parent' }}</div>
                                        <div class="text-xs text-gray-500">{{ $notice->parent->user->email ?? '' }}</div>
                                    </td>
                                    <td class="px-4 py-3">{{ optional($notice->absence_date)->format('d M Y') }}</td>
                                    <td class="px-4 py-3">{{ optional($notice->expected_return_date)->format('d M Y') ?? '—' }}</td>
                                    <td class="px-4 py-3">{{ $notice->reason }}</td>
                                    <td class="px-4 py-3">
                                        @php
                                            $color = \App\Models\ParentAbsenceNotice::statusColor($notice->status);
                                        @endphp
                                        <span class="px-2 py-1 text-xs rounded-full
                                            {{ $color === 'yellow' ? 'bg-yellow-100 text-yellow-800' : '' }}
                                            {{ $color === 'blue' ? 'bg-blue-100 text-blue-800' : '' }}
                                            {{ $color === 'green' ? 'bg-green-100 text-green-800' : '' }}
                                            {{ $color === 'gray' ? 'bg-gray-100 text-gray-800' : '' }}">
                                            {{ \App\Models\ParentAbsenceNotice::statusLabel($notice->status) }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3">{{ optional($notice->created_at)->format('d M Y H:i') }}</td>
                                    <td class="px-4 py-3 text-right">
                                        <a href="{{ route('admin.absence-notices.show', $notice) }}"
                                            class="text-blue-600 hover:text-blue-800 font-semibold">
                                            View
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="px-4 py-8 text-center text-gray-500">
                                        No absence notices found.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="p-4">
                    {{ $notices->links() }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
