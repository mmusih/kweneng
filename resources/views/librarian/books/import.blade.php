<x-app-layout>
    <x-slot name="header">
        <div class="mt-16 p-6 bg-gradient-to-r from-emerald-600 to-teal-700 rounded-lg shadow-lg">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <h2 class="font-semibold text-2xl text-white leading-tight">
                        Import Books from CSV
                    </h2>
                    <p class="text-emerald-100 text-sm mt-1">
                        Import physical book copies using accession numbers. ISBN lookup is used when ISBN is supplied.
                    </p>
                </div>

                <a href="{{ route('librarian.books.index') }}"
                    class="px-4 py-2 bg-white text-emerald-700 rounded-md font-semibold">
                    Back to Catalog
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if (session('success'))
                <div class="rounded-lg bg-green-50 border border-green-200 p-4 text-green-800">
                    {{ session('success') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="rounded-lg bg-red-50 border border-red-200 p-4 text-red-800">
                    <ul class="list-disc pl-5 space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="bg-white rounded-lg shadow-sm p-6">
                <form method="POST" action="{{ route('librarian.books.import.preview') }}" enctype="multipart/form-data">
                    @csrf

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            CSV File
                        </label>

                        <input type="file" name="csv_file" accept=".csv,.txt"
                            class="block w-full border-gray-300 rounded-md shadow-sm" required>
                    </div>

                    <div class="mt-6 rounded-lg bg-gray-50 border border-gray-200 p-4 text-sm text-gray-700">
                        <p class="font-semibold mb-2">Recommended CSV header:</p>

                        <code class="block bg-white border p-3 rounded overflow-x-auto">
                            accession_no,title,author,isbn,publisher,publication_year,category,barcode,shelf_location,description
                        </code>

                        <p class="mt-3">
                            <strong>accession_no</strong> is required. If <strong>isbn</strong> is supplied, the system will try to fetch book details and thumbnail automatically.
                        </p>

                        <p class="mt-2">
                            If <strong>barcode</strong> is empty, the system will use the accession number as the barcode.
                        </p>
                    </div>

                    <div class="mt-6">
                        <button type="submit"
                            class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-semibold rounded-md">
                            Preview Import
                        </button>
                    </div>
                </form>
            </div>

            @if (!empty($previewRows))
                <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                    <div class="px-6 py-4 border-b flex flex-col md:flex-row md:items-center md:justify-between gap-3">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-800">Import Preview</h3>
                            <p class="text-sm text-gray-500">
                                Review errors and warnings before importing.
                            </p>
                        </div>

                        <form method="POST" action="{{ route('librarian.books.import.apply') }}"
                            onsubmit="return confirm('Import all valid rows? Rows with errors will be skipped.');">
                            @csrf
                            <button type="submit"
                                class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-md">
                                Import Valid Rows
                            </button>
                        </form>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left">Row</th>
                                    <th class="px-4 py-3 text-left">Status</th>
                                    <th class="px-4 py-3 text-left">Accession</th>
                                    <th class="px-4 py-3 text-left">Barcode</th>
                                    <th class="px-4 py-3 text-left">Book</th>
                                    <th class="px-4 py-3 text-left">ISBN</th>
                                    <th class="px-4 py-3 text-left">Lookup</th>
                                    <th class="px-4 py-3 text-left">Notes</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 bg-white">
                                @foreach ($previewRows as $row)
                                    <tr class="{{ empty($row['can_import']) ? 'bg-red-50' : ($loop->even ? 'bg-gray-50/40' : '') }}">
                                        <td class="px-4 py-3">
                                            {{ $row['row_number'] }}
                                        </td>

                                        <td class="px-4 py-3">
                                            @if ($row['can_import'])
                                                <span class="px-2 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-800">
                                                    Valid
                                                </span>
                                            @else
                                                <span class="px-2 py-1 rounded-full text-xs font-semibold bg-red-100 text-red-800">
                                                    Error
                                                </span>
                                            @endif
                                        </td>

                                        <td class="px-4 py-3 font-medium">
                                            {{ $row['accession_no'] ?: '—' }}
                                        </td>

                                        <td class="px-4 py-3">
                                            {{ $row['barcode'] ?: '—' }}
                                        </td>

                                        <td class="px-4 py-3">
                                            <div class="font-medium text-gray-900">
                                                {{ $row['title'] ?: '—' }}
                                            </div>
                                            <div class="text-xs text-gray-500">
                                                {{ $row['author'] ?: 'Unknown author' }}
                                            </div>
                                            @if (!empty($row['thumbnail_url']))
                                                <img src="{{ $row['thumbnail_url'] }}" alt="Cover"
                                                    class="mt-2 h-20 rounded border bg-white">
                                            @endif
                                        </td>

                                        <td class="px-4 py-3">
                                            {{ $row['isbn'] ?: '—' }}
                                        </td>

                                        <td class="px-4 py-3">
                                            {{ $row['lookup_source'] ? str_replace('_', ' ', ucfirst($row['lookup_source'])) : 'None' }}
                                        </td>

                                        <td class="px-4 py-3">
                                            @if (!empty($row['errors']))
                                                <div class="text-red-700">
                                                    @foreach ($row['errors'] as $error)
                                                        <div>• {{ $error }}</div>
                                                    @endforeach
                                                </div>
                                            @endif

                                            @if (!empty($row['warnings']))
                                                <div class="text-amber-700 mt-1">
                                                    @foreach ($row['warnings'] as $warning)
                                                        <div>• {{ $warning }}</div>
                                                    @endforeach
                                                </div>
                                            @endif

                                            @if (empty($row['errors']) && empty($row['warnings']))
                                                <span class="text-gray-500">Ready</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>