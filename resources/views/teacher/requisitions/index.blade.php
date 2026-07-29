<x-app-layout>
    <x-slot name="header"><div class="mt-16 p-5 kw-page-header rounded-lg flex justify-between items-center"><div><h2 class="text-2xl font-semibold text-white">My Requisitions</h2><p class="text-white/80 text-sm">Requests sent to inventory</p></div><a href="{{ route('teacher.requisitions.create') }}" class="px-4 py-2 bg-white text-indigo-700 rounded-md font-semibold">New Request</a></div></x-slot>
    <div class="py-8 kw-soft-section min-h-screen"><div class="max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-5">
        @if(session('success'))<div class="p-4 rounded bg-green-50 text-green-800 border">{{ session('success') }}</div>@endif
        <div class="bg-white kw-panel overflow-x-auto">
            <table class="min-w-full text-sm divide-y"><thead class="bg-gray-50"><tr><th class="px-4 py-3 text-left">Reference</th><th class="px-4 py-3 text-left">Title</th><th class="px-4 py-3 text-left">Status</th><th class="px-4 py-3 text-left">Needed By</th><th></th></tr></thead><tbody class="divide-y">
                @forelse($requisitions as $req)
                    <tr><td class="px-4 py-3"><a class="text-indigo-600 font-semibold" href="{{ route('teacher.requisitions.show', $req) }}">{{ $req->reference }}</a></td><td class="px-4 py-3">{{ $req->title }}<div class="text-xs text-gray-500">{{ $req->items->count() }} item(s)</div></td><td class="px-4 py-3">{{ $statuses[$req->status] ?? $req->status }}</td><td class="px-4 py-3">{{ $req->needed_by?->format('d M Y') ?? '-' }}</td><td class="px-4 py-3 text-right"><a class="text-indigo-600" href="{{ route('teacher.requisitions.show', $req) }}">View</a></td></tr>
                @empty<tr><td colspan="5" class="px-4 py-8 text-center text-gray-500">You have not submitted requisitions yet.</td></tr>@endforelse
            </tbody></table>
        </div>
        {{ $requisitions->links() }}
    </div></div>
</x-app-layout>
