<x-app-layout>
<x-slot name="header"><h2 class="text-2xl font-semibold">Timetable</h2><p class="mt-1 text-sm text-slate-500">Teaching reports for {{ $summary['academic_year'] }}.</p></x-slot>
<div class="mx-auto max-w-7xl space-y-4 p-6">
<div class="flex flex-wrap gap-3"><a class="rounded-lg bg-[#124E66] px-4 py-3 font-semibold text-white" href="{{ route($routePrefix.'.timetable.teacher-loads') }}">Teacher loads</a><a class="rounded-lg bg-[#124E66] px-4 py-3 font-semibold text-white" href="{{ route($routePrefix.'.timetable.teaching-summary') }}">Teaching summary</a></div>
@forelse($summary['schedules'] as $schedule)<div class="rounded-xl border bg-white p-5 dark:bg-brand-800"><h3 class="font-bold">{{ $schedule['name'] }}</h3><p>{{ $schedule['label'] }} · {{ $schedule['cycle_length'] }}-day cycle · {{ $schedule['term_label'] }} · Revision {{ $schedule['revision'] }}</p></div>@empty<p>No published timetable exists for this academic year.</p>@endforelse
</div></x-app-layout>
