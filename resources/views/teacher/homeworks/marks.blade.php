<x-app-layout>
    <x-slot name="header">
        <div class="mt-16 p-6 bg-gradient-to-r from-cyan-600 to-blue-700 rounded-lg shadow-lg flex items-center justify-between">
            <div>
                <h2 class="font-semibold text-2xl text-white leading-tight">Homework Submission Register</h2>
                <p class="text-cyan-100 text-sm mt-1">{{ $homework->title }} - {{ $homework->class->name ?? 'N/A' }} - {{ $homework->subject->name ?? 'N/A' }}</p>
            </div>
            <a href="{{ route('teacher.homeworks.index') }}" class="text-white hover:text-cyan-100 text-sm font-medium">Back to Homework</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('success'))
                <div class="rounded-lg bg-green-50 p-4 text-green-800 border border-green-200">{{ session('success') }}</div>
            @endif

            @if ($errors->any())
                <div class="rounded-lg bg-red-50 p-4 text-red-800 border border-red-200">
                    <ul class="list-disc pl-5 space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if ($term->isLocked())
                <div class="rounded-lg bg-yellow-50 p-4 text-yellow-800 border border-yellow-200">This term is locked. Homework records cannot be edited.</div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm rounded-lg border border-gray-100">
                <div class="p-6 text-gray-900">
                    <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                        <div class="border rounded-lg p-4 bg-gray-50 md:col-span-2">
                            <p class="text-sm text-gray-500">Homework</p>
                            <p class="font-semibold text-gray-900 mt-1">{{ $homework->title }}</p>
                            @if ($homework->description)
                                <p class="text-sm text-gray-600 mt-2">{{ $homework->description }}</p>
                            @endif
                        </div>
                        <div class="border rounded-lg p-4 bg-gray-50">
                            <p class="text-sm text-gray-500">Class / Subject</p>
                            <p class="font-semibold text-gray-900 mt-1">{{ $homework->class->name ?? 'N/A' }}</p>
                            <p class="text-sm text-gray-600">{{ $homework->subject->name ?? 'N/A' }}</p>
                        </div>
                        <div class="border rounded-lg p-4 bg-gray-50">
                            <p class="text-sm text-gray-500">Assigned</p>
                            <p class="font-semibold text-gray-900 mt-1">{{ $homework->assigned_date?->format('Y-m-d') }}</p>
                            <p class="text-sm text-gray-600">Due: {{ $homework->due_date?->format('Y-m-d') ?? 'N/A' }}</p>
                        </div>
                        <div class="border rounded-lg p-4 bg-gray-50">
                            <p class="text-sm text-gray-500">Attachment</p>
                            @if ($homework->hasAttachment())
                                <a href="{{ route('teacher.homeworks.attachment', $homework) }}" class="font-semibold text-cyan-700 hover:text-cyan-900 mt-1 inline-block">View photo</a>
                            @else
                                <p class="font-semibold text-gray-900 mt-1">None</p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm rounded-lg border border-gray-100">
                <div class="p-6 text-gray-900">
                    <form method="POST" action="{{ route('teacher.homeworks.store-marks', $homework) }}">
                        @csrf

                        <div class="flex flex-col lg:flex-row lg:items-end lg:justify-between gap-4 mb-6">
                            <div class="max-w-xs">
                                <x-input-label for="total_marks" :value="__('Homework Out Of (Optional)')" />
                                <x-text-input id="total_marks" class="block mt-1 w-full" type="number" step="0.01" min="0.01" name="total_marks" :value="old('total_marks', $homework->hasMarksConfigured() ? $homework->total_marks : '')" :disabled="$term->isLocked()" />
                                <p class="text-xs text-gray-500 mt-1">Leave blank for submission tracking only.</p>
                            </div>

                            @if (!$term->isLocked())
                                <div class="flex flex-wrap gap-2">
                                    @foreach ($submissionStatuses as $statusValue => $statusLabel)
                                        <button type="button" data-bulk-submission="{{ $statusValue }}" class="px-3 py-2 rounded-md text-sm font-semibold border border-gray-300 bg-white hover:bg-gray-50">
                                            Mark all {{ $statusLabel }}
                                        </button>
                                    @endforeach
                                </div>
                            @endif
                        </div>

                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Student</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Admission</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Submission</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Mark</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Percentage</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Grade</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Remarks</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200">
                                    @foreach ($students as $studentData)
                                        @php
                                            $student = $studentData['student'];
                                            $existing = $existingMarks->get($student->id);
                                            $status = old('marks.' . $student->id . '.submission_status', $existing?->submission_status ?? \App\Models\HomeworkMark::STATUS_SUBMITTED);
                                        @endphp
                                        <tr>
                                            <td class="px-4 py-3 text-sm text-gray-900 min-w-[220px]">
                                                <div class="font-semibold">{{ $student->user->name ?? 'Unknown Student' }}</div>
                                            </td>
                                            <td class="px-4 py-3 text-sm text-gray-900 whitespace-nowrap">{{ $student->admission_no }}</td>
                                            <td class="px-4 py-3 text-sm min-w-[190px]">
                                                <select name="marks[{{ $student->id }}][submission_status]" data-submission-select class="block w-full rounded-md border-gray-300 shadow-sm focus:border-cyan-500 focus:ring-cyan-500" {{ $term->isLocked() ? 'disabled' : '' }}>
                                                    @foreach ($submissionStatuses as $statusValue => $statusLabel)
                                                        <option value="{{ $statusValue }}" {{ $status === $statusValue ? 'selected' : '' }}>{{ $statusLabel }}</option>
                                                    @endforeach
                                                </select>
                                            </td>
                                            <td class="px-4 py-3 text-sm min-w-[130px]">
                                                <input type="number" step="0.01" min="0" name="marks[{{ $student->id }}][marks_obtained]" value="{{ old('marks.' . $student->id . '.marks_obtained', $existing?->marks_obtained) }}" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-cyan-500 focus:ring-cyan-500" {{ $term->isLocked() ? 'disabled' : '' }}>
                                            </td>
                                            <td class="px-4 py-3 text-sm text-gray-900 whitespace-nowrap">{{ $existing?->percentage !== null ? number_format($existing->percentage, 2) . '%' : '-' }}</td>
                                            <td class="px-4 py-3 text-sm text-gray-900 whitespace-nowrap">{{ $existing?->grade ?? '-' }}</td>
                                            <td class="px-4 py-3 text-sm min-w-[220px]">
                                                <input type="text" name="marks[{{ $student->id }}][remarks]" value="{{ old('marks.' . $student->id . '.remarks', $existing?->remarks) }}" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-cyan-500 focus:ring-cyan-500" placeholder="Optional note" {{ $term->isLocked() ? 'disabled' : '' }}>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        @if (!$term->isLocked())
                            <div class="flex justify-end mt-6">
                                <x-primary-button>{{ __('Save Homework Records') }}</x-primary-button>
                            </div>
                        @endif
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('[data-bulk-submission]').forEach(function (button) {
                button.addEventListener('click', function () {
                    const status = button.dataset.bulkSubmission;
                    document.querySelectorAll('[data-submission-select]').forEach(function (select) {
                        select.value = status;
                    });
                });
            });
        });
    </script>
</x-app-layout>
