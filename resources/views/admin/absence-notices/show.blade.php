<x-app-layout>
    <x-slot name="header">
        <div class="mt-16 flex flex-col md:flex-row md:items-center md:justify-between gap-3">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Absence Notice Details
                </h2>
                <p class="text-sm text-gray-500 mt-1">
                    {{ $absenceNotice->student->user->name ?? 'Unnamed student' }}
                </p>
            </div>

            <a href="{{ route('admin.absence-notices.index') }}"
                class="inline-flex items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">
                Back
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if (session('success'))
                <div class="bg-green-50 border border-green-200 text-green-800 rounded-lg p-4">
                    {{ session('success') }}
                </div>
            @endif

            <div class="bg-white shadow-sm rounded-lg p-6">
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <h3 class="text-lg font-bold text-gray-900">
                            {{ $absenceNotice->student->user->name ?? 'Unnamed student' }}
                        </h3>
                        <p class="text-sm text-gray-500">
                            {{ $absenceNotice->student->currentClass->name ?? 'No class' }}
                        </p>
                    </div>

                    @php
                        $color = \App\Models\ParentAbsenceNotice::statusColor($absenceNotice->status);
                    @endphp
                    <span class="px-3 py-1 text-sm rounded-full font-semibold
                        {{ $color === 'yellow' ? 'bg-yellow-100 text-yellow-800' : '' }}
                        {{ $color === 'blue' ? 'bg-blue-100 text-blue-800' : '' }}
                        {{ $color === 'green' ? 'bg-green-100 text-green-800' : '' }}
                        {{ $color === 'gray' ? 'bg-gray-100 text-gray-800' : '' }}">
                        {{ \App\Models\ParentAbsenceNotice::statusLabel($absenceNotice->status) }}
                    </span>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
                    <div>
                        <div class="text-xs uppercase text-gray-500">Parent</div>
                        <div class="font-semibold">{{ $absenceNotice->parent->user->name ?? 'Unknown parent' }}</div>
                        <div class="text-sm text-gray-500">{{ $absenceNotice->parent->user->email ?? '' }}</div>
                        <div class="text-sm text-gray-500">{{ $absenceNotice->parent->phone ?? '' }}</div>
                    </div>

                    <div>
                        <div class="text-xs uppercase text-gray-500">Submitted</div>
                        <div class="font-semibold">{{ optional($absenceNotice->created_at)->format('d M Y H:i') }}</div>
                    </div>

                    <div>
                        <div class="text-xs uppercase text-gray-500">Absence Date</div>
                        <div class="font-semibold">{{ optional($absenceNotice->absence_date)->format('d M Y') }}</div>
                    </div>

                    <div>
                        <div class="text-xs uppercase text-gray-500">Expected Return Date</div>
                        <div class="font-semibold">{{ optional($absenceNotice->expected_return_date)->format('d M Y') ?? '—' }}</div>
                    </div>

                    <div>
                        <div class="text-xs uppercase text-gray-500">Reason</div>
                        <div class="font-semibold">{{ $absenceNotice->reason }}</div>
                    </div>

                    <div>
                        <div class="text-xs uppercase text-gray-500">Seen / Resolved</div>
                        <div class="text-sm">
                            Seen: {{ optional($absenceNotice->seen_at)->format('d M Y H:i') ?? 'Not seen' }}
                        </div>
                        <div class="text-sm">
                            Resolved: {{ optional($absenceNotice->resolved_at)->format('d M Y H:i') ?? 'Not resolved' }}
                        </div>
                    </div>
                </div>

                <div class="mt-6">
                    <div class="text-xs uppercase text-gray-500">Parent Note</div>
                    <div class="mt-2 p-4 bg-gray-50 rounded-lg text-gray-800 whitespace-pre-line">
                        {{ $absenceNotice->note ?: 'No note provided.' }}
                    </div>
                </div>

                <div class="mt-6 flex flex-wrap gap-3">
                    @if ($absenceNotice->status === 'pending')
                        <form method="POST" action="{{ route('admin.absence-notices.seen', $absenceNotice) }}">
                            @csrf
                            @method('PATCH')
                            <button type="submit"
                                class="px-4 py-2 bg-blue-600 text-white rounded-md font-semibold text-sm hover:bg-blue-700">
                                Mark as Seen
                            </button>
                        </form>
                    @endif

                    @if ($absenceNotice->status !== 'resolved')
                        <form method="POST" action="{{ route('admin.absence-notices.resolved', $absenceNotice) }}">
                            @csrf
                            @method('PATCH')
                            <button type="submit"
                                class="px-4 py-2 bg-green-600 text-white rounded-md font-semibold text-sm hover:bg-green-700">
                                Mark as Resolved
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
