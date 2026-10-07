<x-app-layout>
    <x-slot name="header">
        <div class="mt-16 rounded-xl bg-slate-900 px-5 py-5 text-white shadow-sm sm:px-6">
            <p class="text-xs font-semibold uppercase tracking-widest text-amber-300">Headmaster overview</p>
            <div class="mt-1 flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <h2 class="text-2xl font-semibold leading-tight">What needs your attention</h2>
                    <p class="mt-1 text-sm text-slate-300">A concise view of reporting readiness and school priorities.</p>
                </div>
                <p class="text-sm text-slate-300">
                    {{ $activeAcademicYear?->year_name ?? 'No active year' }}
                    @if ($currentTerm) · {{ $currentTerm->name }} @endif
                </p>
            </div>
        </div>
    </x-slot>

    @php
        $marksProgress = $dashboard['averageMarksCompletion'];
        $marksComplete = $marksProgress !== null && $dashboard['marksMissing'] === 0;
    @endphp

    <div class="py-6">
        <div class="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
            <form method="GET" class="flex flex-wrap items-center gap-3 rounded-xl border bg-white p-5 dark:bg-brand-800">
                <a class="font-semibold text-blue-700" href="{{ route('headmaster.timetable.index') }}">Timetable</a>
                <label for="overview-term" class="font-semibold">Performance term</label>
                <select id="overview-term" name="term_id" class="rounded border-slate-300">
                    @foreach($terms as $term)<option value="{{ $term->id }}" @selected($currentTerm?->id === $term->id)>{{ $term->academicYear?->year_name }} · {{ $term->name }}</option>@endforeach
                </select>
                <button class="rounded bg-slate-900 px-4 py-2 text-white">View term</button>
                <a class="font-semibold text-blue-700" href="{{ route('headmaster.students.index', ['term_id' => $currentTerm?->id]) }}">Student profiles →</a>
            </form>
            @if (! $activeAcademicYear || ! $currentTerm)
                <div class="rounded-xl border border-amber-200 bg-amber-50 p-5 text-amber-900 shadow-sm">
                    <p class="font-semibold">Reporting context is incomplete</p>
                    <p class="mt-1 text-sm">An active academic year and term are needed before mark-entry progress can be calculated.</p>
                </div>
            @endif

            <section class="grid grid-cols-1 gap-4 lg:grid-cols-[1.5fr_1fr_1fr]">
                <a href="{{ route('headmaster.marks.index') }}"
                    class="rounded-xl border p-5 shadow-sm transition hover:shadow-md sm:p-6 {{ $marksComplete ? 'border-emerald-200 bg-emerald-50' : 'border-slate-200 bg-white' }}">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-sm font-semibold {{ $marksComplete ? 'text-emerald-800' : 'text-slate-600' }}">
                                {{ ucfirst($dashboard['marksAssessment']) }} marks
                            </p>
                            <p class="mt-2 text-4xl font-bold {{ $marksComplete ? 'text-emerald-700' : 'text-slate-900' }}">
                                {{ $marksProgress !== null ? number_format($marksProgress, 0).'%' : 'N/A' }}
                            </p>
                        </div>
                        <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $marksComplete ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' }}">
                            {{ $marksComplete ? 'Complete' : 'Review' }}
                        </span>
                    </div>
                    @if ($marksProgress !== null)
                        <div class="mt-4 h-3 overflow-hidden rounded-full bg-slate-200" role="progressbar"
                            aria-label="Marks completion" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $marksProgress }}">
                            <div class="h-full rounded-full {{ $marksComplete ? 'bg-emerald-600' : 'bg-amber-500' }}" style="width: {{ $marksProgress }}%"></div>
                        </div>
                        <p class="mt-3 text-sm text-slate-600">
                            {{ $dashboard['marksEntered'] }} of {{ $dashboard['marksExpected'] }} assigned learner marks entered
                            @if ($dashboard['marksMissing'] > 0) · {{ $dashboard['marksMissing'] }} missing @endif
                        </p>
                    @else
                        <p class="mt-4 text-sm text-slate-600">No assigned learner marks to monitor yet.</p>
                    @endif
                </a>

                <a href="{{ route('headmaster.comments.index') }}" class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm transition hover:shadow-md">
                    <p class="text-sm font-medium text-slate-500">Comments pending</p>
                    <p class="mt-2 text-3xl font-bold text-slate-900">{{ $dashboard['studentsWithoutComments'] }}</p>
                    <p class="mt-2 text-sm text-slate-600">{{ $dashboard['studentsWithComments'] }} completed</p>
                    <p class="mt-4 text-sm font-semibold text-blue-700">Open comments →</p>
                </a>

                <details open class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <summary class="cursor-pointer list-none">
                        <p class="text-sm font-medium text-slate-500">Students at risk</p>
                        <p class="mt-2 text-3xl font-bold {{ $dashboard['atRiskStudentsCount'] > 0 ? 'text-red-700' : 'text-emerald-700' }}">{{ $dashboard['atRiskStudentsCount'] }}</p>
                        <p class="mt-2 text-sm text-slate-600">Average below 50%</p>
                        <p class="mt-4 text-sm font-semibold text-blue-700">{{ $dashboard['atRiskStudentsCount'] > 0 ? 'Show learners' : 'No action needed' }} {{ $dashboard['atRiskStudentsCount'] > 0 ? '↓' : '' }}</p>
                    </summary>
                    @if ($dashboard['recentAtRiskStudents']->isNotEmpty())
                        <div class="mt-4 max-h-80 space-y-2 overflow-y-auto border-t border-slate-200 pt-4">
                            @foreach ($dashboard['recentAtRiskStudents'] as $student)
                                <div class="rounded-lg bg-red-50 px-3 py-2">
                                    <a class="text-sm font-semibold text-blue-700" href="{{ route('headmaster.students.show', ['student' => $student, 'term_id' => $currentTerm?->id]) }}">{{ $student->user->name ?? 'Unknown Student' }} →</a>
                                    <p class="text-xs text-slate-600">{{ $student->term_class_names }} · {{ $student->admission_no ?? 'N/A' }}</p>
                                    <p class="font-bold text-red-700">{{ number_format($student->average_score, 1) }}%</p>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </details>
            </section>

            <section class="overflow-x-auto rounded-xl border border-slate-200 bg-white p-5 shadow-sm dark:bg-brand-800">
                <h3 class="font-semibold">Class statistics · {{ $currentTerm?->name ?? 'No term selected' }}</h3>
                <p class="my-2 text-sm text-slate-500">Students with recorded scores only. Attention means average below 50%. Students without marks are not counted as low performing.</p>
                <table class="w-full text-left text-sm"><thead><tr>
                    @foreach(['Class', 'Assessed students', 'Average', 'Lowest', 'Highest', 'Need attention'] as $heading)<th class="p-3">{{ $heading }}</th>@endforeach
                </tr></thead><tbody>
                    @forelse($classStats as $row)<tr class="border-t"><td class="p-3 font-semibold">{{ $row['name'] }}</td><td class="p-3">{{ $row['assessed'] }}</td>
                        @foreach(['average', 'lowest', 'highest'] as $field)<td class="p-3">{{ $row[$field] !== null ? number_format($row[$field], 1).'%' : 'No marks' }}</td>@endforeach
                        <td class="p-3 font-bold {{ $row['attention'] ? 'text-red-700' : 'text-emerald-700' }}">{{ $row['attention'] }}</td>
                    </tr>@empty<tr><td colspan="6" class="p-3">No class performance available for this term.</td></tr>@endforelse
                </tbody></table>
                <p class="mt-3 text-sm text-slate-500">Each subject uses the mean of its recorded midterm and endterm scores; class statistics then average each assessed student's subjects.</p>
            </section>

            <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                <div class="mb-4">
                    <h3 class="font-semibold text-slate-900">Common tasks</h3>
                    <p class="mt-1 text-sm text-slate-500">Go straight to the work you need to do.</p>
                </div>
                <div class="grid grid-cols-2 gap-3 md:grid-cols-4">
                    <a href="{{ route('headmaster.marks.index') }}" class="rounded-lg border border-slate-200 px-4 py-3 text-sm font-semibold text-slate-800 hover:border-blue-300 hover:bg-blue-50">Marks monitor</a>
                    <a href="{{ route('headmaster.reports.index') }}" class="rounded-lg border border-slate-200 px-4 py-3 text-sm font-semibold text-slate-800 hover:border-blue-300 hover:bg-blue-50">Report cards</a>
                    <a href="{{ route('headmaster.exam-summaries.index') }}" class="rounded-lg border border-slate-200 px-4 py-3 text-sm font-semibold text-slate-800 hover:border-blue-300 hover:bg-blue-50">Exam summaries</a>
                    <a href="{{ route('headmaster.awards.index') }}" class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-semibold text-amber-900 hover:border-amber-400 hover:bg-amber-100">Awards &amp; certificates</a>
                    <a href="{{ route('headmaster.prefects.index') }}" class="rounded-lg border border-violet-200 bg-violet-50 px-4 py-3 text-sm font-semibold text-violet-900 hover:border-violet-400 hover:bg-violet-100">Prefects &amp; duties</a>
                    <a href="{{ route('headmaster.events.calendar') }}" class="rounded-lg border border-slate-200 px-4 py-3 text-sm font-semibold text-slate-800 hover:border-blue-300 hover:bg-blue-50">Calendar</a>
                    <a href="{{ route('teacher.hod.schemes.dashboard') }}" class="rounded-lg border border-slate-200 px-4 py-3 text-sm font-semibold text-slate-800 hover:border-blue-300 hover:bg-blue-50">Schemes Oversight</a>
                    <a href="{{ route('teacher.dashboard') }}" class="rounded-lg border border-slate-200 px-4 py-3 text-sm font-semibold text-slate-800 hover:border-blue-300 hover:bg-blue-50">Teaching tools</a>
                </div>
            </section>

            <section class="rounded-xl border border-amber-200 bg-gradient-to-br from-amber-50 to-white p-5 shadow-sm sm:p-6">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <div><p class="text-xs font-bold uppercase tracking-widest text-amber-700">Awards &amp; Recognition</p><h3 class="mt-1 text-xl font-bold text-slate-900">Student achievement</h3><p class="mt-1 text-sm text-slate-600">Approve drafts, publish parent badges, print A4 certificates, and export Excel lists.</p></div>
                    <div class="flex flex-wrap gap-2"><a href="{{ route('headmaster.awards.create') }}" class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-bold text-white">Create award</a><a href="{{ route('headmaster.awards.index') }}" class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-bold text-slate-800">Manage awards</a></div>
                </div>
                <div class="mt-5 grid grid-cols-1 gap-3 sm:grid-cols-3">
                    <div class="rounded-lg border border-amber-100 bg-white p-4"><p class="text-xs font-bold uppercase tracking-wide text-slate-500">Draft runs</p><p class="mt-1 text-3xl font-bold text-amber-700">{{ $awardOverview['drafts'] }}</p></div>
                    <div class="rounded-lg border border-emerald-100 bg-white p-4"><p class="text-xs font-bold uppercase tracking-wide text-slate-500">Published runs</p><p class="mt-1 text-3xl font-bold text-emerald-700">{{ $awardOverview['published'] }}</p></div>
                    <div class="rounded-lg border border-sky-100 bg-white p-4"><p class="text-xs font-bold uppercase tracking-wide text-slate-500">Recipients</p><p class="mt-1 text-3xl font-bold text-[#124E66]">{{ $awardOverview['recipients'] }}</p></div>
                </div>
                @if($awardOverview['recent']->isNotEmpty())
                    <div class="mt-5"><p class="mb-2 text-xs font-bold uppercase tracking-wide text-slate-500">Recent award runs</p><div class="grid gap-2 md:grid-cols-2">@foreach($awardOverview['recent'] as $awardRun)<a href="{{ route('headmaster.awards.show', $awardRun) }}" class="flex items-center justify-between rounded-lg border border-slate-200 bg-white px-4 py-3 hover:border-amber-400"><span><strong class="block text-sm text-slate-900">{{ $awardRun->title }}</strong><small class="text-slate-500">{{ $awardRun->academicYear?->year_name }}{{ $awardRun->term ? ' · '.$awardRun->term->name : '' }}</small></span><span class="text-right"><strong class="block text-sm text-slate-900">{{ $awardRun->awards_count }}</strong><small class="capitalize text-slate-500">{{ $awardRun->status }}</small></span></a>@endforeach</div></div>
                @endif
            </section>

            <section class="grid grid-cols-1 gap-4 lg:grid-cols-2">
                <details open class="rounded-xl border border-slate-200 bg-white shadow-sm">
                    <summary class="cursor-pointer list-none px-5 py-4 sm:px-6">
                        <div class="flex items-center justify-between gap-4">
                            <div>
                                <h3 class="font-semibold text-slate-900">Academic performance</h3>
                                <p class="mt-1 text-sm text-slate-500">Averages and strongest or weakest areas</p>
                            </div>
                            <span class="text-slate-400" aria-hidden="true">⌄</span>
                        </div>
                    </summary>
                    <div class="grid grid-cols-2 gap-3 border-t border-slate-200 p-5 sm:p-6">
                        @foreach ([
                            ['School average', $dashboard['schoolAverage'], ''],
                            ['Midterm average', $dashboard['midtermAverage'], ''],
                            ['Endterm average', $dashboard['endtermAverage'], ''],
                            ['Best class', $dashboard['bestClass']?->average_score, $dashboard['bestClass']?->name],
                            ['Weakest class', $dashboard['weakestClass']?->average_score, $dashboard['weakestClass']?->name],
                            ['Top subject', $dashboard['topSubject']?->average_score, $dashboard['topSubject']?->name],
                            ['Weakest subject', $dashboard['weakestSubject']?->average_score, $dashboard['weakestSubject']?->name],
                        ] as [$label, $value, $name])
                            <div class="rounded-lg bg-slate-50 p-4">
                                <p class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ $label }}</p>
                                @if ($name)<p class="mt-1 font-semibold text-slate-900">{{ $name }}</p>@endif
                                <p class="mt-1 text-xl font-bold text-slate-800">{{ $value !== null ? number_format($value, 1).'%' : 'N/A' }}</p>
                            </div>
                        @endforeach
                    </div>
                </details>

                <details class="rounded-xl border border-slate-200 bg-white shadow-sm">
                    <summary class="cursor-pointer list-none px-5 py-4 sm:px-6">
                        <div class="flex items-center justify-between gap-4">
                            <div>
                                <h3 class="font-semibold text-slate-900">School operations</h3>
                                <p class="mt-1 text-sm text-slate-500">Attendance, punctuality and behaviour</p>
                            </div>
                            <span class="text-slate-400" aria-hidden="true">⌄</span>
                        </div>
                    </summary>
                    <div class="grid grid-cols-2 gap-3 border-t border-slate-200 p-5 sm:p-6">
                        <div class="rounded-lg bg-slate-50 p-4"><p class="text-sm text-slate-500">Attendance</p><p class="mt-1 text-2xl font-bold text-slate-900">{{ $dashboard['attendanceRate'] !== null ? number_format($dashboard['attendanceRate'], 1).'%' : 'N/A' }}</p></div>
                        <div class="rounded-lg bg-slate-50 p-4"><p class="text-sm text-slate-500">On time</p><p class="mt-1 text-2xl font-bold text-slate-900">{{ $dashboard['punctualityOnTimeRate'] !== null ? number_format($dashboard['punctualityOnTimeRate'], 1).'%' : 'N/A' }}</p></div>
                        <div class="rounded-lg bg-slate-50 p-4"><p class="text-sm text-slate-500">Behaviour incidents</p><p class="mt-1 text-2xl font-bold text-slate-900">{{ $dashboard['behaviourIncidentCount'] }}</p></div>
                        <div class="rounded-lg bg-slate-50 p-4"><p class="text-sm text-slate-500">Major incidents</p><p class="mt-1 text-2xl font-bold text-red-700">{{ $dashboard['majorBehaviourCount'] }}</p></div>
                    </div>
                </details>
            </section>
        </div>
    </div>
</x-app-layout>
