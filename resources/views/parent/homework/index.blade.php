<x-app-layout>
    <x-slot name="header">
        <div class="mt-16 p-6 bg-gradient-to-r from-cyan-600 to-blue-700 rounded-lg shadow-lg flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <h2 class="font-semibold text-2xl text-white leading-tight">Homework</h2>
                <p class="text-cyan-100 text-sm mt-1">Homework thread by child and subject</p>
            </div>
            <a href="{{ route('parent.dashboard') }}" class="text-white hover:text-cyan-100 text-sm font-medium">Back to Dashboard</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if ($unreadCountBeforeOpen > 0)
                <div class="rounded-xl border border-cyan-200 bg-cyan-50 p-4 text-cyan-900">
                    {{ $unreadCountBeforeOpen }} new homework item{{ $unreadCountBeforeOpen === 1 ? '' : 's' }} opened. Your dashboard counter has been updated.
                </div>
            @endif

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="bg-white rounded-xl border border-gray-100 p-5 shadow-sm">
                    <p class="text-xs uppercase tracking-wide text-gray-400 font-semibold">Linked Children</p>
                    <p class="mt-2 text-3xl font-bold text-gray-900">{{ $children->count() }}</p>
                </div>
                <div class="bg-white rounded-xl border border-gray-100 p-5 shadow-sm">
                    <p class="text-xs uppercase tracking-wide text-gray-400 font-semibold">Homework Records</p>
                    <p class="mt-2 text-3xl font-bold text-cyan-700">{{ $totalHomeworkCount }}</p>
                </div>
                <div class="bg-white rounded-xl border border-gray-100 p-5 shadow-sm">
                    <p class="text-xs uppercase tracking-wide text-gray-400 font-semibold">Newly Opened</p>
                    <p class="mt-2 text-3xl font-bold text-blue-700">{{ $unreadCountBeforeOpen }}</p>
                </div>
            </div>

            @forelse ($groupedHomework as $studentGroup)
                @php $student = $studentGroup['student']; @endphp
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                    <div class="p-5 border-b border-gray-100 bg-gray-50 flex flex-col md:flex-row md:items-center md:justify-between gap-2">
                        <div>
                            <h3 class="text-lg font-bold text-gray-900">{{ $student?->user?->name ?? 'Unknown Student' }}</h3>
                            <p class="text-sm text-gray-500">{{ $student?->currentClass?->name ?? 'No class assigned' }}</p>
                        </div>
                        @if ($student && ! $student->isProfileComplete())
                            <a href="{{ route('parent.children.profile.edit', $student) }}" class="inline-flex items-center px-3 py-2 rounded-lg bg-amber-100 text-amber-800 text-sm font-semibold hover:bg-amber-200">
                                Complete profile
                            </a>
                        @endif
                    </div>

                    <div class="divide-y divide-gray-100">
                        @foreach ($studentGroup['subjects'] as $subjectName => $items)
                            <section class="p-5">
                                <div class="mb-4 flex items-center justify-between">
                                    <h4 class="text-base font-semibold text-gray-900">{{ $subjectName }}</h4>
                                    <span class="text-xs font-semibold px-2 py-1 rounded-full bg-cyan-100 text-cyan-700">
                                        {{ $items->count() }} item{{ $items->count() === 1 ? '' : 's' }}
                                    </span>
                                </div>

                                <div class="space-y-4">
                                    @foreach ($items as $item)
                                        @php
                                            $homework = $item['homework'];
                                            $record = $item['record'];
                                            $statusLabel = \App\Models\HomeworkMark::statusLabel($record->submission_status);
                                            $statusClasses = [
                                                \App\Models\HomeworkMark::STATUS_SUBMITTED => 'bg-green-100 text-green-800',
                                                \App\Models\HomeworkMark::STATUS_LATE_SUBMISSION => 'bg-yellow-100 text-yellow-800',
                                                \App\Models\HomeworkMark::STATUS_COPIED => 'bg-red-100 text-red-800',
                                                \App\Models\HomeworkMark::STATUS_NOT_SUBMITTED => 'bg-gray-100 text-gray-800',
                                            ][$record->submission_status] ?? 'bg-gray-100 text-gray-800';
                                        @endphp

                                        <article class="rounded-xl border {{ $item['was_unread'] ? 'border-cyan-300 bg-cyan-50/40' : 'border-gray-200 bg-white' }} p-4">
                                            <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-4">
                                                <div class="min-w-0">
                                                    <div class="flex flex-wrap items-center gap-2">
                                                        @if ($item['was_unread'])
                                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-cyan-600 text-white">New</span>
                                                        @endif
                                                        <h5 class="text-base font-bold text-gray-900">{{ $homework->title }}</h5>
                                                    </div>

                                                    @if ($homework->description)
                                                        <p class="text-sm text-gray-600 mt-2 whitespace-pre-line">{{ $homework->description }}</p>
                                                    @endif

                                                    <div class="mt-3 flex flex-wrap items-center gap-2 text-xs text-gray-500">
                                                        <span>Teacher: {{ $item['teacher_name'] }}</span>
                                                        <span>•</span>
                                                        <span>Given: {{ $homework->assigned_date?->format('d M Y') ?? 'N/A' }}</span>
                                                        <span>•</span>
                                                        <span>Due: {{ $homework->due_date?->format('d M Y') ?? 'Not set' }}</span>
                                                    </div>
                                                </div>

                                                <div class="flex flex-col sm:flex-row lg:flex-col gap-2 lg:text-right shrink-0">
                                                    <span class="inline-flex items-center justify-center px-3 py-1 rounded-full text-xs font-semibold {{ $statusClasses }}">
                                                        {{ $statusLabel }}
                                                    </span>

                                                    @if ($homework->hasMarksConfigured())
                                                        <span class="inline-flex items-center justify-center px-3 py-1 rounded-full text-xs font-semibold bg-indigo-100 text-indigo-800">
                                                            {{ $record->marks_obtained !== null ? number_format((float) $record->marks_obtained, 2) : '-' }} / {{ number_format((float) $homework->total_marks, 2) }}
                                                        </span>
                                                    @endif

                                                    @if ($homework->hasAttachment())
                                                        <a href="{{ route('parent.homework.attachment', $homework) }}" class="inline-flex items-center justify-center px-3 py-1 rounded-full text-xs font-semibold bg-blue-100 text-blue-800 hover:bg-blue-200">
                                                            Open attachment
                                                        </a>
                                                    @elseif ($homework->attachmentWasPurged())
                                                        <span class="inline-flex items-center justify-center px-3 py-1 rounded-full text-xs font-semibold bg-gray-100 text-gray-600">
                                                            Attachment removed after term close
                                                        </span>
                                                    @endif
                                                </div>
                                            </div>

                                            @if ($record->remarks)
                                                <div class="mt-3 rounded-lg bg-gray-50 border border-gray-100 p-3 text-sm text-gray-600">
                                                    <span class="font-semibold text-gray-700">Teacher note:</span> {{ $record->remarks }}
                                                </div>
                                            @endif
                                        </article>
                                    @endforeach
                                </div>
                            </section>
                        @endforeach
                    </div>
                </div>
            @empty
                <div class="bg-white rounded-2xl border border-gray-100 p-10 text-center shadow-sm">
                    <h3 class="text-lg font-semibold text-gray-900">No homework yet</h3>
                    <p class="text-sm text-gray-500 mt-2">Homework sent by teachers will appear here by subject.</p>
                </div>
            @endforelse
        </div>
    </div>
</x-app-layout>
