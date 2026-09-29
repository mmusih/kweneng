<section class="rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-brand-700 dark:bg-brand-800 space-y-5 p-6" aria-label="Academic progress">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <h3 class="text-xl font-bold">Academic progress</h3>
        <form method="GET" class="flex flex-wrap items-center gap-2">
            @foreach(request()->only(['search', 'page', 'class_id']) as $key => $value)
                @if(is_scalar($value))<input type="hidden" name="{{ $key }}" value="{{ $value }}">@endif
            @endforeach
            <label for="profile-term">Performance term</label>
            <select id="profile-term" name="term_id" class="rounded-lg border-slate-300 dark:border-brand-600 dark:bg-brand-900">
                @foreach($terms as $option)<option value="{{ $option->id }}" @selected($term?->id === $option->id)>{{ $option->academicYear?->year_name }} · {{ $option->name }}</option>@endforeach
            </select>
            <button class="rounded-lg bg-[#124E66] px-4 py-2 text-white">View term</button>
        </form>
    </div>
    <div class="grid gap-4 sm:grid-cols-2">
        @foreach(['midterm_score' => 'Midterm', 'endterm_score' => 'Endterm'] as $field => $label)
            <div class="rounded-xl border border-slate-200 p-4 dark:border-brand-600">
                <p>{{ $label }} average</p><strong class="text-2xl">{{ $averages[$field] !== null ? number_format($averages[$field], 1).'%' : 'No marks' }}</strong>
                <p class="text-sm">{{ $marks->whereNotNull($field)->count() }} subjects assessed</p>
                @if($averages[$field] !== null && $averages[$field] < 50)<p class="text-red-700 dark:text-red-300">Needs academic attention · below 50%</p>@endif
            </div>
        @endforeach
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm">
            <caption class="sr-only">Subject performance for the selected term</caption>
            <thead><tr><th class="p-2">Subject</th><th class="p-2">Midterm</th><th class="p-2">Endterm</th><th class="p-2">Change</th><th class="p-2">Teacher remarks</th></tr></thead>
            <tbody>
                @forelse($marks as $mark)
                    <tr class="border-t border-slate-200 dark:border-brand-600">
                        <th scope="row" class="p-2">{{ $mark->subject?->name }}</th>
                        @foreach(['midterm_score', 'endterm_score'] as $field)<td class="p-2 {{ $mark->$field !== null && $mark->$field < 50 ? 'font-bold text-red-700 dark:text-red-300' : '' }}">{{ $mark->$field !== null ? number_format($mark->$field, 1).'%' : 'Not entered' }}</td>@endforeach
                        <td class="p-2">@if($mark->midterm_score !== null && $mark->endterm_score !== null){{ $mark->endterm_score - $mark->midterm_score > 0 ? '+' : '' }}{{ number_format($mark->endterm_score - $mark->midterm_score, 1) }} points @else — @endif</td>
                        <td class="p-2">{{ $mark->remarks ?: 'No remarks' }}</td>
                    </tr>
                @empty<tr><td colspan="5" class="p-4">No marks recorded for this term.</td></tr>@endforelse
            </tbody>
        </table>
    </div>
    <p class="text-sm text-slate-500 dark:text-brand-300">Scores below 50% are highlighted for support. Missing marks are excluded from averages. Change compares midterm and endterm in the same subject.</p>
    <details>
        <summary class="cursor-pointer font-semibold">Results across terms</summary>
        <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @forelse($record['years'] as $year)
                @foreach($year['terms'] as $historyTerm)
                    <div class="rounded-lg border border-slate-200 p-4 dark:border-brand-600">
                        <p class="font-semibold">{{ $year['name'] }} · {{ $historyTerm['name'] }}</p>
                        <p class="text-sm">{{ $year['class'] ?: 'Class not recorded' }}</p>
                        <p>Average: {{ $historyTerm['average'] === null ? 'No marks' : number_format($historyTerm['average'], 1).'%' }}</p>
                        @if($historyTerm['average'] !== null)<meter class="mt-2 w-full" min="0" max="100" value="{{ $historyTerm['average'] }}" aria-label="Term average">{{ $historyTerm['average'] }}%</meter>@endif
                    </div>
                @endforeach
            @empty<p>No academic history recorded.</p>@endforelse
        </div>
        <p class="mt-3 text-sm">Historical averages use available midterm and endterm marks. Subject and assessment coverage can differ between terms.</p>
    </details>
</section>
<section class="rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-brand-700 dark:bg-brand-800 space-y-4 p-6">
    <h3 class="text-xl font-bold">Attendance and support · {{ $term?->name ?? 'No term' }}</h3>
    <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
        @foreach(\App\Models\Attendance::statusLabels() as $status => $label)<div class="rounded-lg border border-slate-200 p-3 dark:border-brand-600"><p>{{ $label }}</p><strong class="text-xl">{{ $attendance->get($status, 0) }}</strong></div>@endforeach
    </div>
    @if($attendance->isEmpty())<p>No attendance recorded for this term.</p>@endif
    <details><summary class="cursor-pointer font-semibold">Behaviour and support records ({{ $behaviour->count() }})</summary>
        @forelse($behaviour as $entry)<article class="mt-3 border-t border-slate-200 pt-3 dark:border-brand-600"><p class="font-semibold">{{ $entry->record_date?->format('d M Y') }} · {{ ucfirst($entry->category) }} · {{ ucfirst($entry->severity) }}</p><p class="whitespace-pre-line">{{ $entry->incident }}</p><p class="whitespace-pre-line">Action: {{ $entry->action_taken ?: 'Not recorded' }}</p>@if($entry->remarks)<p>{{ $entry->remarks }}</p>@endif</article>@empty<p class="mt-3">No behaviour records for this term.</p>@endforelse
    </details>
</section>

