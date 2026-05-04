<?php

namespace App\Http\Controllers\Pegawai;

use App\Http\Controllers\Controller;
use App\Models\User;

abstract class BasePegawaiController extends Controller
{
    protected function currentPegawai(): User
    {
        $user = auth()->user();

        if ($user instanceof User && $user->role === 'pegawai') {
            return $user;
        }

        return User::query()
            ->where('role', 'pegawai')
            ->orderBy('name')
            ->firstOrFail();
    }

    protected function layoutData(array $data = []): array
    {
        $pegawai = $this->currentPegawai();

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
