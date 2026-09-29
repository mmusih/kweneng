<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StudentPhotoController extends Controller
{
    /**
     * Serve public student images when the host has no public/storage symlink.
     * Keep the existing URL so web pages and mobile clients both benefit.
     */
    public function __invoke(string $filename): StreamedResponse
    {
        abort_unless(preg_match('/\A[A-Za-z0-9_-]+\.(?:jpe?g|png|webp)\z/i', $filename), 404);

        $disk = Storage::disk('public');
        $path = 'students/'.$filename;
        abort_unless($disk->exists($path), 404);

        $mimeType = $disk->mimeType($path);
        abort_unless(in_array($mimeType, ['image/jpeg', 'image/png', 'image/webp'], true), 404);

        return $disk->response($path, null, [
            'Content-Type' => $mimeType,
            'Cache-Control' => 'public, max-age=86400',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
