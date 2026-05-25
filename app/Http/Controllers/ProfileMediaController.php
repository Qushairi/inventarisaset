<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class ProfileMediaController extends Controller
{
    public function show(string $path): Response
    {
        abort_unless($this->isAllowedPath($path), 404);

        $disk = Storage::disk('public');

        abort_unless($disk->exists($path), 404);

        return $disk->response($path);
    }

    private function isAllowedPath(string $path): bool
    {
        $normalizedPath = str_replace('\\', '/', $path);

        if ($normalizedPath !== $path || str_contains($normalizedPath, '..')) {
            return false;
        }

        return str_starts_with($normalizedPath, 'profile-photos/')
            || str_starts_with($normalizedPath, 'signatures/')
            || str_starts_with($normalizedPath, 'asset-images/');
    }
}
