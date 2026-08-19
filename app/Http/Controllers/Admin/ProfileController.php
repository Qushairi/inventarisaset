<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Models\AssetReturn;
use App\Models\Loan;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;

/**
 * Menangani tampilan profil serta pembaruan identitas dan kredensial admin.
 */
class ProfileController extends Controller
{
    /**
     * Menampilkan profil admin aktif beserta ringkasan statistik sistem.
     */
    public function index()
    {
        // Ambil pengguna admin yang sedang terautentikasi.
        $admin = $this->currentAdmin();

        // Kirim data profil dan statistik terkini untuk ditampilkan pada dashboard profil.
        return view('admin.profile.index', [
            'adminUser' => $admin,
            'adminStats' => [
                [
                    'label' => 'Total Aset',
                    'value' => Asset::query()->count(),
                    'icon' => 'box-seam',
                    'variant' => 'primary',
                ],
                [
                    'label' => 'Peminjaman Menunggu',
                    'value' => Loan::query()->where('status', 'Menunggu')->count(),
                    'icon' => 'hourglass-split',
                    'variant' => 'warning',
                ],
                [
                    'label' => 'Pengembalian Terverifikasi',
                    'value' => AssetReturn::query()->where('status', 'Terverifikasi')->count(),
                    'icon' => 'clipboard-check',
                    'variant' => 'info',
                ],
                [
                    'label' => 'Total Pegawai',
                    'value' => User::query()->where('role', 'pegawai')->count(),
                    'icon' => 'people',
                    'variant' => 'success',
                ],
            ],
        ]);
    }

    /**
     * Memproses penghapusan atau penggantian foto profil dan tanda tangan admin.
     */
    public function update(Request $request): RedirectResponse
    {
        // Pastikan seluruh operasi berkas diterapkan pada admin yang sedang login.
        $admin = $this->currentAdmin();

        // Permintaan penghapusan foto diproses terlebih dahulu dan diakhiri dengan redirect.
        if ($request->boolean('remove_profile_photo')) {
            $this->deleteStoredFile($admin->profile_photo_path);

            // Kosongkan referensi foto setelah berkas lama dihapus.
            $admin->update([
                'profile_photo_path' => null,
            ]);

            // Beri umpan balik bahwa foto profil telah dihapus.
            return redirect()
                ->route('admin.profile.index')
                ->with('success', 'Foto profil admin berhasil dihapus.');
        }

        // Permintaan penghapusan tanda tangan diproses sebagai aksi terpisah.
        if ($request->boolean('remove_signature')) {
            $this->deleteStoredFile($admin->signature_path);

            // Kosongkan path dan catat waktu perubahan tanda tangan.
            $admin->update([
                'signature_path' => null,
                'signature_updated_at' => now(),
            ]);

            // Beri umpan balik bahwa tanda tangan telah dihapus.
            return redirect()
                ->route('admin.profile.index')
                ->with('success', 'Tanda tangan admin berhasil dihapus.');
        }

        // Jika ada unggahan tanda tangan, validasi menggunakan bag khusus formulir tanda tangan.
        if ($request->hasFile('signature_file')) {
            $validated = $request->validateWithBag('updateSignature', [
                'signature_file' => ['required', 'image', 'mimes:png,jpg,jpeg', 'max:2048'],
            ]);

            // Hapus berkas lama sebelum menyimpan tanda tangan pengganti.
            $this->deleteStoredFile($admin->signature_path);

            // Simpan tanda tangan baru pada direktori publik yang ditentukan.
            $path = $validated['signature_file']->store('signatures', 'public');

            // Perbarui referensi berkas dan waktu perubahan tanda tangan.
            $admin->update([
                'signature_path' => $path,
                'signature_updated_at' => now(),
            ]);

            // Kembali ke profil setelah pembaruan tanda tangan berhasil.
            return redirect()
                ->route('admin.profile.index')
                ->with('success', 'Tanda tangan admin berhasil diperbarui.');
        }

        // Tanpa aksi tanda tangan, validasi unggahan sebagai pembaruan foto profil.
        $validated = $request->validateWithBag('updatePhoto', [
            'profile_photo' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        // Hapus foto sebelumnya agar berkas lama tidak tertinggal di penyimpanan.
        $this->deleteStoredFile($admin->profile_photo_path);

        // Simpan foto baru pada direktori foto profil di disk publik.
        $path = $validated['profile_photo']->store('profile-photos', 'public');

        // Kaitkan path foto yang baru disimpan dengan akun admin.
        $admin->update([
            'profile_photo_path' => $path,
        ]);

        // Kembali ke halaman profil dengan pesan pembaruan berhasil.
        return redirect()
            ->route('admin.profile.index')
            ->with('success', 'Foto profil admin berhasil diperbarui.');
    }

    /**
     * Memvalidasi dan mengganti password admin yang sedang login.
     */
    public function updatePassword(Request $request): RedirectResponse
    {
        // Ambil akun admin yang akan menerima password baru.
        $admin = $this->currentAdmin();

        // Gunakan bag khusus agar error validasi tampil pada formulir password.
        $validated = $request->validateWithBag('updatePassword', [
            'current_password' => ['required', 'current_password'],
            'password' => ['required', Password::defaults(), 'confirmed', 'different:current_password'],
        ]);

        // Hash password baru sebelum menyimpannya ke database.
        $admin->update([
            'password' => Hash::make($validated['password']),
        ]);

        // Beri notifikasi setelah kredensial berhasil diperbarui.
        return redirect()
            ->route('admin.profile.index')
            ->with('success', 'Password admin berhasil diperbarui.');
    }

    /**
     * Mengembalikan pengguna aktif hanya jika memiliki peran admin.
     */
    private function currentAdmin(): User
    {
        // Ambil pengguna dari sesi autentikasi saat ini.
        $user = auth()->user();

        // Pastikan objek pengguna valid dan perannya sesuai dengan area admin.
        if ($user instanceof User && $user->role === 'admin') {
            return $user;
        }

        // Sembunyikan halaman profil admin dari pengguna yang tidak memenuhi syarat.
        abort(404);
    }

    /**
     * Menghapus berkas dari disk publik dengan fallback ke path fisik.
     */
    private function deleteStoredFile(?string $path): void
    {
        // Lewati proses apabila akun belum memiliki path berkas tersimpan.
        if (filled($path)) {
            $disk = Storage::disk('public');

            // Gunakan API storage sebagai cara utama untuk menghapus berkas.
            if ($disk->delete($path)) {
                return;
            }

            // Dapatkan path absolut sebagai fallback jika penghapusan melalui disk gagal.
            $absolutePath = $disk->path($path);

            // Hapus langsung hanya ketika path fallback benar-benar menunjuk sebuah berkas.
            if (is_file($absolutePath)) {
                @unlink($absolutePath);
            }
        }
    }
}
