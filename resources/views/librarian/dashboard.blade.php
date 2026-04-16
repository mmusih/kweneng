<x-app-layout>
    <x-slot name="header">
        <div class="mt-16 p-6 bg-gradient-to-r from-emerald-600 to-teal-700 rounded-lg shadow-lg">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <h2 class="font-semibold text-2xl text-white leading-tight">
                        Librarian Dashboard
                    </h2>
                    <p class="text-emerald-100 text-sm mt-1">
                        Library operations, catalog, issue and return management
                    </p>
                </div>

                <div class="flex flex-wrap gap-3">
                    <a href="{{ route('librarian.books.index') }}"
                        class="inline-flex items-center px-4 py-2 bg-white text-emerald-700 font-semibold rounded-md shadow-sm hover:bg-emerald-50">
                        Manage Books
                    </a>

                    <a href="{{ route('librarian.borrowings.create') }}"
                        class="inline-flex items-center px-4 py-2 bg-teal-900/30 text-white font-semibold rounded-md shadow-sm hover:bg-teal-900/40 border border-white/20">
                        Issue / Return
                    </a>
                </div>
            </div>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">

            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-5 gap-6">
                <div class="bg-white shadow-sm rounded-lg p-6">
                    <p class="text-sm text-gray-500">Titles</p>
                    <h3 class="text-3xl font-bold text-gray-900 mt-2">{{ $stats['books'] }}</h3>
                    <p class="text-sm mt-2 text-gray-500">Registered book titles</p>
                </div>

                <div class="bg-white shadow-sm rounded-lg p-6">
                    <p class="text-sm text-gray-500">Copies</p>
                    <h3 class="text-3xl font-bold text-indigo-600 mt-2">{{ $stats['copies'] }}</h3>
                    <p class="text-sm mt-2 text-gray-500">All physical book copies</p>
                </div>

                <div class="bg-white shadow-sm rounded-lg p-6">
                    <p class="text-sm text-gray-500">Available</p>
                    <h3 class="text-3xl font-bold text-green-600 mt-2">{{ $stats['availableCopies'] }}</h3>
                    <p class="text-sm mt-2 text-gray-500">Copies ready for issue</p>
                </div>

                <div class="bg-white shadow-sm rounded-lg p-6">
                    <p class="text-sm text-gray-500">Borrowed</p>
                    <h3 class="text-3xl font-bold text-amber-600 mt-2">{{ $stats['borrowedCopies'] }}</h3>
                    <p class="text-sm mt-2 text-gray-500">Currently out on loan</p>
                </div>

                <div class="bg-white shadow-sm rounded-lg p-6">
                    <p class="text-sm text-gray-500">Overdue</p>
                    <h3 class="text-3xl font-bold text-red-600 mt-2">{{ $stats['overdueBorrowings'] }}</h3>
                    <p class="text-sm mt-2 text-gray-500">Require follow-up</p>
                </div>
            </div>

            <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
                <a href="{{ route('librarian.books.create') }}"
                    class="bg-white shadow-sm rounded-lg p-6 hover:shadow-md transition border">
                    <h3 class="text-lg font-semibold text-gray-800">Add New Book</h3>
                    <p class="text-sm text-gray-500 mt-2">
                        Register a new title, then add physical copies with accession numbers and barcodes.
                    </p>
                </a>

                <a href="{{ route('librarian.borrowings.create') }}"
                    class="bg-white shadow-sm rounded-lg p-6 hover:shadow-md transition border">
                    <h3 class="text-lg font-semibold text-gray-800">Issue or Return</h3>
                    <p class="text-sm text-gray-500 mt-2">
                        Use barcode scan/manual entry to issue books to students or teachers and process returns.
                    </p>
                </a>

                <a href="{{ route('librarian.borrowings.index', ['status' => 'overdue']) }}"
                    class="bg-white shadow-sm rounded-lg p-6 hover:shadow-md transition border">
                    <h3 class="text-lg font-semibold text-gray-800">Overdue Follow-up</h3>
                    <p class="text-sm text-gray-500 mt-2">
                        Review overdue borrowings and follow up on outstanding library materials.
                    </p>
                </a>
            </div>

            <div class="bg-white shadow-sm rounded-lg overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-800">Recent Borrowing Activity</h3>
                </div>

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
                                <th class="px-4 py-3 text-left">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                            @forelse($recentBorrowings as $borrowing)
                                <tr class="{{ $loop->even ? 'bg-gray-50/40' : '' }}">
                                    <td class="px-4 py-3">
                                        <div class="font-medium text-gray-900">
                                            {{ $borrowing->bookCopy->book->title ?? 'N/A' }}
                                        </div>
                                        <div class="text-xs text-gray-500">
                                            {{ $borrowing->bookCopy->book->author ?? 'N/A' }}
                                        </div>
                                    </td>
                                    <td class="px-4 py-3">{{ $borrowing->bookCopy->barcode ?? 'N/A' }}</td>
                                    <td class="px-4 py-3">
                                        {{ $borrowing->student?->user?->name ?? ($borrowing->teacher?->user?->name ?? 'N/A') }}
                                    </td>
                                    <td class="px-4 py-3">{{ $borrowing->student_id ? 'Student' : 'Teacher' }}</td>
                                    <td class="px-4 py-3">{{ optional($borrowing->issued_at)->format('d M Y') }}</td>
                                    <td class="px-4 py-3">{{ optional($borrowing->due_at)->format('d M Y') }}</td>
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
                                    <td colspan="7" class="px-4 py-6 text-center text-gray-500">
                                        No borrowing activity yet.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
