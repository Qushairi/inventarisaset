<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

/**
 * Melayani file media profil dan aset yang disimpan pada disk publik.
 *
 * Akses file dibatasi ke folder yang diizinkan agar parameter URL tidak dapat
 * dipakai untuk membaca file lain di luar direktori media aplikasi.
 */
class ProfileMediaController extends Controller
{
    /**
     * Menampilkan file media yang diminta sebagai respons HTTP.
     */
    public function show(string $path): Response
    {
        // Tolak path yang tidak aman atau berada di luar folder yang diizinkan.
        abort_unless($this->isAllowedPath($path), 404);

        // Gunakan disk publik yang sama dengan lokasi penyimpanan media aplikasi.
        $disk = Storage::disk('public');

        // Sembunyikan detail penyimpanan dengan respons 404 jika file tidak ada.
        abort_unless($disk->exists($path), 404);

        // Biarkan Laravel membentuk respons file beserta header yang sesuai.
        return $disk->response($path);
    }

    /**
     * Memastikan path bersifat relatif, bebas traversal, dan berada pada folder aman.
     */
    private function isAllowedPath(string $path): bool
    {
        // Samakan pemisah direktori agar pemeriksaan konsisten pada semua sistem operasi.
        $normalizedPath = str_replace('\\', '/', $path);

        // Tolak backslash asli dan segmen ".." yang dapat digunakan untuk path traversal.
        if ($normalizedPath !== $path || str_contains($normalizedPath, '..')) {
            return false;
        }

        // Hanya media profil, tanda tangan, dan gambar aset yang boleh dilayani.
        return str_starts_with($normalizedPath, 'profile-photos/')
            || str_starts_with($normalizedPath, 'signatures/')
            || str_starts_with($normalizedPath, 'asset-images/');
    }
}
