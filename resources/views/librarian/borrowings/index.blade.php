<x-app-layout>
    <x-slot name="header">
        <div class="mt-16 p-6 bg-gradient-to-r from-emerald-600 to-teal-700 rounded-lg shadow-lg">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <h2 class="font-semibold text-2xl text-white leading-tight">
                        Library Borrowings
                    </h2>
                    <p class="text-emerald-100 text-sm mt-1">
                        Monitor issued, returned, overdue, and active borrowing records.
                    </p>
                </div>

                <a href="{{ route('librarian.borrowings.create') }}"
                    class="inline-flex items-center px-4 py-2 bg-white text-emerald-700 font-semibold rounded-md shadow-sm hover:bg-emerald-50">
                    Issue / Return
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if (session('success'))
                <div class="bg-green-50 border border-green-200 text-green-800 p-4 rounded-lg">
                    {{ session('success') }}
                </div>
            @endif

            <div class="bg-white shadow-sm rounded-lg p-6">
                <form method="GET" action="{{ route('librarian.borrowings.index') }}"
                    class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                        <select id="status" name="status"
                            class="w-full border-gray-300 rounded-md shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                            <option value="">All</option>
                            <option value="borrowed" {{ $status === 'borrowed' ? 'selected' : '' }}>Borrowed</option>
                            <option value="returned" {{ $status === 'returned' ? 'selected' : '' }}>Returned</option>
                            <option value="overdue" {{ $status === 'overdue' ? 'selected' : '' }}>Overdue</option>
                            <option value="lost" {{ $status === 'lost' ? 'selected' : '' }}>Lost</option>
                        </select>
                    </div>

                    <div class="md:col-span-2 flex items-end justify-end">
                        <button type="submit"
                            class="inline-flex items-center px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-semibold rounded-md shadow-sm">
                            Apply Filter
                        </button>
                    </div>
                </form>
            </div>

            <div class="bg-white shadow-sm rounded-lg overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left">Book</th>
                                <th class="px-4 py-3 text-left">Barcode</th>
                                <th class="px-4 py-3 text-left">Borrower</th>
                                <th class="px-4 py-3 text-left">Type</th>
                                <th class="px-4 py-3 text-left">Issued</th>
                                <th class="px-4 py-3 text-left">Due</th>
                                <th class="px-4 py-3 text-left">Returned</th>
                                <th class="px-4 py-3 text-left">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                            @forelse($borrowings as $borrowing)
                                <tr class="{{ $loop->even ? 'bg-gray-50/40' : '' }}">
                                    <td class="px-4 py-3">
                                        <div class="font-medium text-gray-900">
                                            {{ $borrowing->bookCopy->book->title ?? 'N/A' }}
                                        </div>
                                        <div class="text-xs text-gray-500">
                                            {{ $borrowing->bookCopy->book->author ?? 'N/A' }}
                                        </div>
                                    </td>

                                    <td class="px-4 py-3">
                                        {{ $borrowing->bookCopy->barcode ?? 'N/A' }}
                                    </td>

                                    <td class="px-4 py-3">
                                        {{ $borrowing->student?->user?->name ?? ($borrowing->teacher?->user?->name ?? 'N/A') }}
                                    </td>

                                    <td class="px-4 py-3">
                                        {{ $borrowing->student_id ? 'Student' : 'Teacher' }}
                                    </td>

                                    <td class="px-4 py-3">
                                        {{ optional($borrowing->issued_at)->format('d M Y') }}
                                    </td>

                                    <td class="px-4 py-3">
                                        {{ optional($borrowing->due_at)->format('d M Y') }}
                                    </td>

                                    <td class="px-4 py-3">
                                        {{ $borrowing->returned_at ? optional($borrowing->returned_at)->format('d M Y') : '—' }}
                                    </td>

                                    <td class="px-4 py-3">
                                        @php
                                            $badgeClass = match ($borrowing->status) {
                                                'returned' => 'bg-green-100 text-green-800',
                                                'overdue' => 'bg-red-100 text-red-800',
                                                'lost' => 'bg-gray-200 text-gray-800',
                                                default => 'bg-yellow-100 text-yellow-800',
                                            };
                                        @endphp

                                        <span
                                            class="px-2.5 py-0.5 rounded-full text-xs font-medium {{ $badgeClass }}">
                                            {{ ucfirst($borrowing->status) }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="px-4 py-6 text-center text-gray-500">
                                        No borrowing records found.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="p-4 border-t border-gray-200">
                    {{ $borrowings->links() }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
