<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SchoolDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ParentDocumentsController extends Controller
{
    // GET /api/parent/documents
    public function index()
    {
        $documents = SchoolDocument::orderBy('category')->orderByDesc('created_at')->get();

        return response()->json([
            'documents' => $documents->map(fn($d) => $this->formatDoc($d)),
        ]);
    }

    // GET /api/parent/documents/{id}/download
    public function download($id)
    {
        $doc = SchoolDocument::findOrFail($id);

        if (!Storage::disk('public')->exists($doc->file_path)) {
            return response()->json(['message' => 'File not found.'], 404);
        }

        return response()->download(
            Storage::disk('public')->path($doc->file_path),
            $doc->original_filename
        );
    }

    private function formatDoc($d): array
    {
        return [
            'id'                => $d->id,
            'title'             => $d->title,
            'category'          => $d->category,
            'original_filename' => $d->original_filename,
            'academic_year'     => $d->academic_year,
            'created_at'        => $d->created_at->toDateTimeString(),
        ];
    }
}
