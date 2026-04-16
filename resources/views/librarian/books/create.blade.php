<x-app-layout>
    <x-slot name="header">
        <div class="mt-16 p-6 bg-gradient-to-r from-emerald-600 to-teal-700 rounded-lg shadow-lg">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <h2 class="font-semibold text-2xl text-white leading-tight">
                        Add Book
                    </h2>
                    <p class="text-emerald-100 text-sm mt-1">
                        Scan or type ISBN to fetch book details automatically. Manual entry remains available.
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
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">

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

            <div class="bg-white shadow-sm rounded-lg p-6">
                <div class="mb-6 border-b border-gray-200 pb-3">
                    <h3 class="text-xl font-semibold text-gray-800">ISBN Lookup</h3>
                    <p class="text-sm text-gray-500 mt-1">
                        Scan ISBN barcode or type ISBN manually to auto-fill the form.
                    </p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div class="md:col-span-3">
                        <label for="isbn_lookup" class="block text-sm font-medium text-gray-700 mb-1">ISBN</label>
                        <input type="text" id="isbn_lookup" placeholder="Scan or type ISBN"
                            class="w-full border-gray-300 rounded-md shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                    </div>

                    <div class="flex items-end">
                        <button type="button" id="lookup_isbn_btn"
                            class="w-full inline-flex justify-center items-center px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-semibold rounded-md shadow-sm">
                            Fetch Book Info
                        </button>
                    </div>
                </div>

                <div id="lookup_message" class="hidden mt-4 rounded-md p-3 text-sm"></div>

                <div id="lookup_preview" class="hidden mt-6 rounded-lg border border-gray-200 bg-gray-50 p-4">
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div class="md:col-span-1">
                            <img id="preview_thumbnail" src="" alt="Book cover"
                                class="w-full max-w-[180px] rounded-md border border-gray-200 bg-white">
                        </div>

                        <div class="md:col-span-3 text-sm text-gray-700 space-y-2">
                            <p><span class="font-medium">Title:</span> <span id="preview_title"></span></p>
                            <p><span class="font-medium">Author:</span> <span id="preview_author"></span></p>
                            <p><span class="font-medium">Publisher:</span> <span id="preview_publisher"></span></p>
                            <p><span class="font-medium">Publication Year:</span> <span id="preview_year"></span></p>
                            <p><span class="font-medium">Source:</span> <span id="preview_source"></span></p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white shadow-sm rounded-lg p-6">
                <form method="POST" action="{{ route('librarian.books.store') }}" class="space-y-5">
                    @csrf

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div class="md:col-span-2">
                            <label for="title" class="block text-sm font-medium text-gray-700 mb-1">Title</label>
                            <input type="text" id="title" name="title" value="{{ old('title') }}"
                                class="w-full border-gray-300 rounded-md shadow-sm focus:border-emerald-500 focus:ring-emerald-500"
                                required>
                        </div>

                        <div>
                            <label for="author" class="block text-sm font-medium text-gray-700 mb-1">Author</label>
                            <input type="text" id="author" name="author" value="{{ old('author') }}"
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
                                        {{ (string) old('book_category_id') === (string) $category->id ? 'selected' : '' }}>
                                        {{ $category->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label for="isbn" class="block text-sm font-medium text-gray-700 mb-1">ISBN</label>
                            <input type="text" id="isbn" name="isbn" value="{{ old('isbn') }}"
                                class="w-full border-gray-300 rounded-md shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                        </div>

                        <div>
                            <label for="publisher"
                                class="block text-sm font-medium text-gray-700 mb-1">Publisher</label>
                            <input type="text" id="publisher" name="publisher" value="{{ old('publisher') }}"
                                class="w-full border-gray-300 rounded-md shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                        </div>

                        <div>
                            <label for="publication_year"
                                class="block text-sm font-medium text-gray-700 mb-1">Publication Year</label>
                            <input type="number" id="publication_year" name="publication_year"
                                value="{{ old('publication_year') }}"
                                class="w-full border-gray-300 rounded-md shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                        </div>

                        <div class="md:col-span-2">
                            <label for="thumbnail_url" class="block text-sm font-medium text-gray-700 mb-1">Thumbnail
                                URL</label>
                            <input type="url" id="thumbnail_url" name="thumbnail_url"
                                value="{{ old('thumbnail_url') }}"
                                class="w-full border-gray-300 rounded-md shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                        </div>

                        <div class="md:col-span-2">
                            <label for="description"
                                class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                            <textarea id="description" name="description" rows="4"
                                class="w-full border-gray-300 rounded-md shadow-sm focus:border-emerald-500 focus:ring-emerald-500">{{ old('description') }}</textarea>
                        </div>

                        <div class="md:col-span-2">
                            <label class="inline-flex items-center">
                                <input type="checkbox" name="is_active" value="1"
                                    class="rounded border-gray-300 text-emerald-600 shadow-sm focus:ring-emerald-500"
                                    {{ old('is_active', '1') ? 'checked' : '' }}>
                                <span class="ml-2 text-sm text-gray-700">Book is active in catalog</span>
                            </label>
                        </div>
                    </div>

                    <div class="pt-2">
                        <button type="submit"
                            class="inline-flex items-center px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-semibold rounded-md shadow-sm">
                            Save Book
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const lookupInput = document.getElementById('isbn_lookup');
            const lookupButton = document.getElementById('lookup_isbn_btn');
            const lookupMessage = document.getElementById('lookup_message');
            const lookupPreview = document.getElementById('lookup_preview');

            const formFields = {
                title: document.getElementById('title'),
                author: document.getElementById('author'),
                isbn: document.getElementById('isbn'),
                publisher: document.getElementById('publisher'),
                publication_year: document.getElementById('publication_year'),
                description: document.getElementById('description'),
                thumbnail_url: document.getElementById('thumbnail_url'),
            };

            const preview = {
                thumbnail: document.getElementById('preview_thumbnail'),
                title: document.getElementById('preview_title'),
                author: document.getElementById('preview_author'),
                publisher: document.getElementById('preview_publisher'),
                year: document.getElementById('preview_year'),
                source: document.getElementById('preview_source'),
            };

            function showMessage(text, type = 'info') {
                lookupMessage.classList.remove('hidden', 'bg-red-50', 'border-red-200', 'text-red-800',
                    'bg-green-50', 'border-green-200', 'text-green-800', 'bg-blue-50', 'border-blue-200',
                    'text-blue-800');
                lookupMessage.classList.add('border');

                if (type === 'error') {
                    lookupMessage.classList.add('bg-red-50', 'border-red-200', 'text-red-800');
                } else if (type === 'success') {
                    lookupMessage.classList.add('bg-green-50', 'border-green-200', 'text-green-800');
                } else {
                    lookupMessage.classList.add('bg-blue-50', 'border-blue-200', 'text-blue-800');
                }

                lookupMessage.textContent = text;
            }

            function fillForm(data, source) {
                formFields.title.value = data.title ?? '';
                formFields.author.value = data.author ?? '';
                formFields.isbn.value = data.isbn ?? '';
                formFields.publisher.value = data.publisher ?? '';
                formFields.publication_year.value = data.publication_year ?? '';
                formFields.description.value = data.description ?? '';
                formFields.thumbnail_url.value = data.thumbnail_url ?? '';

                preview.title.textContent = data.title ?? 'N/A';
                preview.author.textContent = data.author ?? 'N/A';
                preview.publisher.textContent = data.publisher ?? 'N/A';
                preview.year.textContent = data.publication_year ?? 'N/A';
                preview.source.textContent = source ?? 'N/A';
                preview.thumbnail.src = data.thumbnail_url || 'https://placehold.co/180x260?text=No+Cover';

                lookupPreview.classList.remove('hidden');
            }

            async function lookupIsbn() {
                const isbn = lookupInput.value.trim();

                if (!isbn) {
                    showMessage('Please scan or type an ISBN first.', 'error');
                    return;
                }

                lookupButton.disabled = true;
                lookupButton.textContent = 'Fetching...';

                try {
                    const url = new URL(`{{ route('librarian.books.lookup-isbn') }}`);
                    url.searchParams.set('isbn', isbn);

                    const response = await fetch(url.toString(), {
                        headers: {
                            'Accept': 'application/json'
                        }
                    });

                    const data = await response.json();

                    if (!response.ok || !data.found) {
                        lookupPreview.classList.add('hidden');
                        showMessage(data.message || 'No book information found. Please enter details manually.',
                            'error');
                        return;
                    }

                    fillForm(data.data, data.source);
                    showMessage('Book information fetched successfully. Please review before saving.',
                        'success');
                } catch (error) {
                    lookupPreview.classList.add('hidden');
                    showMessage('Lookup failed. Please enter the book manually.', 'error');
                } finally {
                    lookupButton.disabled = false;
                    lookupButton.textContent = 'Fetch Book Info';
                }
            }

            lookupButton.addEventListener('click', lookupIsbn);

            lookupInput.addEventListener('keydown', function(event) {
                if (event.key === 'Enter') {
                    event.preventDefault();
                    lookupIsbn();
                }
            });
        });
    </script>
</x-app-layout>
