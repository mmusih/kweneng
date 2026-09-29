<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div><h2 class="text-2xl font-bold text-slate-900 dark:text-white">Prefects</h2><p class="text-sm text-slate-500 dark:text-brand-300">Student leadership appointments, duties, service periods, and certificates.</p></div>
            <div class="flex flex-wrap gap-2"><a href="{{ route(Auth::user()->role.'.prefects.certificates-pdf', request()->query()) }}" class="rounded-lg bg-amber-600 px-4 py-2 font-semibold text-white">Download certificates (one PDF)</a><a href="{{ route(Auth::user()->role.'.prefects.create') }}" class="rounded-lg bg-[#124E66] px-4 py-2 font-semibold text-white">Appoint prefect</a></div>
        </div>
    </x-slot>
    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        @if(session('warning'))<div class="mb-5 rounded-lg bg-amber-50 p-4 font-medium text-amber-800">{{ session('warning') }}</div>@endif
        @if(session('success'))<div class="mb-5 rounded-lg bg-emerald-50 p-4 text-emerald-800">{{ session('success') }}</div>@endif
        <form method="GET" class="mb-5 grid gap-3 rounded-xl bg-white p-4 shadow dark:bg-brand-800 md:grid-cols-4">
            <input name="search" value="{{ request('search') }}" placeholder="Student, title, admission no. or duty" class="rounded-lg border-slate-300 dark:bg-brand-700 md:col-span-2">
            <select name="academic_year_id" class="rounded-lg border-slate-300 dark:bg-brand-700"><option value="">All academic years</option>@foreach($academicYears as $year)<option value="{{ $year->id }}" @selected(request('academic_year_id')==$year->id)>{{ $year->year_name }}</option>@endforeach</select>
            <div class="flex gap-2"><select name="status" class="min-w-0 flex-1 rounded-lg border-slate-300 dark:bg-brand-700"><option value="">All statuses</option>@foreach($statuses as $value=>$label)<option value="{{ $value }}" @selected(request('status')===$value)>{{ $label }}</option>@endforeach</select><button class="rounded-lg bg-slate-800 px-4 font-semibold text-white">Filter</button></div>
        </form>
        <div class="overflow-hidden rounded-xl bg-white shadow dark:bg-brand-800">
            <table class="min-w-full divide-y divide-slate-200 dark:divide-brand-600">
                <thead class="bg-slate-50 dark:bg-brand-700"><tr>@foreach(['Student','Prefect role','Duties','Service period','Status',''] as $heading)<th class="px-4 py-3 text-left text-xs font-bold uppercase text-slate-500">{{ $heading }}</th>@endforeach</tr></thead>
                <tbody class="divide-y divide-slate-100 dark:divide-brand-700">
                    @forelse($prefects as $prefect)
                        <tr>
                            <td class="px-4 py-4"><strong class="block text-slate-900 dark:text-white">{{ $prefect->student?->user?->name }}</strong><small class="text-slate-500">{{ $prefect->student?->admission_no }} · {{ $prefect->student?->currentClass?->name ?? $prefect->class_name_snapshot }}</small></td>
                            <td class="px-4 py-4"><strong>{{ $prefect->title }}</strong><small class="block text-slate-500">{{ $prefect->academicYear?->year_name }}</small></td>
                            <td class="max-w-sm px-4 py-4 text-sm text-slate-600 dark:text-brand-200">{{ Str::limit($prefect->duties, 120) }}</td>
                            <td class="px-4 py-4 text-sm">{{ $prefect->appointed_on?->format('j M Y') }}<span class="block text-slate-500">to {{ $prefect->service_ends_on?->format('j M Y') ?? 'ongoing' }}</span></td>
                            <td class="px-4 py-4"><span class="rounded-full px-2.5 py-1 text-xs font-bold {{ $prefect->status === 'active' ? 'bg-emerald-100 text-emerald-800' : ($prefect->status === 'completed' ? 'bg-sky-100 text-sky-800' : 'bg-rose-100 text-rose-800') }}">{{ $statuses[$prefect->status] ?? ucfirst($prefect->status) }}</span></td>
                            <td class="px-4 py-4"><div class="flex flex-wrap justify-end gap-3"><a class="font-semibold text-amber-700" href="{{ route(Auth::user()->role.'.prefects.certificate', $prefect) }}">Certificate</a><a class="font-semibold text-sky-700" href="{{ route(Auth::user()->role.'.prefects.edit', $prefect) }}">Edit</a><form method="POST" action="{{ route(Auth::user()->role.'.prefects.destroy', $prefect) }}" onsubmit="return confirm('Delete this prefect appointment?')">@csrf @method('DELETE')<button class="font-semibold text-rose-700">Delete</button></form></div></td>
                        </tr>
                    @empty<tr><td colspan="6" class="px-4 py-12 text-center text-slate-500">No prefect appointments found.</td></tr>@endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $prefects->links() }}</div>
    </div>
</x-app-layout>
