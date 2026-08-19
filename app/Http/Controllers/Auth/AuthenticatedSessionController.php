<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Support\DashboardRedirector;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Mengelola proses masuk dan keluar pengguna pada sesi web.
 */
class AuthenticatedSessionController extends Controller
{
    /**
     * Menampilkan halaman formulir login.
     */
    public function create(): View
    {
        // Render halaman login untuk pengguna yang belum terautentikasi.
        return view('auth.login');
    }

    /**
     * Memproses kredensial login dan membuat sesi pengguna.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        // Delegasikan validasi kredensial dan pembatasan percobaan ke LoginRequest.
        $request->authenticate();

        // Ganti ID sesi setelah login untuk mencegah serangan session fixation.
        $request->session()->regenerate();

        // Kembali ke tujuan awal atau dashboard yang sesuai dengan peran pengguna.
        return redirect()->intended(DashboardRedirector::pathFor($request->user()));
    }

    /**
     * Mengakhiri sesi pengguna yang sedang aktif.
     */
    public function destroy(Request $request): RedirectResponse
    {
        // Keluarkan pengguna dari guard web.
        Auth::guard('web')->logout();

        // Hapus seluruh data sesi lama agar tidak dapat digunakan kembali.
        $request->session()->invalidate();

        // Buat token CSRF baru untuk sesi anonim setelah logout.
        $request->session()->regenerateToken();

        // Arahkan pengguna kembali ke halaman login.
        return redirect()->route('login');
    }
}
