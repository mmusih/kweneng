<div class="flex flex-wrap items-center justify-between gap-3">
    <nav aria-label="Teaching reports" class="flex flex-wrap gap-2">
        <a href="{{ route($routePrefix.'.timetable.teacher-loads', $reportFilters) }}" @if($report === 'teacher-loads') aria-current="page" @endif class="rounded-lg border px-4 py-2 font-semibold {{ $report === 'teacher-loads' ? 'bg-[#124E66] text-white' : 'bg-white dark:bg-brand-800' }}">Teacher loads</a>
        <a href="{{ route($routePrefix.'.timetable.teaching-summary', $reportFilters) }}" @if($report === 'teaching-summary') aria-current="page" @endif class="rounded-lg border px-4 py-2 font-semibold {{ $report === 'teaching-summary' ? 'bg-[#124E66] text-white' : 'bg-white dark:bg-brand-800' }}">Teaching summary</a>
    </nav>
    <div class="flex gap-2">
        <a href="{{ route($routePrefix.'.timetable.'.$report.'.download', $reportFilters) }}" class="rounded-lg bg-[#124E66] px-4 py-2 text-sm font-bold text-white">Download A3 PDF</a>
        <a href="{{ route($routePrefix.'.timetable.teaching-summary.csv', $reportFilters) }}" class="rounded-lg border bg-white px-4 py-2 text-sm font-semibold dark:bg-brand-800">Export teaching CSV</a>
    </div>
</div>
<form method="GET" class="flex flex-wrap items-end gap-4 rounded-xl border bg-white p-4 shadow-sm dark:border-brand-700 dark:bg-brand-800">
    <div><label for="report-year" class="block text-sm font-semibold">Academic year</label><select id="report-year" name="academic_year_id" class="mt-1 rounded-md border-slate-300 text-sm dark:bg-brand-900"><option value="">Current year</option>@foreach($academicYears as $year)<option value="{{ $year->id }}" @selected($selectedYearId === $year->id)>{{ $year->year_name }}</option>@endforeach</select></div>
    <div><label for="report-source" class="block text-sm font-semibold">Timetable source</label><select id="report-source" name="source" class="mt-1 rounded-md border-slate-300 text-sm dark:bg-brand-900"><option value="published" @selected($source === 'published')>Current published timetable</option><option value="working" @selected($source === 'working')>Latest working timetable</option></select></div>
    <div><label for="report-teacher" class="block text-sm font-semibold">Teacher</label><select id="report-teacher" name="teacher_id" class="mt-1 rounded-md border-slate-300 text-sm dark:bg-brand-900"><option value="">All teachers</option>@foreach($teachers as $teacher)<option value="{{ $teacher['teacher_id'] }}" @selected($selectedTeacherId === $teacher['teacher_id'])>{{ $teacher['teacher_name'] }}</option>@endforeach</select></div>
    @if(isset($reportFilters['setting']))<input type="hidden" name="setting" value="{{ $reportFilters['setting'] }}">@endif
    <fieldset class="w-full"><legend class="text-sm font-semibold">Exclude forms</legend><div class="mt-2 flex flex-wrap gap-4">
        @foreach($availableForms as $form)<label class="flex items-center gap-2 text-sm"><input type="checkbox" name="exclude_forms[]" value="{{ $form }}" @checked(in_array((int) $form, $excludedForms, true))> Form {{ $form }}</label>@endforeach
    </div><p class="mt-2 text-xs text-slate-500">Tick forms no longer attending lessons, then view the report. Shared lessons remain for forms still attending.</p></fieldset>
    <button class="rounded-lg bg-[#124E66] px-4 py-2 text-sm font-semibold text-white">View report</button>
</form>
<div class="rounded-xl border border-sky-200 bg-sky-50 p-4 text-sm text-sky-950 dark:border-brand-600 dark:bg-brand-800 dark:text-brand-100">
    <p class="font-semibold">{{ $summary['academic_year'] ?? 'No academic year selected' }} · {{ $source === 'published' ? 'Published timetable' : 'Working timetable — may include unpublished changes' }}</p>
    <p class="mt-2 font-semibold">{{ $excludedForms ? 'Excluding forms: '.implode(', ', $excludedForms) : 'All forms included' }}</p>
    <p class="mt-1">A single period counts as 1; a double counts as 2. Options, splits and joint classes count once for each period the teacher occupies. Unplaced lessons are excluded. Day and afternoon loads are kept separate.</p>
    @foreach($summary['schedules'] as $schedule)<p class="mt-2">{{ $schedule['label'] }}: {{ $schedule['name'] }} · {{ $schedule['term_label'] }} · Revision {{ $schedule['revision'] }} · {{ $schedule['cycle_length'] }}-day cycle · {{ $schedule['published'] ? 'Published' : 'Unpublished' }}</p>@endforeach
    @if(!$summary['schedules'])<p class="mt-2 font-semibold">No {{ $source === 'published' ? 'active published' : 'working' }} timetable exists for this year. @if($source === 'published')Choose the working timetable to review scheduled draft periods.@endif</p>@endif
    <p class="mt-2 text-xs">Counts cover occupied positions in one timetable cycle. If subjects alternate in the same position, the teacher total counts that position once.</p>
</div>
<div class="grid gap-4 sm:grid-cols-3">
    @foreach(['Teachers shown' => count($summary['teachers']), 'Teachers with scheduled periods' => collect($summary['teachers'])->where('grand_scheduled', '>', 0)->count(), 'Scheduled teacher periods' => collect($summary['teachers'])->sum('grand_scheduled')] as $label => $value)
        <div class="rounded-xl border bg-white p-4 dark:border-brand-700 dark:bg-brand-800"><p class="text-sm text-slate-500 dark:text-brand-200">{{ $label }}</p><p class="mt-1 text-3xl font-bold">{{ $value }}</p></div>
    @endforeach
</div>
