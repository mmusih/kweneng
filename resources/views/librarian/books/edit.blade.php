<x-app-layout>
    <x-slot name="header">
        <div class="mt-16 p-6 bg-gradient-to-r from-emerald-600 to-teal-700 rounded-lg shadow-lg">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <h2 class="font-semibold text-2xl text-white leading-tight">
                        Edit Book
                    </h2>
                    <p class="text-emerald-100 text-sm mt-1">
                        Update title metadata and manage physical copies.
                    </p>
                </div>

                <a href="{{ route('librarian.books.index') }}"
                    class="inline-flex items-center px-4 py-2 bg-white text-emerald-700 font-semibold rounded-md shadow-sm hover:bg-emerald-50">
                    Back to Catalog
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

            @if ($errors->any())
                <div class="bg-red-50 border border-red-200 text-red-800 p-4 rounded-lg">
                    <div class="font-semibold mb-2">Please fix the following:</div>
                    <ul class="list-disc pl-5 space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">

                <div class="bg-white shadow-sm rounded-lg p-6">
                    <div class="mb-6 border-b border-gray-200 pb-3">
                        <h3 class="text-xl font-semibold text-gray-800">Book Details</h3>
                    </div>

                    <form method="POST" action="{{ route('librarian.books.update', $book) }}" class="space-y-5">
                        @csrf
                        @method('PUT')

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                            <div class="md:col-span-2">
                                <label for="title" class="block text-sm font-medium text-gray-700 mb-1">Title</label>
                                <input type="text" id="title" name="title"
                                    value="{{ old('title', $book->title) }}"
                                    class="w-full border-gray-300 rounded-md shadow-sm focus:border-emerald-500 focus:ring-emerald-500"
                                    required>
                            </div>

                            <div>
                                <label for="author"
                                    class="block text-sm font-medium text-gray-700 mb-1">Author</label>
                                <input type="text" id="author" name="author"
                                    value="{{ old('author', $book->author) }}"
                                    class="w-full border-gray-300 rounded-md shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                            </div>

                            <div>
                                <label for="book_category_id"
                                    class="block text-sm font-medium text-gray-700 mb-1">Category</label>
                                <select id="book_category_id" name="book_category_id"
                                    class="w-full border-gray-300 rounded-md shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                                    <option value="">Select category</option>
                                    @foreach ($categories as $category)
                                        <option value="{{ $category->id }}"
                                            {{ (string) old('book_category_id', $book->book_category_id) === (string) $category->id ? 'selected' : '' }}>
                                            {{ $category->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label for="isbn" class="block text-sm font-medium text-gray-700 mb-1">ISBN</label>
                                <input type="text" id="isbn" name="isbn"
                                    value="{{ old('isbn', $book->isbn) }}"
                                    class="w-full border-gray-300 rounded-md shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                            </div>

                            <div>
                                <label for="publisher"
                                    class="block text-sm font-medium text-gray-700 mb-1">Publisher</label>
                                <input type="text" id="publisher" name="publisher"
                                    value="{{ old('publisher', $book->publisher) }}"
                                    class="w-full border-gray-300 rounded-md shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                            </div>

                            <div>
                                <label for="publication_year"
                                    class="block text-sm font-medium text-gray-700 mb-1">Publication Year</label>
                                <input type="number" id="publication_year" name="publication_year"
                                    value="{{ old('publication_year', $book->publication_year) }}"
                                    class="w-full border-gray-300 rounded-md shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                            </div>

                            <div class="md:col-span-2">
                                <label for="thumbnail_url"
                                    class="block text-sm font-medium text-gray-700 mb-1">Thumbnail URL</label>
                                <input type="url" id="thumbnail_url" name="thumbnail_url"
                                    value="{{ old('thumbnail_url', $book->thumbnail_url) }}"
                                    class="w-full border-gray-300 rounded-md shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                            </div>

                            @if ($book->thumbnail_url)
                                <div class="md:col-span-2">
                                    <img src="{{ $book->thumbnail_url }}" alt="{{ $book->title }}"
                                        class="h-40 rounded-md border border-gray-200 bg-white">
                                </div>
                            @endif

                            <div class="md:col-span-2">
                                <label for="description"
                                    class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                                <textarea id="description" name="description" rows="4"
                                    class="w-full border-gray-300 rounded-md shadow-sm focus:border-emerald-500 focus:ring-emerald-500">{{ old('description', $book->description) }}</textarea>
                            </div>

                            <div class="md:col-span-2">
                                <label class="inline-flex items-center">
                                    <input type="checkbox" name="is_active" value="1"
                                        class="rounded border-gray-300 text-emerald-600 shadow-sm focus:ring-emerald-500"
                                        {{ old('is_active', $book->is_active) ? 'checked' : '' }}>
                                    <span class="ml-2 text-sm text-gray-700">Book is active in catalog</span>
                                </label>
                            </div>
                        </div>

                        <div class="pt-2">
                            <button type="submit"
                                class="inline-flex items-center px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-semibold rounded-md shadow-sm">
                                Update Book
                            </button>
                        </div>
                    </form>
                </div>

                <div class="bg-white shadow-sm rounded-lg p-6">
                    <div class="mb-6 border-b border-gray-200 pb-3">
                        <h3 class="text-xl font-semibold text-gray-800">Add Copy</h3>
                        <p class="text-sm text-gray-500 mt-1">
                            Each physical copy needs its own accession number and barcode.
                        </p>
                    </div>

                    <form method="POST" action="{{ route('librarian.books.copies.store', $book) }}" class="space-y-5">
                        @csrf

                        <div>
                            <label for="accession_no" class="block text-sm font-medium text-gray-700 mb-1">Accession
                                No</label>
                            <input type="text" id="accession_no" name="accession_no"
                                value="{{ old('accession_no') }}"
                                class="w-full border-gray-300 rounded-md shadow-sm focus:border-emerald-500 focus:ring-emerald-500"
                                required>
                        </div>

                        <div>
                            <label for="barcode" class="block text-sm font-medium text-gray-700 mb-1">Barcode</label>
                            <input type="text" id="barcode" name="barcode" value="{{ old('barcode') }}"
                                class="w-full border-gray-300 rounded-md shadow-sm focus:border-emerald-500 focus:ring-emerald-500"
                                required>
                        </div>

                        <div>
                            <label for="shelf_location" class="block text-sm font-medium text-gray-700 mb-1">Shelf
                                Location</label>
                            <input type="text" id="shelf_location" name="shelf_location"
                                value="{{ old('shelf_location') }}"
                                class="w-full border-gray-300 rounded-md shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                        </div>

                        <div class="pt-2">
                            <button type="submit"
                                class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-md shadow-sm">
                                Add Copy
                            </button>
                        </div>
                    </form>

                    <div class="mt-8 border-t border-gray-200 pt-6">
                        <h4 class="text-lg font-semibold text-gray-800 mb-4">Registered Copies</h4>

                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 text-sm">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-3 text-left">Accession No</th>
                                        <th class="px-4 py-3 text-left">Barcode</th>
                                        <th class="px-4 py-3 text-left">Shelf</th>
                                        <th class="px-4 py-3 text-left">Status</th>
                                        <th class="px-4 py-3 text-left">Available</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 bg-white">
                                    @forelse($book->copies as $copy)
                                        <tr class="{{ $loop->even ? 'bg-gray-50/40' : '' }}">
                                            <td class="px-4 py-3">{{ $copy->accession_no }}</td>
                                            <td class="px-4 py-3">{{ $copy->barcode }}</td>
                                            <td class="px-4 py-3">{{ $copy->shelf_location ?? 'N/A' }}</td>
                                            <td class="px-4 py-3">{{ ucfirst($copy->status) }}</td>
                                            <td class="px-4 py-3">
                                                @if ($copy->is_available)
                                                    <span
                                                        class="px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                        Yes
                                                    </span>
                                                @else
                                                    <span
                                                        class="px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                                        No
                                                    </span>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="px-4 py-6 text-center text-gray-500">
                                                No copies added yet.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                </div>

            </div>
        </div>
    </div>
</x-app-layout>
