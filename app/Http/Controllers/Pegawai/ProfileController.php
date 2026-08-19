<?php

namespace App\Http\Controllers\Pegawai;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;

/**
 * Mengelola foto, tanda tangan, dan password profil pegawai.
 */
class ProfileController extends BasePegawaiController
{
    /**
     * Menampilkan halaman profil pegawai aktif.
     */
    public function index()
    {
        // Data pengguna dan elemen layout disiapkan oleh controller dasar pegawai.
        return view('pegawai.profile.index', $this->layoutData());
    }

    /**
     * Memperbarui atau menghapus foto profil maupun tanda tangan pegawai.
     */
    public function update(Request $request): RedirectResponse
    {
        // Semua perubahan media diterapkan pada akun pegawai yang sedang aktif.
        $pegawai = $this->currentPegawai();

        // Tangani aksi penghapusan foto lebih dahulu dan hentikan alur setelah selesai.
        if ($request->boolean('remove_profile_photo')) {
            // Hapus file fisik sebelum mengosongkan referensinya di database.
            $this->deleteStoredFile($pegawai->profile_photo_path);

            $pegawai->update([
                'profile_photo_path' => null,
            ]);

            return redirect()
                ->route('pegawai.profile.index')
                ->with('success', 'Foto profil berhasil dihapus.');
        }

        // Tangani penghapusan tanda tangan sebagai aksi terpisah.
        if ($request->boolean('remove_signature')) {
            // Bersihkan file lama agar tidak menjadi berkas yatim.
            $this->deleteStoredFile($pegawai->signature_path);

            // Catat waktu perubahan untuk kebutuhan audit tanda tangan.
            $pegawai->update([
                'signature_path' => null,
                'signature_updated_at' => now(),
            ]);

            return redirect()
                ->route('pegawai.profile.index')
                ->with('success', 'Tanda tangan berhasil dihapus.');
        }

        // Jika berkas tanda tangan dikirim, validasi dan simpan sebagai tanda tangan baru.
        if ($request->hasFile('signature_file')) {
            // Error dimasukkan ke bag khusus agar tampil di formulir tanda tangan.
            $validated = $request->validateWithBag('updateSignature', [
                'signature_file' => ['required', 'image', 'mimes:jpg,jpeg,jfif,png,webp', 'max:2048'],
            ]);

            // Hapus tanda tangan sebelumnya sebelum menyimpan penggantinya.
            $this->deleteStoredFile($pegawai->signature_path);

            // Simpan file pada disk publik di folder khusus tanda tangan.
            $path = $validated['signature_file']->store('signatures', 'public');

            // Simpan path baru beserta waktu pembaruannya pada profil.
            $pegawai->update([
                'signature_path' => $path,
                'signature_updated_at' => now(),
            ]);

            return redirect()
                ->route('pegawai.profile.index')
                ->with('success', 'Tanda tangan berhasil diperbarui.');
        }

        // Jika tidak ada aksi tanda tangan, permintaan dianggap sebagai unggahan foto.
        $validated = $request->validateWithBag('updatePhoto', [
            'profile_photo' => ['required', 'image', 'mimes:jpg,jpeg,jfif,png,webp', 'max:2048'],
        ]);

        // Hapus foto lama agar penyimpanan tidak menumpuk berkas yang tak terpakai.
        $this->deleteStoredFile($pegawai->profile_photo_path);

        // Simpan foto baru pada folder profil di disk publik.
        $path = $validated['profile_photo']->store('profile-photos', 'public');

        // Hubungkan profil pegawai dengan path foto yang baru.
        $pegawai->update([
            'profile_photo_path' => $path,
        ]);

        \App\Support\ActivityLogger::log(
            'profile_updated',
            'Pembaruan Foto Profil',
            'Mengunggah dan memperbarui foto profil akun.'
        );

        return redirect()
            ->route('pegawai.profile.index')
            ->with('success', 'Foto profil berhasil diperbarui.');
    }

    /**
     * Mengganti password setelah password saat ini berhasil diverifikasi.
     */
    public function updatePassword(Request $request): RedirectResponse
    {
        // Tetapkan akun yang akan diperbarui dari konteks pegawai saat ini.
        $pegawai = $this->currentPegawai();

        // Pisahkan error ke bag password dan terapkan aturan keamanan bawaan Laravel.
        $validated = $request->validateWithBag('updatePassword', [
            'current_password' => ['required', 'current_password'],
            'password' => ['required', Password::defaults(), 'confirmed', 'different:current_password'],
        ]);

        // Simpan hanya hash password; nilai mentah tidak pernah dipersist.
        $pegawai->update([
            'password' => Hash::make($validated['password']),
        ]);

        \App\Support\ActivityLogger::log(
            'password_changed',
            'Pembaruan Password',
            'Mengubah password akun pegawai.'
        );

        return redirect()
            ->route('pegawai.profile.index')
            ->with('success', 'Password berhasil diperbarui.');
    }

    /**
     * Menghapus file media lama dari disk publik dengan fallback file sistem.
     */
    private function deleteStoredFile(?string $path): void
    {
        // Lewati operasi penyimpanan ketika model tidak memiliki path file.
        if (filled($path)) {
            // Gunakan konfigurasi disk publik yang sama dengan proses unggah.
            $disk = Storage::disk('public');

            // Berhenti jika penghapusan melalui adapter storage berhasil.
            if ($disk->delete($path)) {
                return;
            }

            // Siapkan path absolut sebagai fallback untuk adapter yang gagal menghapus.
            $absolutePath = $disk->path($path);

            // Periksa keberadaan file sebelum mencoba penghapusan langsung.
            if (is_file($absolutePath)) {
                // Abaikan warning level sistem karena kegagalan tidak boleh memutus halaman profil.
                @unlink($absolutePath);
            }
        }
    }
}
