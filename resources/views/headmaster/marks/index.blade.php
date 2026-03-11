<x-app-layout>
    <x-slot name="header">
        <div
            class="mt-16 p-4 bg-gradient-to-r from-yellow-500 to-orange-600 rounded-lg shadow-lg flex items-center justify-center">
            <h2 class="font-semibold text-2xl text-white leading-tight">
                Marks Monitor
            </h2>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <form method="GET" action="{{ route('headmaster.marks.index') }}"
                        class="grid grid-cols-1 md:grid-cols-5 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Academic Year</label>
                            <select name="academic_year_id" class="w-full border-gray-300 rounded-md shadow-sm">
                                <option value="">All</option>
                                @foreach ($academicYears as $year)
                                    <option value="{{ $year->id }}"
                                        {{ request('academic_year_id') == $year->id ? 'selected' : '' }}>
                                        {{ $year->year_name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Term</label>
                            <select name="term_id" class="w-full border-gray-300 rounded-md shadow-sm">
                                <option value="">All</option>
                                @foreach ($terms as $term)
                                    <option value="{{ $term->id }}"
                                        {{ request('term_id') == $term->id ? 'selected' : '' }}>
                                        {{ $term->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Class</label>
                            <select name="class_id" class="w-full border-gray-300 rounded-md shadow-sm">
                                <option value="">All</option>
                                @foreach ($classes as $class)
                                    <option value="{{ $class->id }}"
                                        {{ request('class_id') == $class->id ? 'selected' : '' }}>
                                        {{ $class->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Subject</label>
                            <select name="subject_id" class="w-full border-gray-300 rounded-md shadow-sm">
                                <option value="">All</option>
                                @foreach ($subjects as $subject)
                                    <option value="{{ $subject->id }}"
                                        {{ request('subject_id') == $subject->id ? 'selected' : '' }}>
                                        {{ $subject->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="flex items-end">
                            <button type="submit"
                                class="w-full inline-flex justify-center items-center px-4 py-2 bg-orange-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-orange-700">
                                Filter
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-2 text-left">Student</th>
                                <th class="px-4 py-2 text-left">Class</th>
                                <th class="px-4 py-2 text-left">Subject</th>
                                <th class="px-4 py-2 text-left">Teacher</th>
                                <th class="px-4 py-2 text-center">Midterm</th>
                                <th class="px-4 py-2 text-center">Endterm</th>
                                <th class="px-4 py-2 text-center">Grade</th>
                                <th class="px-4 py-2 text-left">Term</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @forelse($marks as $mark)
                                <tr>
                                    <td class="px-4 py-2">{{ $mark->student->user->name ?? 'N/A' }}</td>
                                    <td class="px-4 py-2">{{ $mark->class->name ?? 'N/A' }}</td>
                                    <td class="px-4 py-2">{{ $mark->subject->name ?? 'N/A' }}</td>
                                    <td class="px-4 py-2">{{ $mark->teacher->user->name ?? 'N/A' }}</td>
                                    <td class="px-4 py-2 text-center">{{ $mark->midterm_score ?? '-' }}</td>
                                    <td class="px-4 py-2 text-center">{{ $mark->endterm_score ?? '-' }}</td>
                                    <td class="px-4 py-2 text-center">{{ $mark->grade ?? '-' }}</td>
                                    <td class="px-4 py-2">{{ $mark->term->name ?? 'N/A' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="px-4 py-6 text-center text-gray-500">
                                        No marks found for the selected filters.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>

                    <div class="mt-4">
                        {{ $marks->links() }}
                    </div>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
