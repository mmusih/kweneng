<x-app-layout>
    <x-slot name="header">
        <div class="kw-page-header mt-16 rounded-xl bg-slate-900 px-5 py-5 text-white shadow-sm sm:px-6">
            <p class="text-xs font-semibold uppercase tracking-widest text-amber-300">Academic oversight</p>
            <h2 class="mt-1 text-2xl font-semibold">Marks entry progress</h2>
            <p class="mt-1 text-sm text-slate-300">Monitor and edit every teacher's marks. The active term loads automatically, and other terms remain selectable.</p>
        </div>
    </x-slot>

    @php
        $progress = $summary['progress'];
        $hasExtraFilters = filled($classId) || filled($subjectId) || filled($teacherId) || filled($search);
        $statusClasses = fn ($status) => match ($status) {
            'complete' => 'bg-emerald-100 text-emerald-800', 'good' => 'bg-blue-100 text-blue-800',
            'pending' => 'bg-amber-100 text-amber-800', 'critical' => 'bg-red-100 text-red-800',
            default => 'bg-slate-100 text-slate-600',
        };
        $statusText = fn ($status) => match ($status) {
            'complete' => 'Complete', 'good' => 'Nearly done', 'pending' => 'In progress',
            'critical' => 'Needs attention', default => 'No learners assigned',
        };
        $barClass = fn ($value) => match (true) {
            $value === null => 'bg-slate-300', $value >= 100 => 'bg-emerald-600',
            $value >= 80 => 'bg-blue-600', $value >= 50 => 'bg-amber-500', default => 'bg-red-500',
        };
    @endphp

    <div class="py-6">
        <div class="mx-auto max-w-7xl space-y-5 sm:px-6 lg:px-8">
            @if (session('success'))
                <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-emerald-800">{{ session('success') }}</div>
            @endif

            <section class="overflow-hidden rounded-xl bg-white shadow-sm">
                <form method="GET" action="{{ route('admin.marks.index') }}" class="p-5 sm:p-6">
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
                        <label class="text-sm font-medium text-slate-700">Academic year
                            <select name="academic_year_id" class="mt-1 w-full rounded-lg border-slate-300 text-sm shadow-sm">
                                @foreach ($academicYears as $year)<option value="{{ $year->id }}" @selected((string) $selectedAcademicYearId === (string) $year->id)>{{ $year->year_name }}</option>@endforeach
                            </select>
                        </label>
                        <label class="text-sm font-medium text-slate-700">Term
                            <select name="term_id" class="mt-1 w-full rounded-lg border-slate-300 text-sm shadow-sm">
                                @foreach ($terms as $term)<option value="{{ $term->id }}" @selected((string) $selectedTermId === (string) $term->id)>{{ $term->name }}</option>@endforeach
                            </select>
                        </label>
                        <label class="text-sm font-medium text-slate-700">Assessment
                            <select name="assessment" class="mt-1 w-full rounded-lg border-slate-300 text-sm shadow-sm">
                                <option value="midterm" @selected($assessment === 'midterm')>Midterm</option>
                                <option value="endterm" @selected($assessment === 'endterm')>End-term</option>
                            </select>
                        </label>
                        <div class="flex items-end"><button class="w-full rounded-lg bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-800">Show progress</button></div>
                    </div>
                    <details class="mt-4 border-t border-slate-200 pt-4" {{ $hasExtraFilters ? 'open' : '' }}>
                        <summary class="cursor-pointer text-sm font-semibold text-slate-700">More filters{{ $hasExtraFilters ? ' · Active' : '' }}</summary>
                        <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
                            <label class="text-sm text-slate-700">Class<select name="class_id" class="mt-1 w-full rounded-lg border-slate-300 text-sm"><option value="">All classes</option>@foreach ($classes as $class)<option value="{{ $class->id }}" @selected((string) $classId === (string) $class->id)>{{ $class->name }}</option>@endforeach</select></label>
                            <label class="text-sm text-slate-700">Subject<select name="subject_id" class="mt-1 w-full rounded-lg border-slate-300 text-sm"><option value="">All subjects</option>@foreach ($subjects as $subject)<option value="{{ $subject->id }}" @selected((string) $subjectId === (string) $subject->id)>{{ $subject->name }}</option>@endforeach</select></label>
                            <label class="text-sm text-slate-700">Teacher<select name="teacher_id" class="mt-1 w-full rounded-lg border-slate-300 text-sm"><option value="">All teachers</option>@foreach ($teachers as $teacher)<option value="{{ $teacher->id }}" @selected((string) $teacherId === (string) $teacher->id)>{{ $teacher->user?->name ?? 'N/A' }}</option>@endforeach</select></label>
                            <label class="text-sm text-slate-700">Search<input type="search" name="search" value="{{ $search }}" placeholder="Teacher, class or subject" class="mt-1 w-full rounded-lg border-slate-300 text-sm"></label>
                        </div>
                    </details>
                </form>
            </section>

            @if ($progress !== null)
                <section class="grid grid-cols-1 gap-4 lg:grid-cols-[1.7fr_1fr_1fr]">
                    <div class="rounded-xl border {{ $summary['missing'] === 0 ? 'border-emerald-200 bg-emerald-50' : 'border-slate-200 bg-white' }} p-5 shadow-sm sm:p-6">
                        <div class="flex items-end justify-between gap-4">
                            <div><p class="text-sm font-medium text-slate-600">{{ $assessment === 'midterm' ? 'Midterm' : 'End-term' }} entry</p><p class="mt-1 text-4xl font-bold text-slate-900">{{ $progress }}%</p></div>
                            <p class="text-right text-sm text-slate-600">{{ $summary['completed'] }} of {{ $summary['expected'] }} entries complete</p>
                        </div>
                        <div class="mt-4 h-3 overflow-hidden rounded-full bg-slate-200" role="progressbar" aria-valuenow="{{ $progress }}" aria-valuemin="0" aria-valuemax="100"><div class="h-full rounded-full {{ $barClass($progress) }}" style="width: {{ $progress }}%"></div></div>
                        <p class="mt-3 text-sm {{ $summary['missing'] === 0 ? 'font-semibold text-emerald-800' : 'text-slate-600' }}">{{ $summary['missing'] === 0 ? 'All teachers have completed their assigned entry.' : $summary['missing'].' entries remain across the selected term.' }}</p>
                    </div>
                    <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm"><p class="text-sm text-slate-500">Teachers complete</p><p class="mt-2 text-3xl font-bold">{{ $summary['complete_teachers'] }}<span class="text-lg font-medium text-slate-400"> / {{ $summary['teachers'] }}</span></p><p class="mt-2 text-sm text-slate-600">{{ $summary['incomplete_teachers'] }} still working</p></div>
                    <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm"><p class="text-sm text-slate-500">Teaching groups complete</p><p class="mt-2 text-3xl font-bold">{{ $summary['complete_assignments'] }}<span class="text-lg font-medium text-slate-400"> / {{ $summary['assignments'] }}</span></p><p class="mt-2 text-sm text-slate-600">Class and subject assignments</p></div>
                </section>
            @else
                <div class="rounded-xl border border-slate-200 bg-white p-6 text-center text-slate-600 shadow-sm">No teacher assignments with active learners were found for this selection.</div>
            @endif

            @if (count($teachersData))
                <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                    <div class="border-b border-slate-200 px-5 py-4 sm:px-6"><h3 class="font-semibold text-slate-900">Teacher progress</h3><p class="mt-1 text-sm text-slate-500">Teachers needing attention appear first. Expand a teacher for the class and subject breakdown.</p></div>
                    <div class="divide-y divide-slate-200">
                        @foreach ($teachersData as $teacher)
                            <details class="group">
                                <summary class="grid cursor-pointer list-none grid-cols-[1fr_auto] items-center gap-4 px-5 py-4 hover:bg-slate-50 sm:grid-cols-[minmax(12rem,1fr)_8rem_9rem_auto] sm:px-6">
                                    <div><p class="font-semibold text-slate-900">{{ $teacher['teacher'] }}</p><p class="text-xs text-slate-500">{{ count($teacher['subjects']) }} teaching {{ Str::plural('group', count($teacher['subjects'])) }}</p></div>
                                    <div class="hidden text-sm text-slate-600 sm:block">{{ $teacher['completed'] }}/{{ $teacher['expected'] }} entered</div>
                                    <div class="hidden h-2 overflow-hidden rounded-full bg-slate-200 sm:block"><div class="h-full {{ $barClass($teacher['progress']) }}" style="width: {{ $teacher['progress'] ?? 0 }}%"></div></div>
                                    <div class="flex items-center gap-3"><span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $statusClasses($teacher['status']) }}">{{ $teacher['progress'] !== null ? $teacher['progress'].'%' : $statusText($teacher['status']) }}</span><span class="text-slate-400 transition group-open:rotate-180">⌄</span></div>
                                </summary>
                                <div class="border-t border-slate-100 bg-slate-50 px-4 py-4 sm:px-6">
                                    <div class="overflow-x-auto rounded-lg border border-slate-200 bg-white">
                                        <table class="min-w-full divide-y divide-slate-200 text-sm">
                                            <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500"><tr><th class="px-4 py-3">Class</th><th class="px-4 py-3">Subject</th><th class="px-4 py-3">Entered</th><th class="px-4 py-3">Status</th><th class="px-4 py-3 text-right">Action</th></tr></thead>
                                            <tbody class="divide-y divide-slate-100">
                                                @foreach ($teacher['subjects'] as $subject)
                                                    <tr><td class="px-4 py-3 text-slate-700">{{ $subject['class'] }}</td><td class="px-4 py-3 font-medium text-slate-900">{{ $subject['subject'] }}</td><td class="whitespace-nowrap px-4 py-3">{{ $subject['completed'] }}/{{ $subject['expected'] }}</td><td class="px-4 py-3"><span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $statusClasses($subject['status']) }}">{{ $statusText($subject['status']) }}{{ $subject['missing'] > 0 ? ' · '.$subject['missing'].' remaining' : '' }}</span></td><td class="px-4 py-3 text-right"><a href="{{ route('admin.marks.group.edit', ['academic_year_id' => $subject['academic_year_id'], 'term_id' => $subject['term_id'], 'class_id' => $subject['class_id'], 'subject_id' => $subject['subject_id'], 'teacher_id' => $subject['teacher_id']]) }}" class="inline-flex rounded-md bg-indigo-600 px-3 py-2 text-xs font-bold text-white hover:bg-indigo-700">Edit marks</a></td></tr>
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
