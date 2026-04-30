<x-app-layout>
    <x-slot name="header">
        <div class="mt-16 p-6 bg-gradient-to-r from-emerald-600 to-teal-700 rounded-lg shadow-lg">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <h2 class="font-semibold text-2xl text-white leading-tight">
                        Book Catalog
                    </h2>
                    <p class="text-emerald-100 text-sm mt-1">
                        Manage book titles, copies, categories, and barcode-ready inventory.
                    </p>
                </div>

                <div class="flex flex-wrap gap-3">
                    <a href="{{ route('librarian.dashboard') }}"
                        class="inline-flex items-center px-4 py-2 bg-teal-900/30 text-white font-semibold rounded-md shadow-sm hover:bg-teal-900/40 border border-white/20">
                        Dashboard
                    </a>

                    <a href="{{ route('librarian.books.create') }}"
                        class="inline-flex items-center px-4 py-2 bg-white text-emerald-700 font-semibold rounded-md shadow-sm hover:bg-emerald-50">
                        Add Book
                    </a>

                    <a href="{{ route('librarian.books.import') }}"
    class="inline-flex items-center px-4 py-2 bg-teal-900/30 text-white font-semibold rounded-md shadow-sm hover:bg-teal-900/40 border border-white/20">
    Import CSV
</a>
                </div>
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
                <form method="GET" action="{{ route('librarian.books.index') }}"
                    class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="md:col-span-2">
                        <label for="search" class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                        <input type="text" id="search" name="search" value="{{ request('search') }}"
                            placeholder="Title, author, ISBN..."
                            class="w-full border-gray-300 rounded-md shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                    </div>

                    <div class="flex items-end justify-end">
                        <button type="submit"
                            class="inline-flex items-center px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-semibold rounded-md shadow-sm">
                            Search
                        </button>
                    </div>
                </form>
            </div>

            <div class="bg-white shadow-sm rounded-lg overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left">Title</th>
                                <th class="px-4 py-3 text-left">Author</th>
                                <th class="px-4 py-3 text-left">Category</th>
                                <th class="px-4 py-3 text-left">ISBN</th>
                                <th class="px-4 py-3 text-center">Copies</th>
                                <th class="px-4 py-3 text-center">Available</th>
                                <th class="px-4 py-3 text-left">Status</th>
                                <th class="px-4 py-3 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                            @forelse($books as $book)
                                <tr class="{{ $loop->even ? 'bg-gray-50/40' : '' }}">
                                    <td class="px-4 py-3">
                                        <div class="font-medium text-gray-900">{{ $book->title }}</div>
                                        @if ($book->publisher || $book->publication_year)
                                            <div class="text-xs text-gray-500">
                                                {{ $book->publisher ?? 'N/A' }}
                                                @if ($book->publication_year)
                                                    · {{ $book->publication_year }}
                                                @endif
                                            </div>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3">{{ $book->author ?? 'N/A' }}</td>
                                    <td class="px-4 py-3">{{ $book->category?->name ?? 'Uncategorized' }}</td>
                                    <td class="px-4 py-3">{{ $book->isbn ?? 'N/A' }}</td>
                                    <td class="px-4 py-3 text-center">{{ $book->copies->count() }}</td>
                                    <td class="px-4 py-3 text-center">
                                        {{ $book->copies->where('is_available', true)->count() }}</td>
                                    <td class="px-4 py-3">
                                        @if ($book->is_active)
                                            <span
                                                class="px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                Active
                                            </span>
                                        @else
                                            <span
                                                class="px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-200 text-gray-800">
                                                Inactive
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <a href="{{ route('librarian.books.edit', $book) }}"
                                            class="text-emerald-600 hover:text-emerald-800 font-medium">
                                            Open
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="px-4 py-6 text-center text-gray-500">
                                        No books found.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="p-4 border-t border-gray-200">
                    {{ $books->links() }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
