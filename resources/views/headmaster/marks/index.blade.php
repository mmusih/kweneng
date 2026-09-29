<x-app-layout>
    <x-slot name="header">
        <div class="mt-16 rounded-xl bg-slate-900 px-5 py-5 text-white shadow-sm sm:px-6">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-widest text-amber-300">Academic oversight</p>
                    <h2 class="mt-1 text-2xl font-semibold leading-tight">Marks Monitor</h2>
                    <p class="mt-1 text-sm text-slate-300">See what still needs attention, then open the learner list.</p>
                </div>
                <a href="{{ route('headmaster.dashboard') }}"
                    class="text-sm font-medium text-slate-200 hover:text-white">Back to dashboard</a>
            </div>
        </div>
    </x-slot>

    @php
        $progress = $summary['progress'];
        $progressWidth = $progress ?? 0;
        $hasExtraFilters = filled($classId) || filled($subjectId) || filled($teacherId) || filled($search);
        $statusClasses = fn ($status) => match ($status) {
            'complete' => 'bg-emerald-100 text-emerald-800',
            'good' => 'bg-blue-100 text-blue-800',
            'pending' => 'bg-amber-100 text-amber-800',
            'critical' => 'bg-red-100 text-red-800',
            default => 'bg-slate-100 text-slate-600',
        };
        $statusText = fn ($status) => match ($status) {
            'complete' => 'Complete',
            'good' => 'Nearly done',
            'pending' => 'In progress',
            'critical' => 'Needs attention',
            default => 'No learners',
        };
        $barClass = fn ($value) => match (true) {
            $value === null => 'bg-slate-300',
            $value >= 100 => 'bg-emerald-600',
            $value >= 80 => 'bg-blue-600',
            $value >= 50 => 'bg-amber-500',
            default => 'bg-red-500',
        };
    @endphp

    <div class="py-6">
        <div class="mx-auto max-w-7xl space-y-5 sm:px-6 lg:px-8">
            <section class="overflow-hidden bg-white shadow-sm sm:rounded-xl">
                <form method="GET" action="{{ route('headmaster.marks.index') }}" class="p-5 sm:p-6">
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
                        <div>
                            <label for="academic_year_id" class="mb-1 block text-sm font-medium text-slate-700">Academic year</label>
                            <select id="academic_year_id" name="academic_year_id" class="w-full rounded-lg border-slate-300 text-sm shadow-sm">
                                @foreach ($academicYears as $year)
                                    <option value="{{ $year->id }}" {{ (string) $academicYearId === (string) $year->id ? 'selected' : '' }}>
                                        {{ $year->year_name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="term_id" class="mb-1 block text-sm font-medium text-slate-700">Term</label>
                            <select id="term_id" name="term_id" class="w-full rounded-lg border-slate-300 text-sm shadow-sm">
                                @foreach ($terms as $term)
                                    <option value="{{ $term->id }}" {{ (string) $termId === (string) $term->id ? 'selected' : '' }}>
                                        {{ $term->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="assessment" class="mb-1 block text-sm font-medium text-slate-700">Assessment</label>
                            <select id="assessment" name="assessment" class="w-full rounded-lg border-slate-300 text-sm shadow-sm">
                                <option value="midterm" {{ $assessment === 'midterm' ? 'selected' : '' }}>Midterm</option>
                                <option value="endterm" {{ $assessment === 'endterm' ? 'selected' : '' }}>Endterm</option>
                            </select>
                        </div>
                        <div class="flex items-end">
                            <button type="submit" class="w-full rounded-lg bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-800">
                                Update view
                            </button>
                        </div>
                    </div>

                    <details class="mt-4 border-t border-slate-200 pt-4" {{ $hasExtraFilters ? 'open' : '' }}>
                        <summary class="cursor-pointer text-sm font-semibold text-slate-700">More filters{{ $hasExtraFilters ? ' · Active' : '' }}</summary>
                        <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
                            <div>
                                <label for="class_id" class="mb-1 block text-sm font-medium text-slate-700">Class</label>
                                <select id="class_id" name="class_id" class="w-full rounded-lg border-slate-300 text-sm shadow-sm">
                                    <option value="">All classes</option>
                                    @foreach ($classes as $class)
                                        <option value="{{ $class->id }}" {{ (string) $classId === (string) $class->id ? 'selected' : '' }}>{{ $class->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label for="subject_id" class="mb-1 block text-sm font-medium text-slate-700">Subject</label>
                                <select id="subject_id" name="subject_id" class="w-full rounded-lg border-slate-300 text-sm shadow-sm">
                                    <option value="">All subjects</option>
                                    @foreach ($subjects as $subject)
                                        <option value="{{ $subject->id }}" {{ (string) $subjectId === (string) $subject->id ? 'selected' : '' }}>{{ $subject->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label for="teacher_id" class="mb-1 block text-sm font-medium text-slate-700">Teacher</label>
                                <select id="teacher_id" name="teacher_id" class="w-full rounded-lg border-slate-300 text-sm shadow-sm">
                                    <option value="">All teachers</option>
                                    @foreach ($teachers as $teacher)
                                        <option value="{{ $teacher->id }}" {{ (string) $teacherId === (string) $teacher->id ? 'selected' : '' }}>{{ $teacher->user->name ?? 'N/A' }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label for="search" class="mb-1 block text-sm font-medium text-slate-700">Search</label>
                                <input id="search" type="search" name="search" value="{{ $search }}" placeholder="Teacher, class or subject"
                                    class="w-full rounded-lg border-slate-300 text-sm shadow-sm">
                            </div>
                        </div>
                        @if ($hasExtraFilters)
                            <div class="mt-4 text-right">
                                <a href="{{ route('headmaster.marks.index', ['academic_year_id' => $academicYearId, 'term_id' => $termId, 'assessment' => $assessment]) }}"
                                    class="text-sm font-medium text-slate-600 hover:text-slate-900">Clear extra filters</a>
                            </div>
                        @endif
                    </details>
                </form>
            </section>

            @if ($progress !== null)
                <section class="grid grid-cols-1 gap-4 lg:grid-cols-[1.7fr_1fr_1fr]">
                    <div class="rounded-xl border {{ $summary['missing'] === 0 ? 'border-emerald-200 bg-emerald-50' : 'border-slate-200 bg-white' }} p-5 shadow-sm sm:p-6">
                        <div class="flex items-end justify-between gap-4">
                            <div>
                                <p class="text-sm font-medium text-slate-600">{{ ucfirst($assessment) }} mark entry</p>
                                <p class="mt-1 text-4xl font-bold {{ $summary['missing'] === 0 ? 'text-emerald-700' : 'text-slate-900' }}">{{ $progress }}%</p>
                            </div>
                            <p class="text-right text-sm text-slate-600">{{ $summary['completed'] }} of {{ $summary['expected'] }} marks entered</p>
                        </div>
                        <div class="mt-4 h-3 w-full overflow-hidden rounded-full bg-slate-200" role="progressbar"
                            aria-label="Overall marks entry progress" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $progress }}">
                            <div class="h-full rounded-full {{ $barClass($progress) }}" style="width: {{ $progressWidth }}%"></div>
                        </div>
                        <p class="mt-3 text-sm {{ $summary['missing'] === 0 ? 'font-semibold text-emerald-800' : 'text-slate-600' }}">
                            {{ $summary['missing'] === 0 ? 'All assigned learners have marks entered.' : $summary['missing'].' marks still need to be entered.' }}
                        </p>
                    </div>
                    <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                        <p class="text-sm text-slate-500">Teachers complete</p>
                        <p class="mt-2 text-3xl font-bold text-slate-900">{{ $summary['complete_teachers'] }}<span class="text-lg font-medium text-slate-400"> / {{ $summary['teachers'] }}</span></p>
                        <p class="mt-2 text-sm text-slate-600">{{ $summary['incomplete_teachers'] }} still in progress</p>
                    </div>
                    <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                        <p class="text-sm text-slate-500">Class-subject groups</p>
                        <p class="mt-2 text-3xl font-bold text-slate-900">{{ $summary['complete_assignments'] }}<span class="text-lg font-medium text-slate-400"> / {{ $summary['assignments'] }}</span></p>
                        <p class="mt-2 text-sm text-slate-600">Fully entered</p>
                    </div>
                </section>
            @else
                <div class="rounded-xl border border-slate-200 bg-white p-6 text-center text-slate-600 shadow-sm">
                    No learner mark assignments were found for this selection.
                </div>
            @endif

            @if (count($teachersData))
                <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                    <div class="border-b border-slate-200 px-5 py-4 sm:px-6">
                        <h3 class="font-semibold text-slate-900">Teacher progress</h3>
                        <p class="mt-1 text-sm text-slate-500">Teachers needing attention appear first. Open a teacher only when you need the class-subject breakdown.</p>
                    </div>
                    <div class="divide-y divide-slate-200">
                        @foreach ($teachersData as $teacher)
                            <details class="group">
                                <summary class="grid cursor-pointer list-none grid-cols-[1fr_auto] items-center gap-4 px-5 py-4 hover:bg-slate-50 sm:grid-cols-[minmax(12rem,1fr)_8rem_9rem_auto] sm:px-6">
                                    <div>
                                        <p class="font-semibold text-slate-900">{{ $teacher['teacher'] }}</p>
                                        <p class="mt-0.5 text-xs text-slate-500">{{ count($teacher['subjects']) }} class-subject {{ Str::plural('group', count($teacher['subjects'])) }}</p>
                                    </div>
                                    <div class="hidden text-sm text-slate-600 sm:block">{{ $teacher['completed'] }}/{{ $teacher['expected'] }} entered</div>
                                    <div class="hidden sm:block">
                                        <div class="h-2 overflow-hidden rounded-full bg-slate-200">
                                            <div class="h-full {{ $barClass($teacher['progress']) }}" style="width: {{ $teacher['progress'] ?? 0 }}%"></div>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-3">
                                        <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $statusClasses($teacher['status']) }}">
                                            {{ $teacher['progress'] !== null ? $teacher['progress'].'%' : $statusText($teacher['status']) }}
                                        </span>
                                        <span class="text-slate-400 transition group-open:rotate-180" aria-hidden="true">⌄</span>
                                    </div>
                                </summary>
                                <div class="border-t border-slate-100 bg-slate-50 px-4 py-4 sm:px-6">
                                    <div class="overflow-x-auto rounded-lg border border-slate-200 bg-white">
                                        <table class="min-w-full divide-y divide-slate-200 text-sm">
                                            <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                                <tr>
                                                    <th class="px-4 py-3">Class & subject</th>
                                                    <th class="px-4 py-3">Entered</th>
                                                    <th class="px-4 py-3">Status</th>
                                                    <th class="px-4 py-3 text-right"><span class="sr-only">Action</span></th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-slate-100">
                                                @foreach ($teacher['subjects'] as $subject)
                                                    <tr>
                                                        <td class="px-4 py-3">
                                                            <p class="font-medium text-slate-900">{{ $subject['subject'] }}</p>
                                                            <p class="text-xs text-slate-500">{{ $subject['class'] }}</p>
                                                        </td>
                                                        <td class="whitespace-nowrap px-4 py-3 text-slate-700">{{ $subject['completed'] }}/{{ $subject['expected'] }}</td>
                                                        <td class="whitespace-nowrap px-4 py-3">
                                                            <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $statusClasses($subject['status']) }}">
                                                                {{ $statusText($subject['status']) }}{{ $subject['missing'] > 0 ? ' · '.$subject['missing'].' missing' : '' }}
                                                            </span>
                                                        </td>
                                                        <td class="whitespace-nowrap px-4 py-3 text-right">
                                                            @if ($subject['expected'] > 0)
                                                                <a href="{{ route('headmaster.marks.detail', [
                                                                    'class_id' => $subject['class_id'],
                                                                    'subject_id' => $subject['subject_id'],
                                                                    'teacher_id' => $subject['teacher_id'],
                                                                    'academic_year_id' => $subject['academic_year_id'],
                                                                    'term_id' => $subject['term_id'],
                                                                    'assessment' => $subject['assessment'],
                                                                ]) }}" class="font-semibold text-blue-700 hover:text-blue-900">View learners</a>
                                                            @endif
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </details>
                        @endforeach
                    </div>
                </section>
            @endif
        </div>
    </div>
</x-app-layout>
