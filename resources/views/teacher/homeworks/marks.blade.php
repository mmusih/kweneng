<x-app-layout>
    <x-slot name="header">
        <div
            class="mt-16 p-6 bg-gradient-to-r from-blue-500 to-indigo-600 rounded-lg shadow-lg flex items-center justify-between">
            <div>
                <h2 class="font-semibold text-2xl text-white leading-tight">
                    Homework Marks
                </h2>
                <p class="text-blue-100 text-sm mt-1">
                    {{ $homework->title }} - {{ $homework->class->name ?? 'N/A' }} -
                    {{ $homework->subject->name ?? 'N/A' }}
                </p>
            </div>

            <a href="{{ route('teacher.homeworks.index') }}"
                class="text-white hover:text-blue-100 text-sm font-medium flex items-center">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
                Back to Homework
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white overflow-hidden shadow-sm rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div class="border rounded-lg p-4 bg-gray-50">
                            <p class="text-sm text-gray-500">Title</p>
                            <p class="font-semibold text-gray-900 mt-1">{{ $homework->title }}</p>
                        </div>
                        <div class="border rounded-lg p-4 bg-gray-50">
                            <p class="text-sm text-gray-500">Total Marks</p>
                            <p class="font-semibold text-gray-900 mt-1">{{ number_format($homework->total_marks, 2) }}
                            </p>
                        </div>
                        <div class="border rounded-lg p-4 bg-gray-50">
                            <p class="text-sm text-gray-500">Assigned Date</p>
                            <p class="font-semibold text-gray-900 mt-1">{{ $homework->assigned_date?->format('Y-m-d') }}
                            </p>
                        </div>
                        <div class="border rounded-lg p-4 bg-gray-50">
                            <p class="text-sm text-gray-500">Due Date</p>
                            <p class="font-semibold text-gray-900 mt-1">
                                {{ $homework->due_date?->format('Y-m-d') ?? 'N/A' }}</p>
                        </div>
                    </div>
                </div>
            </div>

            @if (session('success'))
                <div class="rounded-lg bg-green-50 p-4 text-green-800 border border-green-200">
                    {{ session('success') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="rounded-lg bg-red-50 p-4 text-red-800 border border-red-200">
                    <ul class="list-disc pl-5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if ($term->isLocked())
                <div class="rounded-lg bg-yellow-50 p-4 text-yellow-800 border border-yellow-200">
                    This term is locked. Homework marks cannot be edited.
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm rounded-lg">
                <div class="p-6 text-gray-900">
                    <form method="POST" action="{{ route('teacher.homeworks.store-marks', $homework) }}">
                        @csrf

                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                                            Student</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                                            Admission No</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                                            Marks Obtained / {{ number_format($homework->total_marks, 2) }}
                                        </th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                                            Percentage</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                                            Grade</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                                            Remarks</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200">
                                    @foreach ($students as $studentData)
                                        @php
                                            $student = $studentData['student'];
                                            $existing = $existingMarks->get($student->id);
                                        @endphp
                                        <tr>
                                            <td class="px-4 py-3 text-sm text-gray-900">
                                                {{ $student->user->name ?? 'Unknown Student' }}
                                            </td>
                                            <td class="px-4 py-3 text-sm text-gray-900">
                                                {{ $student->admission_no }}
                                            </td>
                                            <td class="px-4 py-3 text-sm">
                                                <input type="number" step="0.01" min="0"
                                                    max="{{ $homework->total_marks }}"
                                                    name="marks[{{ $student->id }}][marks_obtained]"
                                                    value="{{ old('marks.' . $student->id . '.marks_obtained', $existing?->marks_obtained) }}"
                                                    class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                                    {{ $term->isLocked() ? 'disabled' : '' }}>
                                            </td>
                                            <td class="px-4 py-3 text-sm text-gray-900">
                                                {{ $existing?->percentage !== null ? number_format($existing->percentage, 2) . '%' : '-' }}
                                            </td>
                                            <td class="px-4 py-3 text-sm text-gray-900">
                                                {{ $existing?->grade ?? '-' }}
                                            </td>
                                            <td class="px-4 py-3 text-sm">
                                                <input type="text" name="marks[{{ $student->id }}][remarks]"
                                                    value="{{ old('marks.' . $student->id . '.remarks', $existing?->remarks) }}"
                                                    class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                                    {{ $term->isLocked() ? 'disabled' : '' }}>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        @if (!$term->isLocked())
                            <div class="flex justify-end mt-6">
                                <x-primary-button>
                                    {{ __('Save Homework Marks') }}
                                </x-primary-button>
                            </div>
                        @endif
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
