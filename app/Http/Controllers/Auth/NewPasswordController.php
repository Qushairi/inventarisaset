<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\View\View;

/**
 * Mengelola formulir dan proses penyimpanan password baru.
 */
class NewPasswordController extends Controller
{
    /**
     * Menampilkan formulir reset beserta email dan token dari tautan pengguna.
     */
    public function create(Request $request, string $token): View
    {
        // Teruskan identitas email dan token agar formulir dapat mengirimkannya kembali.
        return view('auth.reset-password', [
            'email' => $request->string('email')->toString(),
            'token' => $token,
        ]);
    }

    /**
     * Memvalidasi token lalu mengganti password akun terkait.
     */
    public function store(Request $request): RedirectResponse
    {
        // Validasi token, email, password, dan kolom konfirmasinya.
        $credentials = $request->validate([
            'token' => ['required'],
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'confirmed', PasswordRule::defaults()],
        ], [
            'token.required' => 'Token reset password tidak tersedia.',
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'password.required' => 'Password baru wajib diisi.',
            'password.confirmed' => 'Konfirmasi password baru tidak cocok.',
            'password.min' => 'Password baru minimal :min karakter.',
        ]);

        // Broker memverifikasi token sebelum menjalankan callback perubahan password.
        $status = Password::reset(
            $credentials,
            function (User $user, string $password): void {
                // Simpan hash password baru dan rotasi token "remember me".
                $user->forceFill([
                    'password' => Hash::make($password),
                ])->setRememberToken(Str::random(60));

                // Persist perubahan akun sebelum mencabut sesi yang masih aktif.
                $user->save();

                // Paksa perangkat lain login ulang setelah password berubah.
                $this->deleteDatabaseSessions($user);

                // Beri tahu listener Laravel bahwa reset password telah selesai.
                event(new PasswordReset($user));
            }
        );

        // Jika token valid dan reset berhasil, arahkan pengguna ke halaman login.
        if ($status === Password::PASSWORD_RESET) {
            return redirect()
                ->route('login')
                ->with('status', 'Password berhasil direset. Silakan login menggunakan password baru.');
        }

        // Kembalikan pesan generik jika token salah, sudah dipakai, atau kedaluwarsa.
        return back()
            ->withInput($request->only('email'))
            ->withErrors([
                'email' => 'Tautan reset password tidak valid atau sudah kedaluwarsa. Silakan minta tautan baru.',
            ]);
    }

    /**
     * Menghapus sesi lama pengguna saat aplikasi memakai driver sesi database.
     */
    private function deleteDatabaseSessions(User $user): void
    {
        // Driver selain database tidak memiliki tabel sesi yang dapat dibersihkan di sini.
        if (config('session.driver') !== 'database') {
            return;
        }

        // Hapus semua baris sesi milik pengguna dari koneksi dan tabel terkonfigurasi.
        DB::connection(config('session.connection'))
            ->table(config('session.table', 'sessions'))
            ->where('user_id', $user->getAuthIdentifier())
            ->delete();
    }
}
