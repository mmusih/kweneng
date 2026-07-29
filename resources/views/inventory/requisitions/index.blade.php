<x-app-layout>
    <x-slot name="header"><div class="mt-16 p-5 kw-page-header rounded-lg"><h2 class="text-2xl font-semibold text-white">Teacher Requisitions</h2></div></x-slot>
    <div class="py-8 kw-soft-section min-h-screen"><div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-5">
        @if(session('success'))<div class="p-4 rounded bg-green-50 text-green-800 border">{{ session('success') }}</div>@endif
        <div class="bg-white kw-panel p-5">
            <form method="GET" class="grid grid-cols-1 md:grid-cols-5 gap-3">
                <input name="search" value="{{ request('search') }}" placeholder="Search reference, teacher or item" class="md:col-span-2 rounded-md border-gray-300">
                <select name="status" class="rounded-md border-gray-300"><option value="">All statuses</option>@foreach($statuses as $v=>$l)<option value="{{ $v }}" @selected(request('status')===$v)>{{ $l }}</option>@endforeach</select>
                <select name="priority" class="rounded-md border-gray-300"><option value="">All priorities</option>@foreach($priorities as $v=>$l)<option value="{{ $v }}" @selected(request('priority')===$v)>{{ $l }}</option>@endforeach</select>
                <div class="flex gap-2"><button class="px-4 py-2 bg-indigo-600 text-white rounded-md">Filter</button><a href="{{ route('inventory.requisitions.csv', request()->query()) }}" class="px-4 py-2 bg-slate-700 text-white rounded-md">CSV</a></div>
            </form>
        </div>
        <div class="bg-white kw-panel overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm"><thead class="bg-gray-50"><tr><th class="px-4 py-3 text-left">Reference</th><th class="px-4 py-3 text-left">Teacher</th><th class="px-4 py-3 text-left">Title</th><th class="px-4 py-3 text-left">Priority</th><th class="px-4 py-3 text-left">Status</th><th class="px-4 py-3 text-left">Needed By</th><th></th></tr></thead>
            <tbody class="divide-y">
            @forelse($requisitions as $req)
                <tr class="{{ $req->status === 'submitted' ? 'bg-amber-50' : '' }}">
                    <td class="px-4 py-3 font-semibold"><a class="text-indigo-600" href="{{ route('inventory.requisitions.show', $req) }}">{{ $req->reference }}</a></td>
                    <td class="px-4 py-3">{{ $req->requester->name ?? 'Unknown' }}</td>
                    <td class="px-4 py-3">{{ $req->title }}<div class="text-xs text-gray-500">{{ $req->items->count() }} item(s)</div></td>
                    <td class="px-4 py-3">{{ $priorities[$req->priority] ?? $req->priority }}</td>
                    <td class="px-4 py-3">{{ $statuses[$req->status] ?? $req->status }}</td>
                    <td class="px-4 py-3">{{ $req->needed_by?->format('d M Y') ?? '-' }}</td>
                    <td class="px-4 py-3 text-right"><a class="text-indigo-600" href="{{ route('inventory.requisitions.show', $req) }}">Open</a></td>
                </tr>
            @empty<tr><td colspan="7" class="px-4 py-8 text-center text-gray-500">No requisitions found.</td></tr>@endforelse
            </tbody></table>
        </div>
        {{ $requisitions->links() }}
    </div></div>
</x-app-layout>
