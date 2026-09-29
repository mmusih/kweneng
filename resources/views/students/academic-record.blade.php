<x-app-layout>
    <x-slot name="header">
        <div class="mt-16 rounded-2xl bg-gradient-to-r from-[#212A31] to-[#124E66] p-6 text-white shadow-md">
            <div class="flex flex-wrap items-center justify-between gap-4"><div><p class="text-xs font-bold uppercase tracking-widest text-white/70">Student profile</p><h2 class="mt-1 text-2xl font-semibold">Academic Record</h2><p class="mt-1 text-white/80">{{ $record['student']['name'] }} · {{ $record['student']['admission_no'] }}</p></div><div class="flex gap-2"><a href="{{ $backRoute }}" class="rounded-lg bg-white/10 px-4 py-2 text-sm font-semibold">Back</a><a target="_blank" href="{{ $downloadRoute }}" class="rounded-lg bg-white px-4 py-2 text-sm font-bold text-[#124E66]">Download PDF</a></div></div>
        </div>
    </x-slot>
    <div class="mx-auto max-w-6xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">
        @forelse($record['years'] as $year)
            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-brand-700 dark:bg-brand-800">
                <div class="flex flex-wrap justify-between gap-2 bg-slate-50 px-5 py-4 dark:bg-brand-900"><div><h3 class="text-xl font-bold">{{ $year['name'] }}</h3><p class="text-sm text-slate-500">{{ $year['class'] ?: 'Class not recorded' }}</p></div>@if($year['status'])<span class="self-start rounded-full bg-sky-100 px-3 py-1 text-xs font-bold capitalize text-sky-700">{{ $year['status'] }}</span>@endif</div>
                <div class="space-y-6 p-5">@foreach($year['terms'] as $term)<div><div class="mb-3 flex justify-between"><h4 class="font-bold">{{ $term['name'] }}</h4><span class="font-bold text-[#124E66]">Average: {{ $term['average'] === null ? 'N/A' : number_format($term['average'], 1).'%' }}</span></div><div class="overflow-x-auto"><table class="min-w-full divide-y divide-slate-200 text-sm"><thead><tr class="bg-slate-50"><th class="px-3 py-2 text-left">Subject</th><th class="px-3 py-2 text-right">Midterm</th><th class="px-3 py-2 text-right">End term</th><th class="px-3 py-2 text-right">Average</th><th class="px-3 py-2 text-left">Grade</th></tr></thead><tbody class="divide-y divide-slate-100">@foreach($term['subjects'] as $subject)<tr><td class="px-3 py-2 font-medium">{{ $subject['subject'] }}</td><td class="px-3 py-2 text-right">{{ $subject['midterm_score'] ?? '—' }}</td><td class="px-3 py-2 text-right">{{ $subject['endterm_score'] ?? '—' }}</td><td class="px-3 py-2 text-right">{{ $subject['average'] ?? '—' }}</td><td class="px-3 py-2 font-bold">{{ $subject['grade'] ?? '—' }}</td></tr>@endforeach</tbody></table></div></div>@endforeach</div>
            </section>
        @empty<div class="rounded-2xl border bg-white p-10 text-center text-slate-500">No academic results have been recorded yet.</div>@endforelse
    </div>
</x-app-layout>
