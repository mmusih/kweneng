<x-app-layout>
    <x-slot name="header">
        <div class="mt-16 p-6 bg-white rounded-lg shadow-md border border-gray-200">
            <div class="flex items-center justify-between gap-4">
                <h2 class="font-bold text-2xl text-gray-800 leading-tight">
                    My Library Books
                </h2>

                <a href="{{ route('student.dashboard') }}"
                    class="text-indigo-600 hover:text-indigo-900 text-sm font-medium">
                    Back to Dashboard
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">

                    <div class="overflow-x-auto">
                        <table
                            class="min-w-full divide-y divide-gray-200 border border-gray-200 rounded-lg overflow-hidden text-sm">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left">Book</th>
                                    <th class="px-4 py-3 text-left">Barcode</th>
                                    <th class="px-4 py-3 text-left">Issued</th>
                                    <th class="px-4 py-3 text-left">Due</th>
                                    <th class="px-4 py-3 text-left">Returned</th>
                                    <th class="px-4 py-3 text-left">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 bg-white">
                                @forelse($borrowings as $borrowing)
                                    @php
                                        $isReturned = !is_null($borrowing->returned_at);
                                        $isOverdue =
                                            !$isReturned &&
                                            !empty($borrowing->due_at) &&
                                            \Illuminate\Support\Carbon::parse($borrowing->due_at)->isPast();
                                    @endphp
                                    <tr class="{{ $isOverdue ? 'bg-red-50' : ($loop->even ? 'bg-gray-50/40' : '') }}">
                                        <td class="px-4 py-3">
                                            <div class="font-medium text-gray-900">
                                                {{ $borrowing->bookCopy->book->title ?? 'N/A' }}
                                            </div>
                                            <div class="text-xs text-gray-500">
                                                {{ $borrowing->bookCopy->book->author ?? 'N/A' }}
                                            </div>
                                        </td>
                                        <td class="px-4 py-3">{{ $borrowing->bookCopy->barcode ?? 'N/A' }}</td>
                                        <td class="px-4 py-3">{{ optional($borrowing->issued_at)->format('d M Y') }}
                                        </td>
                                        <td class="px-4 py-3">{{ optional($borrowing->due_at)->format('d M Y') }}</td>
                                        <td class="px-4 py-3">
                                            {{ $borrowing->returned_at ? optional($borrowing->returned_at)->format('d M Y') : '—' }}
                                        </td>
                                        <td class="px-4 py-3">
                                            @if ($isReturned)
                                                <span
                                                    class="px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                    Returned
                                                </span>
                                            @elseif($isOverdue)
                                                <span
                                                    class="px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                                    Overdue
                                                </span>
                                            @else
                                                <span
                                                    class="px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                                    Borrowed
                                                </span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="px-4 py-6 text-center text-gray-500">
                                            No library records found.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4">
                        {{ $borrowings->links() }}
                    </div>

                </div>
            </div>
        </div>
    </div>
</x-app-layout>
