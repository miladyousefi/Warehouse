<?php

namespace App\Http\Controllers\Media;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class PublicFileController extends Controller
{
    public function show(Request $request, string $path): BinaryFileResponse
    {
        // Prevent path traversal and restrict to the avatars directory.
        $path = ltrim($path, '/');
        if (str_contains($path, '..') || !str_starts_with($path, 'avatars/')) {
            abort(404);
        }

        $disk = Storage::disk('public');
        if (!$disk->exists($path)) {
            abort(404);
        }

        $fullPath = $disk->path($path);
        $mime = $disk->mimeType($path) ?? 'application/octet-stream';

        return response()->file($fullPath, [
            'Content-Type' => $mime,
            // Files are stored with unique names; safe to cache.
            'Cache-Control' => 'private, max-age=86400',
        ]);
    }
}

