<x-app-layout>
    <x-slot name="header">
        <div class="mt-16 p-6 bg-gradient-to-r from-indigo-600 to-blue-700 rounded-lg shadow-lg">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="font-semibold text-2xl text-white leading-tight">
                        Report Cards
                    </h2>
                    <p class="text-blue-100 text-sm mt-1">
                        Generate and preview student term reports
                    </p>
                </div>

                <a href="{{ route('headmaster.dashboard') }}"
                    class="text-white hover:text-blue-100 text-sm font-medium flex items-center">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    Back to Dashboard
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if (!$activeAcademicYear)
                <div class="bg-yellow-50 border border-yellow-200 text-yellow-800 p-4 rounded-lg">
                    No active academic year found.
                </div>
            @else
                <div class="bg-white shadow-sm rounded-lg p-6">
                    <form method="GET" action="{{ route('headmaster.reports.index') }}">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div>
                                <label for="class_id" class="block text-sm font-medium text-gray-700">Class</label>
                                <select id="class_id" name="class_id"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    required>
                                    <option value="">Select class</option>
                                    @foreach ($classes as $class)
                                        <option value="{{ $class->id }}"
                                            {{ (string) $selectedClassId === (string) $class->id ? 'selected' : '' }}>
                                            {{ $class->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label for="term_id" class="block text-sm font-medium text-gray-700">Term</label>
                                <select id="term_id" name="term_id"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    required>
                                    <option value="">Select term</option>
                                    @foreach ($terms as $term)
                                        <option value="{{ $term->id }}"
                                            {{ (string) $selectedTermId === (string) $term->id ? 'selected' : '' }}>
                                            {{ $term->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="flex items-end">
                                <button type="submit"
                                    class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-2 px-4 rounded-md">
                                    Load Students
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

                @if ($selectedClassId && $selectedTermId)
                    <div class="bg-white shadow-sm rounded-lg p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-semibold text-gray-900">
                                Students
                            </h3>

                            @if ($students->count())
                                <a href="{{ route('headmaster.reports.bulk-pdf', ['class_id' => $selectedClassId, 'term_id' => $selectedTermId]) }}"
                                    class="inline-flex items-center px-4 py-2 bg-red-600 text-white text-sm font-semibold rounded-md hover:bg-red-700">
                                    Download Class PDF
                                </a>
                            @endif
                        </div>

                        @if ($students->count())
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                                                Admission No
                                            </th>
                                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                                                Student Name
                                            </th>
                                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                                                Class
                                            </th>
                                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                                                Mid Avg
                                            </th>
                                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                                                Mid Pos
                                            </th>
                                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                                                End Avg
                                            </th>
                                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                                                End Pos
                                            </th>
                                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                                                Action
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-200">
                                        @foreach ($students as $student)
                                            <tr>
                                                <td class="px-4 py-3 text-sm text-gray-900">
                                                    {{ $student->admission_no }}
                                                </td>
                                                <td class="px-4 py-3 text-sm text-gray-900">
                                                    {{ $student->user->name }}
                                                </td>
                                                <td class="px-4 py-3 text-sm text-gray-900">
                                                    {{ $student->currentClass->name ?? 'N/A' }}
                                                </td>
                                                <td class="px-4 py-3 text-sm text-gray-900">
                                                    {{ $student->midterm_average !== null ? number_format($student->midterm_average, 2) . '%' : 'N/A' }}
                                                </td>
                                                <td class="px-4 py-3 text-sm text-gray-900">
                                                    {{ $student->midterm_position ?? 'N/A' }}
                                                </td>
                                                <td class="px-4 py-3 text-sm text-gray-900">
                                                    {{ $student->endterm_average !== null ? number_format($student->endterm_average, 2) . '%' : 'N/A' }}
                                                </td>
                                                <td class="px-4 py-3 text-sm text-gray-900">
                                                    {{ $student->endterm_position ?? 'N/A' }}
                                                </td>
                                                <td class="px-4 py-3 text-sm">
                                                    <a href="{{ route('headmaster.reports.show', ['student' => $student->id, 'term_id' => $selectedTermId]) }}"
                                                        class="inline-flex items-center px-3 py-2 bg-indigo-600 text-white text-sm font-semibold rounded-md hover:bg-indigo-700">
                                                        View Report
                                                    </a>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <p class="text-gray-500">No students found for the selected class.</p>
                        @endif
                    </div>
                @endif
            @endif
        </div>
    </div>
</x-app-layout>
