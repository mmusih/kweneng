<x-app-layout>
    <x-slot name="header">
        <div class="mt-16 p-4 bg-gradient-to-r from-yellow-500 to-orange-600 rounded-lg shadow-lg">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
                <div>
                    <h2 class="font-semibold text-2xl text-white leading-tight">
                        Marks Monitor
                    </h2>
                    <p class="text-orange-100 text-sm mt-1">
                        Teacher-by-teacher marks entry progress overview
                    </p>
                </div>
            </div>
        </div>
    </x-slot>

    @php
        $progressBarClass = function ($progress) {
            if ($progress >= 100) {
                return 'bg-green-600';
            }
            if ($progress >= 80) {
                return 'bg-emerald-500';
            }
            if ($progress >= 50) {
                return 'bg-amber-500';
            }
            return 'bg-red-500';
        };

        $badgeClass = function ($status) {
            return match ($status) {
                'complete' => 'bg-green-100 text-green-800',
                'good' => 'bg-emerald-100 text-emerald-800',
                'pending' => 'bg-amber-100 text-amber-800',
                default => 'bg-red-100 text-red-800',
            };
        };

        $badgeText = function ($status) {
            return match ($status) {
                'complete' => 'Complete',
                'good' => 'On Track',
                'pending' => 'Pending',
                default => 'Critical',
            };
        };
    @endphp

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <form method="GET" action="{{ route('headmaster.marks.index') }}"
                        class="grid grid-cols-1 md:grid-cols-3 xl:grid-cols-7 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Academic Year</label>
                            <select name="academic_year_id" class="w-full border-gray-300 rounded-md shadow-sm">
                                <option value="">All</option>
                                @foreach ($academicYears as $year)
                                    <option value="{{ $year->id }}"
                                        {{ (string) $academicYearId === (string) $year->id ? 'selected' : '' }}>
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
                                        {{ (string) $termId === (string) $term->id ? 'selected' : '' }}>
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
                                        {{ (string) $classId === (string) $class->id ? 'selected' : '' }}>
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
                                        {{ (string) $subjectId === (string) $subject->id ? 'selected' : '' }}>
                                        {{ $subject->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Teacher</label>
                            <select name="teacher_id" class="w-full border-gray-300 rounded-md shadow-sm">
                                <option value="">All</option>
                                @foreach ($teachers as $teacher)
                                    <option value="{{ $teacher->id }}"
                                        {{ (string) $teacherId === (string) $teacher->id ? 'selected' : '' }}>
                                        {{ $teacher->user->name ?? 'N/A' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Assessment</label>
                            <select name="assessment" class="w-full border-gray-300 rounded-md shadow-sm">
                                <option value="midterm" {{ $assessment === 'midterm' ? 'selected' : '' }}>Midterm
                                </option>
                                <option value="endterm" {{ $assessment === 'endterm' ? 'selected' : '' }}>Endterm
                                </option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                            <input type="text" name="search" value="{{ $search }}"
                                placeholder="Teacher, class, subject..."
                                class="w-full border-gray-300 rounded-md shadow-sm">
                        </div>

                        <div class="md:col-span-3 xl:col-span-7 flex justify-end">
                            <button type="submit"
                                class="inline-flex justify-center items-center px-4 py-2 bg-orange-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-orange-700">
                                Apply Filters
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-6 gap-4">
                <div class="bg-white p-4 rounded-xl shadow-sm border">
                    <p class="text-sm text-gray-500">Teachers</p>
                    <p class="text-2xl font-bold text-gray-900 mt-1">{{ $summary['teachers'] }}</p>
                </div>

                <div class="bg-white p-4 rounded-xl shadow-sm border">
                    <p class="text-sm text-gray-500">Assignments</p>
                    <p class="text-2xl font-bold text-gray-900 mt-1">{{ $summary['assignments'] }}</p>
                </div>

                <div class="bg-white p-4 rounded-xl shadow-sm border">
                    <p class="text-sm text-gray-500">Expected Entries</p>
                    <p class="text-2xl font-bold text-indigo-600 mt-1">{{ $summary['expected'] }}</p>
                </div>

                <div class="bg-white p-4 rounded-xl shadow-sm border">
                    <p class="text-sm text-gray-500">Completed</p>
                    <p class="text-2xl font-bold text-green-600 mt-1">{{ $summary['completed'] }}</p>
                </div>

                <div class="bg-white p-4 rounded-xl shadow-sm border">
                    <p class="text-sm text-gray-500">Missing</p>
                    <p class="text-2xl font-bold text-red-600 mt-1">{{ $summary['missing'] }}</p>
                </div>

                <div class="bg-white p-4 rounded-xl shadow-sm border">
                    <p class="text-sm text-gray-500">Overall Progress</p>
                    <p class="text-2xl font-bold text-orange-600 mt-1">{{ $summary['progress'] }}%</p>
                </div>
            </div>

            @if ($summary['critical_teachers'] > 0)
                <div class="rounded-lg border border-red-200 bg-red-50 p-4 text-red-800">
                    <span class="font-semibold">{{ $summary['critical_teachers'] }}</span> teacher(s) are below 50%
                    progress for the selected filters.
                </div>
            @endif

            @if (count($teachersData))
                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
                    @foreach ($teachersData as $teacher)
                        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
                            <div class="px-5 py-4 border-b bg-gray-50">
                                <div class="flex items-start justify-between gap-4">
                                    <div>
                                        <h3 class="text-lg font-bold text-gray-900">
                                            {{ $teacher['teacher'] }}
                                        </h3>
                                        <p class="text-sm text-gray-500 mt-1">
                                            {{ count($teacher['subjects']) }} subject/class assignment(s)
                                        </p>
                                    </div>

                                    <span
                                        class="px-3 py-1 text-xs font-semibold rounded-full {{ $badgeClass($teacher['status']) }}">
                                        {{ $badgeText($teacher['status']) }}
                                    </span>
                                </div>

                                <div class="mt-4">
                                    <div class="flex items-center justify-between text-sm mb-1">
                                        <span class="font-medium text-gray-700">Overall Progress</span>
                                        <span class="font-semibold text-gray-900">{{ $teacher['progress'] }}%</span>
                                    </div>
                                    <div class="w-full bg-gray-200 rounded-full h-3">
                                        <div class="h-3 rounded-full {{ $progressBarClass($teacher['progress']) }}"
                                            style="width: {{ $teacher['progress'] }}%"></div>
                                    </div>
                                    <div class="mt-2 flex justify-between text-xs text-gray-500">
                                        <span>Completed: {{ $teacher['completed'] }}/{{ $teacher['expected'] }}</span>
                                        <span
                                            class="{{ $teacher['missing'] > 0 ? 'text-red-600 font-semibold' : 'text-green-600 font-semibold' }}">
                                            Missing: {{ $teacher['missing'] }}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <div class="p-5 space-y-4">
                                @foreach ($teacher['subjects'] as $subject)
                                    <div class="rounded-xl border border-gray-200 p-4">
                                        <div class="flex items-start justify-between gap-3 mb-2">
                                            <div>
                                                <h4 class="text-sm font-semibold text-gray-900">
                                                    {{ $subject['subject'] }}
                                                </h4>
                                                <p class="text-xs text-gray-500">
                                                    {{ $subject['class'] }}
                                                </p>
                                            </div>

                                            <span
                                                class="px-2.5 py-1 text-[11px] font-semibold rounded-full {{ $badgeClass($subject['status']) }}">
                                                {{ $subject['progress'] }}%
                                            </span>
                                        </div>

                                        <div class="w-full bg-gray-200 rounded-full h-2.5 mb-2">
                                            <a href="{{ route('headmaster.marks.detail', [
                                                'class_id' => $subject['class_id'],
                                                'subject_id' => $subject['subject_id'],
                                                'teacher_id' => $subject['teacher_id'],
                                                'academic_year_id' => $subject['academic_year_id'],
                                                'term_id' => $subject['term_id'],
                                                'assessment' => $subject['assessment'],
                                            ]) }}"
                                                class="block h-2.5 rounded-full {{ $progressBarClass($subject['progress']) }}"
                                                style="width: {{ $subject['progress'] }}%"
                                                title="Click to view detail">
                                            </a>
                                        </div>

                                        <div class="flex items-center justify-between text-xs">
                                            <span class="text-gray-500">
                                                {{ $subject['completed'] }}/{{ $subject['expected'] }} entered
                                            </span>
                                            <span
                                                class="{{ $subject['missing'] > 0 ? 'text-red-600 font-semibold' : 'text-green-600 font-semibold' }}">
                                                {{ $subject['missing'] }} missing
                                            </span>
                                        </div>

                                        @if ($subject['missing'] > 0 && count($subject['missing_student_names']))
                                            <div class="mt-3">
                                                <p class="text-[11px] font-semibold text-red-700 mb-1">Missing marks:
                                                </p>
                                                <div class="flex flex-wrap gap-1">
                                                    @foreach (collect($subject['missing_student_names'])->take(6) as $missingName)
                                                        <span
                                                            class="px-2 py-1 rounded-full bg-red-50 text-red-700 text-[11px]">
                                                            {{ $missingName }}
                                                        </span>
                                                    @endforeach

                                                    @if (count($subject['missing_student_names']) > 6)
                                                        <span
                                                            class="px-2 py-1 rounded-full bg-gray-100 text-gray-600 text-[11px]">
                                                            +{{ count($subject['missing_student_names']) - 6 }} more
                                                        </span>
                                                    @endif
                                                </div>
                                            </div>
                                        @endif

                                        <div class="mt-3">
                                            <a href="{{ route('headmaster.marks.detail', [
                                                'class_id' => $subject['class_id'],
                                                'subject_id' => $subject['subject_id'],
                                                'teacher_id' => $subject['teacher_id'],
                                                'academic_year_id' => $subject['academic_year_id'],
                                                'term_id' => $subject['term_id'],
                                                'assessment' => $subject['assessment'],
                                            ]) }}"
                                                class="text-sm font-medium text-blue-600 hover:text-blue-800">
                                                View student list
                                            </a>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="bg-white rounded-xl shadow-sm border p-8 text-center text-gray-500">
                    No teacher assignments matched the selected filters.
                </div>
            @endif

        </div>
    </div>
</x-app-layout>
