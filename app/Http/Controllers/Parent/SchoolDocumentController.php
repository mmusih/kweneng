<?php

namespace App\Http\Controllers\Parent;

use App\Http\Controllers\Controller;
use App\Models\SchoolDocument;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SchoolDocumentController extends Controller
{
    /**
     * Show available school documents grouped by category.
     */
    public function index()
    {
        $documents = SchoolDocument::where('is_active', true)
            ->with('academicYear')
            ->orderBy('category')
            ->orderByDesc('created_at')
            ->get()
            ->groupBy('category');

        return view('parent.documents.index', compact('documents'));
    }

    /**
     * Stream a document file to the browser for download.
     */
    public function download(SchoolDocument $document): StreamedResponse
    {
        abort_unless($document->is_active, 404);
        abort_unless(Storage::disk('public')->exists($document->file_path), 404, 'File not found.');

        return Storage::disk('public')->download(
            $document->file_path,
            $document->original_filename
        );
    }
}
