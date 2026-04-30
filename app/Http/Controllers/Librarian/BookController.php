<?php

namespace App\Http\Controllers\Librarian;

use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Models\BookCategory;
use App\Models\BookCopy;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\Rule;

class BookController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->input('search', ''));

        $books = Book::with(['category', 'copies'])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                        ->orWhere('author', 'like', "%{$search}%")
                        ->orWhere('isbn', 'like', "%{$search}%")
                        ->orWhere('publisher', 'like', "%{$search}%");
                });
            })
            ->orderBy('title')
            ->paginate(20)
            ->withQueryString();

        return view('librarian.books.index', [
            'books' => $books,
            'categories' => BookCategory::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function create()
    {
        return view('librarian.books.create', [
            'categories' => BookCategory::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'book_category_id' => ['nullable', 'exists:book_categories,id'],
            'title' => ['required', 'string', 'max:255'],
            'author' => ['nullable', 'string', 'max:255'],
            'isbn' => ['nullable', 'string', 'max:255'],
            'publisher' => ['nullable', 'string', 'max:255'],
            'publication_year' => ['nullable', 'integer', 'min:1900', 'max:' . now()->year],
            'description' => ['nullable', 'string'],
            'thumbnail_url' => ['nullable', 'url', 'max:2048'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);

        Book::create($validated);

        return redirect()
            ->route('librarian.books.index')
            ->with('success', 'Book created successfully.');
    }

    public function edit(Book $book)
    {
        $book->load(['category', 'copies']);

        return view('librarian.books.edit', [
            'book' => $book,
            'categories' => BookCategory::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Book $book)
    {
        $validated = $request->validate([
            'book_category_id' => ['nullable', 'exists:book_categories,id'],
            'title' => ['required', 'string', 'max:255'],
            'author' => ['nullable', 'string', 'max:255'],
            'isbn' => ['nullable', 'string', 'max:255'],
            'publisher' => ['nullable', 'string', 'max:255'],
            'publication_year' => ['nullable', 'integer', 'min:1900', 'max:' . now()->year],
            'description' => ['nullable', 'string'],
            'thumbnail_url' => ['nullable', 'url', 'max:2048'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);

        $book->update($validated);

        return redirect()
            ->route('librarian.books.edit', $book)
            ->with('success', 'Book updated successfully.');
    }

    public function storeCopy(Request $request, Book $book)
    {
        $validated = $request->validate([
            'accession_no' => ['required', 'string', 'max:255', Rule::unique('book_copies', 'accession_no')],
            'barcode' => ['required', 'string', 'max:255', Rule::unique('book_copies', 'barcode')],
            'shelf_location' => ['nullable', 'string', 'max:255'],
        ]);

        $book->copies()->create([
            'accession_no' => $validated['accession_no'],
            'barcode' => $validated['barcode'],
            'shelf_location' => $validated['shelf_location'] ?? null,
            'status' => 'available',
            'is_available' => true,
        ]);

        return redirect()
            ->route('librarian.books.edit', $book)
            ->with('success', 'Book copy added successfully.');
    }

    public function import()
    {
        return view('librarian.books.import', [
            'previewRows' => session('book_import_preview', []),
        ]);
    }

    public function importPreview(Request $request)
    {
        $request->validate([
            'csv_file' => ['required', 'file', 'mimes:csv,txt', 'max:4096'],
        ]);

        $handle = fopen($request->file('csv_file')->getRealPath(), 'r');

        if (!$handle) {
            return back()->withErrors(['csv_file' => 'Could not read uploaded CSV file.']);
        }

        $header = fgetcsv($handle);

        if (!$header) {
            fclose($handle);
            return back()->withErrors(['csv_file' => 'CSV file is empty or invalid.']);
        }

        $header = array_map(fn ($h) => strtolower(trim($h)), $header);

        $requiredHeader = 'accession_no';

        if (!in_array($requiredHeader, $header, true)) {
            fclose($handle);
            return back()->withErrors([
                'csv_file' => 'CSV must include an accession_no column.',
            ]);
        }

        $previewRows = [];
        $seenAccessions = [];
        $rowNumber = 1;

        while (($row = fgetcsv($handle)) !== false) {
            $rowNumber++;

            $data = array_combine($header, array_pad($row, count($header), ''));

            if (!$data) {
                continue;
            }

            $accessionNo = trim((string) ($data['accession_no'] ?? ''));
            $isbn = $this->cleanIsbn((string) ($data['isbn'] ?? ''));
            $barcode = trim((string) ($data['barcode'] ?? '')) ?: $accessionNo;

            $title = trim((string) ($data['title'] ?? ''));
            $author = trim((string) ($data['author'] ?? ''));
            $publisher = trim((string) ($data['publisher'] ?? ''));
            $publicationYear = trim((string) ($data['publication_year'] ?? ''));
            $description = trim((string) ($data['description'] ?? ''));
            $category = trim((string) ($data['category'] ?? ''));
            $shelfLocation = trim((string) ($data['shelf_location'] ?? ''));

            $lookupSource = null;
            $lookupData = [];

            if ($isbn) {
                $lookup = $this->lookupFromOpenLibrary($isbn);

                if (!$lookup['found']) {
                    $lookup = $this->lookupFromGoogleBooks($isbn);
                }

                if ($lookup['found']) {
                    $lookupSource = $lookup['source'];
                    $lookupData = $lookup['data'] ?? [];
                }
            }

            $title = $title ?: ($lookupData['title'] ?? '');
            $author = $author ?: ($lookupData['author'] ?? '');
            $publisher = $publisher ?: ($lookupData['publisher'] ?? '');
            $publicationYear = $publicationYear ?: ($lookupData['publication_year'] ?? '');
            $description = $description ?: ($lookupData['description'] ?? '');
            $thumbnailUrl = $lookupData['thumbnail_url'] ?? null;

            $errors = [];
            $warnings = [];

            if ($accessionNo === '') {
                $errors[] = 'Missing accession number.';
            }

            if ($title === '') {
                $errors[] = 'Missing title and no ISBN lookup result was found.';
            }

            if ($accessionNo !== '' && in_array($accessionNo, $seenAccessions, true)) {
                $errors[] = 'Duplicate accession number inside CSV.';
            }

            if ($accessionNo !== '' && BookCopy::where('accession_no', $accessionNo)->exists()) {
                $errors[] = 'Accession number already exists.';
            }

            if ($barcode !== '' && BookCopy::where('barcode', $barcode)->exists()) {
                $errors[] = 'Barcode already exists.';
            }

            if (!$isbn) {
                $warnings[] = 'No ISBN supplied; book details will be created from CSV only.';
            } elseif (!$lookupSource) {
                $warnings[] = 'ISBN lookup failed; CSV data will be used.';
            }

            if ($accessionNo !== '') {
                $seenAccessions[] = $accessionNo;
            }

            $previewRows[] = [
                'row_number' => $rowNumber,
                'accession_no' => $accessionNo,
                'barcode' => $barcode,
                'isbn' => $isbn,
                'title' => $title,
                'author' => $author,
                'publisher' => $publisher,
                'publication_year' => $publicationYear,
                'description' => $description,
                'thumbnail_url' => $thumbnailUrl,
                'category' => $category,
                'shelf_location' => $shelfLocation,
                'lookup_source' => $lookupSource,
                'errors' => $errors,
                'warnings' => $warnings,
                'can_import' => count($errors) === 0,
            ];
        }

        fclose($handle);

        session(['book_import_preview' => $previewRows]);

        return redirect()
            ->route('librarian.books.import')
            ->with('success', 'CSV preview generated. Review the rows before importing.');
    }

    public function importApply()
    {
        $previewRows = session('book_import_preview', []);

        if (empty($previewRows)) {
            return redirect()
                ->route('librarian.books.import')
                ->withErrors(['csv_file' => 'No import preview found. Please upload and preview a CSV first.']);
        }

        $createdBooks = 0;
        $updatedBooks = 0;
        $createdCopies = 0;
        $skippedRows = 0;

        DB::beginTransaction();

        try {
            foreach ($previewRows as $row) {
                if (empty($row['can_import'])) {
                    $skippedRows++;
                    continue;
                }

                $categoryId = null;

                if (!empty($row['category'])) {
                    $category = BookCategory::firstOrCreate(
                        ['name' => $row['category']],
                        ['description' => null, 'is_active' => true]
                    );

                    $categoryId = $category->id;
                }

                $book = null;

                if (!empty($row['isbn'])) {
                    $book = Book::where('isbn', $row['isbn'])->first();
                }

                if (!$book) {
                    $book = Book::where('title', $row['title'])
                        ->when(!empty($row['author']), fn ($q) => $q->where('author', $row['author']))
                        ->first();
                }

                $bookData = [
                    'book_category_id' => $categoryId,
                    'title' => $row['title'],
                    'author' => $row['author'] ?: null,
                    'isbn' => $row['isbn'] ?: null,
                    'publisher' => $row['publisher'] ?: null,
                    'publication_year' => $row['publication_year'] ?: null,
                    'description' => $row['description'] ?: null,
                    'thumbnail_url' => $row['thumbnail_url'] ?: null,
                    'is_active' => true,
                ];

                if ($book) {
                    $book->update(array_filter($bookData, fn ($value) => $value !== null && $value !== ''));
                    $updatedBooks++;
                } else {
                    $book = Book::create($bookData);
                    $createdBooks++;
                }

                if (
                    BookCopy::where('accession_no', $row['accession_no'])->exists()
                    || BookCopy::where('barcode', $row['barcode'])->exists()
                ) {
                    $skippedRows++;
                    continue;
                }

                $book->copies()->create([
                    'accession_no' => $row['accession_no'],
                    'barcode' => $row['barcode'] ?: $row['accession_no'],
                    'shelf_location' => $row['shelf_location'] ?: null,
                    'status' => 'available',
                    'is_available' => true,
                ]);

                $createdCopies++;
            }

            DB::commit();

            session()->forget('book_import_preview');

            return redirect()
                ->route('librarian.books.index')
                ->with(
                    'success',
                    "Import complete. Books created: {$createdBooks}, books updated: {$updatedBooks}, copies created: {$createdCopies}, skipped rows: {$skippedRows}."
                );
        } catch (\Throwable $e) {
            DB::rollBack();

            return redirect()
                ->route('librarian.books.import')
                ->withErrors(['csv_file' => 'Import failed: ' . $e->getMessage()]);
        }
    }

    public function lookupIsbn(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'isbn' => ['required', 'string', 'max:50'],
        ]);

        $isbn = $this->cleanIsbn($validated['isbn']);

        if (!$isbn) {
            return response()->json([
                'found' => false,
                'message' => 'Invalid ISBN supplied.',
            ], 422);
        }

        $openLibrary = $this->lookupFromOpenLibrary($isbn);

        if ($openLibrary['found']) {
            return response()->json($openLibrary);
        }

        $googleBooks = $this->lookupFromGoogleBooks($isbn);

        if ($googleBooks['found']) {
            return response()->json($googleBooks);
        }

        return response()->json([
            'found' => false,
            'message' => 'No book information found for this ISBN. Please enter details manually.',
        ], 404);
    }

    protected function lookupFromOpenLibrary(string $isbn): array
    {
        try {
            $response = Http::timeout(10)
                ->acceptJson()
                ->get('https://openlibrary.org/search.json', [
                    'isbn' => $isbn,
                    'limit' => 1,
                ]);

            if (!$response->successful()) {
                return ['found' => false];
            }

            $payload = $response->json();
            $doc = $payload['docs'][0] ?? null;

            if (!$doc) {
                return ['found' => false];
            }

            $coverId = $doc['cover_i'] ?? null;

            return [
                'found' => true,
                'source' => 'open_library',
                'data' => [
                    'isbn' => $isbn,
                    'title' => $doc['title'] ?? null,
                    'author' => $doc['author_name'][0] ?? null,
                    'publisher' => $doc['publisher'][0] ?? null,
                    'publication_year' => $doc['first_publish_year'] ?? null,
                    'description' => null,
                    'thumbnail_url' => $coverId
                        ? "https://covers.openlibrary.org/b/id/{$coverId}-M.jpg"
                        : "https://covers.openlibrary.org/b/isbn/{$isbn}-M.jpg?default=false",
                ],
            ];
        } catch (\Throwable $e) {
            return ['found' => false];
        }
    }

    protected function lookupFromGoogleBooks(string $isbn): array
    {
        try {
            $response = Http::timeout(10)
                ->acceptJson()
                ->get('https://www.googleapis.com/books/v1/volumes', [
                    'q' => 'isbn:' . $isbn,
                    'maxResults' => 1,
                ]);

            if (!$response->successful()) {
                return ['found' => false];
            }

            $payload = $response->json();
            $item = $payload['items'][0] ?? null;

            if (!$item) {
                return ['found' => false];
            }

            $info = $item['volumeInfo'] ?? [];

            return [
                'found' => true,
                'source' => 'google_books',
                'data' => [
                    'isbn' => $isbn,
                    'title' => $info['title'] ?? null,
                    'author' => $info['authors'][0] ?? null,
                    'publisher' => $info['publisher'] ?? null,
                    'publication_year' => isset($info['publishedDate']) ? (int) substr($info['publishedDate'], 0, 4) : null,
                    'description' => $info['description'] ?? null,
                    'thumbnail_url' => $info['imageLinks']['thumbnail'] ?? ($info['imageLinks']['smallThumbnail'] ?? null),
                ],
            ];
        } catch (\Throwable $e) {
            return ['found' => false];
        }
    }

    protected function cleanIsbn(string $isbn): string
    {
        return preg_replace('/[^0-9Xx]/', '', $isbn) ?? '';
    }
}