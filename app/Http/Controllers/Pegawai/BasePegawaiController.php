<?php

namespace App\Http\Controllers\Pegawai;

use App\Http\Controllers\Controller;
use App\Models\User;

/**
 * Menyediakan data dan perilaku bersama untuk seluruh halaman pegawai.
 */
abstract class BasePegawaiController extends Controller
{
    /**
     * Mengambil akun pegawai yang menjadi konteks permintaan saat ini.
     */
    protected function currentPegawai(): User
    {
        // Utamakan pengguna login jika akunnya memang memiliki peran pegawai.
        $user = auth()->user();

        if ($user instanceof User && $user->role === 'pegawai') {
            return $user;
        }

        // Fallback ini menjaga halaman pegawai tetap memiliki konteks pengguna.
        return User::query()
            ->where('role', 'pegawai')
            ->orderBy('name')
            ->firstOrFail();
    }

    /**
     * Menggabungkan data layout standar dengan data khusus dari tiap halaman.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function layoutData(array $data = []): array
    {
        // Ambil pegawai sekali agar seluruh elemen layout merujuk akun yang sama.
        $pegawai = $this->currentPegawai();

        // Data pada $data dapat menambah atau menimpa nilai bawaan bila diperlukan.
        return array_merge([
            'sidebarPartial' => 'layouts.sidebar-pegawai',
            'pageUser' => $pegawai,
            'profileRoute' => 'pegawai.profile.index',
            'footerLabel' => 'Panel pegawai inventaris aset.',
            'pegawaiUser' => $pegawai,
            'pegawaiInitials' => $pegawai->initials(),
        ], $data);
    }
}
