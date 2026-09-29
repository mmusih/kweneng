<x-app-layout>
    <x-slot name="header"><div class="flex items-center justify-between"><div><h2 class="text-2xl font-bold text-slate-900 dark:text-white">Awards</h2><p class="text-sm text-slate-500 dark:text-brand-300">Generate, review, publish and print student awards.</p></div><a href="{{ route(Auth::user()->role.'.awards.create') }}" class="rounded-lg bg-[#124E66] px-4 py-2 font-semibold text-white">Create award</a></div></x-slot>
    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        @if(session('success'))<div class="mb-5 rounded-lg bg-emerald-50 p-4 text-emerald-800">{{ session('success') }}</div>@endif
        <div class="overflow-hidden rounded-xl bg-white shadow dark:bg-brand-800">
            <table class="min-w-full divide-y divide-slate-200 dark:divide-brand-600">
                <thead class="bg-slate-50 dark:bg-brand-700"><tr>@foreach(['Award','Period','Type','Recipients','Status',''] as $heading)<th class="px-4 py-3 text-left text-xs font-bold uppercase text-slate-500 dark:text-brand-200">{{ $heading }}</th>@endforeach</tr></thead>
                <tbody class="divide-y divide-slate-100 dark:divide-brand-700">
                    @forelse($runs as $run)
                        <tr><td class="px-4 py-4 font-semibold text-slate-900 dark:text-white">{{ $run->title }}</td><td class="px-4 py-4 text-sm">{{ $run->academicYear?->year_name }}{{ $run->term ? ' · '.$run->term->name : '' }}</td><td class="px-4 py-4 text-sm capitalize">{{ $run->type }}</td><td class="px-4 py-4 text-sm">{{ $run->awards_count }}</td><td class="px-4 py-4"><span class="rounded-full px-2 py-1 text-xs font-bold {{ $run->status === 'published' ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' }}">{{ ucfirst($run->status) }}</span></td><td class="px-4 py-4"><div class="flex items-center justify-end gap-3"><a class="font-semibold text-sky-700 dark:text-sky-300" href="{{ route(Auth::user()->role.'.awards.show', $run) }}">Open</a>@if($run->status === 'draft')<a class="font-semibold text-amber-700 dark:text-amber-300" href="{{ route(Auth::user()->role.'.awards.edit', $run) }}">Edit</a><form method="POST" action="{{ route(Auth::user()->role.'.awards.destroy', $run) }}" onsubmit="return confirm('Delete this draft list and all generated certificates? This cannot be undone.')">@csrf @method('DELETE')<button class="font-semibold text-rose-700 dark:text-rose-300">Delete</button></form>@endif</div></td></tr>
                    @empty<tr><td colspan="6" class="px-4 py-12 text-center text-slate-500">No award runs have been created.</td></tr>@endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $runs->links() }}</div>
    </div>
</x-app-layout>
