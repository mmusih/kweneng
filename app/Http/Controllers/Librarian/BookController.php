<?php

namespace App\Http\Controllers\Librarian;

use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Models\BookCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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
            'accession_no' => [
                'required',
                'string',
                'max:255',
                Rule::unique('book_copies', 'accession_no'),
            ],
            'barcode' => [
                'required',
                'string',
                'max:255',
                Rule::unique('book_copies', 'barcode'),
            ],
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

    public function lookupIsbn(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'isbn' => ['required', 'string', 'max:50'],
        ]);

        $isbn = preg_replace('/[^0-9Xx]/', '', $validated['isbn']);

        if (!$isbn) {
            return response()->json([
                'found' => false,
                'message' => 'Invalid ISBN supplied.',
            ], 422);
        }

        // 1) Open Library Search API (preferred)
        $openLibrary = $this->lookupFromOpenLibrary($isbn);
        if ($openLibrary['found']) {
            return response()->json($openLibrary);
        }

        // 2) Google Books fallback
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

            $title = $doc['title'] ?? null;
            $author = $doc['author_name'][0] ?? null;
            $publisher = $doc['publisher'][0] ?? null;
            $publicationYear = $doc['first_publish_year'] ?? null;

            $thumbnailUrl = "https://covers.openlibrary.org/b/isbn/{$isbn}-M.jpg?default=false";

            return [
                'found' => true,
                'source' => 'open_library',
                'data' => [
                    'isbn' => $isbn,
                    'title' => $title,
                    'author' => $author,
                    'publisher' => $publisher,
                    'publication_year' => $publicationYear,
                    'description' => null,
                    'thumbnail_url' => $thumbnailUrl,
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
}
