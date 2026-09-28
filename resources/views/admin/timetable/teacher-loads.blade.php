<x-app-layout>
    <x-slot name="header">
        <div class="mt-16 rounded-2xl bg-gradient-to-r from-[#212A31] to-[#124E66] p-6 text-white shadow-md">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div><p class="text-xs font-semibold uppercase tracking-widest text-white/70">Timetabling</p><h2 class="mt-1 text-2xl font-semibold">Teacher load summary</h2><p class="mt-1 text-sm text-white/80">Periods by subject, with day and afternoon loads kept separate.</p></div>
                <a target="_blank" href="{{ route('admin.timetable.teacher-loads.download', ['academic_year_id' => $selectedYearId]) }}" class="rounded-lg bg-white px-4 py-2 text-sm font-bold text-[#124E66]">Download PDF</a>
            </div>
        </div>
    </x-slot>

    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <form method="GET" class="mb-5 rounded-xl border bg-white p-4 shadow-sm dark:border-brand-700 dark:bg-brand-800">
            <label class="text-sm font-semibold">Academic year
                <select name="academic_year_id" onchange="this.form.submit()" class="ml-2 rounded-md border-slate-300 text-sm dark:bg-brand-900">
                    @foreach($academicYears as $year)<option value="{{ $year->id }}" @selected($selectedYearId === $year->id)>{{ $year->year_name }}</option>@endforeach
                </select>
            </label>
        </form>

        <div class="overflow-x-auto rounded-xl border bg-white shadow-sm dark:border-brand-700 dark:bg-brand-800">
            <table class="min-w-full divide-y divide-slate-200 text-sm dark:divide-brand-700">
                <thead class="bg-slate-50 dark:bg-brand-900"><tr><th class="px-4 py-3 text-left">Teacher</th>@foreach($summary['schedules'] as $schedule)<th class="px-4 py-3 text-left">{{ $schedule['label'] }} load</th>@endforeach<th class="px-4 py-3 text-right">Total</th></tr></thead>
                <tbody class="divide-y divide-slate-100 dark:divide-brand-700">
                    @forelse($summary['teachers'] as $teacher)
                        <tr class="align-top"><td class="px-4 py-4 font-semibold">{{ $teacher['teacher_name'] }}</td>
                            @foreach($teacher['schedules'] as $load)
                                <td class="px-4 py-4"><div class="space-y-1">@forelse($load['subjects'] as $subject)<div class="flex min-w-56 justify-between gap-4"><span>{{ $subject['subject'] }}</span><strong>{{ number_format($subject['periods'], 1) }}</strong></div>@empty<span class="text-slate-400">No periods</span>@endforelse</div><div class="mt-2 border-t pt-2 font-bold">{{ $load['total'] }} periods</div></td>
                            @endforeach
                            <td class="px-4 py-4 text-right text-lg font-black text-[#124E66]">{{ $teacher['grand_total'] }}</td>
                        </tr>
                    @empty<tr><td colspan="10" class="p-8 text-center text-slate-500">No active teachers found.</td></tr>@endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
