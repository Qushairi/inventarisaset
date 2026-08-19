<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\View\View;

/**
 * Mengelola permintaan tautan untuk mengatur ulang password.
 */
class PasswordResetLinkController extends Controller
{
    /**
     * Menampilkan formulir permintaan tautan reset password.
     */
    public function create(): View
    {
        // Render halaman tempat pengguna memasukkan alamat email akunnya.
        return view('auth.forgot-password');
    }

    /**
     * Memvalidasi email dan meminta Laravel mengirim tautan reset password.
     */
    public function store(Request $request): RedirectResponse
    {
        // Pastikan email terisi dan memiliki format yang valid.
        $request->validate([
            'email' => ['required', 'string', 'email'],
        ], [
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
        ]);

        // Broker password menangani pencarian akun, token, dan pengiriman notifikasi.
        Password::sendResetLink($request->only('email'));

        // Selalu berikan respons yang sama agar status pendaftaran email tidak bocor.
        return back()
            ->withInput($request->only('email'))
            ->with('status', 'Jika email terdaftar, tautan reset password telah dikirim. Silakan periksa kotak masuk dan folder spam.');
    }
}
