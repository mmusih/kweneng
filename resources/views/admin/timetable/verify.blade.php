<x-app-layout>
    <x-slot name="header">
        <div class="mt-16 rounded-2xl bg-gradient-to-r from-[#212A31] via-[#124E66] to-[#2E3944] p-6 text-white shadow-md">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-widest text-[#D3D9D4]">Timetabling</p>
                    <h2 class="mt-1 text-2xl font-semibold">Verification — {{ $setting->name }}</h2>
                    <p class="mt-1 text-sm text-white/90">Hard failures block publishing; gaps and missing rooms remain review items.</p>
                </div>
                <a href="{{ route('admin.timetable.index', ['setting' => $setting->id]) }}" class="rounded-md bg-white/10 px-4 py-2 text-sm font-semibold ring-1 ring-white/20">Back to grid</a>
            </div>
        </div>
    </x-slot>

    <div class="space-y-6 px-4 pb-12 sm:px-6 lg:px-8">
        <section class="grid gap-3 sm:grid-cols-2 xl:grid-cols-6">
            @foreach ([
                'Hard failures' => $report['summary']['hard_failures'],
                'Unplaced lessons' => $report['summary']['unplaced_lessons'],
                'Conflicts' => $report['summary']['conflicts'],
                'Workload issues' => $report['summary']['workload_issues'],
                'Room issues' => $report['summary']['room_issues'],
                'Class gaps' => $report['summary']['class_gaps'],
            ] as $label => $count)
                <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-700 dark:bg-slate-800">
                    <div class="text-2xl font-bold {{ $label === 'Hard failures' && $count ? 'text-rose-600' : 'text-slate-900 dark:text-white' }}">{{ $count }}</div>
                    <div class="mt-1 text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $label }}</div>
                </div>
            @endforeach
        </section>

        <section class="rounded-xl border {{ $report['clean'] ? 'border-emerald-300 bg-emerald-50' : 'border-rose-300 bg-rose-50' }} p-4 text-sm">
            <strong>{{ $report['clean'] ? 'Ready to publish.' : 'Publishing is blocked.' }}</strong>
            {{ $report['clean'] ? 'No hard timetable failures were found.' : 'Resolve every hard failure below and run verification again.' }}
        </section>

        @php
            $sections = [
                ['Unplaced lessons', $report['unplaced'], fn ($r) => "{$r['subject']} — {$r['classes']} ({$r['cards']} cards / {$r['periods']} periods unplaced)"],
                ['Placement conflicts', $report['conflicts'], fn ($r) => "Day {$r['day']}, period {$r['period']}: {$r['message']}"],
                ['Room issues', $report['room_issues'], fn ($r) => ($r['hard'] ? 'Hard: ' : 'Review: ').$r['message']],
                ['Class gaps', $report['class_gaps'], fn ($r) => "{$r['class']}, Day {$r['day']}: periods ".implode(', ', $r['periods'])],
            ];
        @endphp
        @foreach ($sections as [$title, $rows, $describe])
            <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-800">
                <h3 class="text-base font-semibold text-slate-900 dark:text-white">{{ $title }}</h3>
                @if ($rows === [])
                    <p class="mt-2 text-sm text-slate-500">No issues found.</p>
                @else
                    <ul class="mt-3 space-y-2 text-sm text-slate-700 dark:text-slate-200">
                        @foreach ($rows as $row)<li class="rounded-md bg-slate-50 px-3 py-2 dark:bg-slate-900">{{ $describe($row) }}</li>@endforeach
                    </ul>
                @endif
            </section>
        @endforeach

        <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-800">
            <h3 class="text-base font-semibold text-slate-900 dark:text-white">Teacher workload</h3>
            <div class="mt-3 overflow-x-auto"><table class="min-w-full text-left text-sm">
                <thead><tr class="border-b"><th class="p-2">Teacher</th><th class="p-2">Demand</th><th class="p-2">Placed</th><th class="p-2">Unplaced</th><th class="p-2">Daily limit issues</th></tr></thead>
                <tbody>@foreach ($report['workloads'] as $row)<tr class="border-b border-slate-100">
                    <td class="p-2 font-medium">{{ $row['teacher'] }}</td><td class="p-2">{{ $row['demand_periods'] }}</td><td class="p-2">{{ $row['placed_periods'] }}</td><td class="p-2">{{ $row['unplaced_periods'] }}</td>
                    <td class="p-2">{{ collect($row['overloaded_days'])->map(fn ($d) => "Day {$d['day']} (week {$d['week']}, term {$d['term']}): {$d['periods']}/{$d['maximum']}")->implode('; ') ?: 'None' }}</td>
                </tr>@endforeach</tbody>
            </table></div>
        </section>

        <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-800">
            <h3 class="text-base font-semibold text-slate-900 dark:text-white">Constraint violations by weight</h3>
            <p class="mt-2 text-sm text-slate-600 dark:text-slate-300">
                @forelse ($report['violations_by_weight'] as $weight => $count)
                    Weight {{ $weight }}: {{ $count }} {{ Str::plural('violation', $count) }}{{ $loop->last ? '' : ' · ' }}
                @empty
                    No evaluated constraint violations.
                @endforelse
            </p>
            @foreach ($report['constraints'] as $constraint)
                <p class="mt-2 text-xs text-amber-700">{{ $constraint['kind'] }} (weight {{ $constraint['weight'] }}): {{ $constraint['message'] }}</p>
            @endforeach
        </section>

        <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-800">
            <h3 class="text-base font-semibold text-slate-900 dark:text-white">Printable schedules</h3>
            <div class="mt-3 grid gap-4 md:grid-cols-3">
                @foreach ([['Classes', 'class', $classes], ['Teachers', 'teacher', $teachers], ['Rooms', 'room', $rooms]] as [$label, $type, $items])
                    <div><h4 class="text-sm font-semibold">{{ $label }}</h4><div class="mt-2 flex flex-wrap gap-2">
                        @foreach ($items as $item)<a target="_blank" href="{{ route('admin.timetable.settings.print', [$setting, $type, $item->id]) }}" class="rounded-md bg-[#124E66] px-3 py-2 text-xs font-semibold text-white">{{ $type === 'teacher' ? $item->user?->name : $item->name }}</a>@endforeach
                    </div></div>
                @endforeach
            </div>
        </section>
    </div>
</x-app-layout>
