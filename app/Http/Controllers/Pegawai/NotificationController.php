<?php

namespace App\Http\Controllers\Pegawai;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Notifications\DatabaseNotification;

/**
 * Mengelola daftar dan status baca notifikasi milik pegawai.
 */
class NotificationController extends BasePegawaiController
{
    /**
     * Menampilkan notifikasi terbaru beserta jumlah yang belum dibaca.
     */
    public function index()
    {
        // Gunakan pegawai aktif sebagai pemilik seluruh data notifikasi.
        $pegawai = $this->currentPegawai();

        // Paginasi notifikasi agar halaman tetap ringan ketika riwayat bertambah.
        return view('pegawai.notifications.index', $this->layoutData([
            'notifications' => $pegawai->notifications()->latest()->paginate(12),
            'unreadNotificationCount' => $pegawai->unreadNotifications()->count(),
        ]));
    }

    /**
     * Menandai satu notifikasi sebagai dibaca lalu membuka tujuan aksinya.
     */
    public function show(DatabaseNotification $notification): RedirectResponse
    {
        // Ambil konteks pengguna untuk validasi kepemilikan notifikasi.
        $pegawai = $this->currentPegawai();

        // Cegah pegawai membuka notifikasi milik akun lain.
        $this->ensureNotificationOwnership($notification, $pegawai);

        // Hindari pembaruan database yang tidak perlu jika sudah pernah dibaca.
        if (is_null($notification->read_at)) {
            $notification->markAsRead();
        }

        // Gunakan halaman daftar sebagai fallback jika notifikasi tidak memiliki URL aksi.
        return redirect($notification->data['action_url'] ?? route('pegawai.notifications.index'));
    }

    /**
     * Menandai seluruh notifikasi pegawai sebagai sudah dibaca.
     */
    public function markAllAsRead(): RedirectResponse
    {
        // Batasi operasi massal pada notifikasi milik pegawai yang aktif.
        $pegawai = $this->currentPegawai();

        // Koleksi unreadNotifications menerapkan perubahan hanya pada item yang belum dibaca.
        $pegawai->unreadNotifications->markAsRead();

        // Kembali ke daftar notifikasi dengan pesan konfirmasi.
        return redirect()
            ->route('pegawai.notifications.index')
            ->with('success', 'Semua notifikasi berhasil ditandai sudah dibaca.');
    }

    public function destroy(DatabaseNotification $notification): RedirectResponse
    {
        $pegawai = $this->currentPegawai();

        $this->ensureNotificationOwnership($notification, $pegawai);

        $notification->delete();

        return redirect()
            ->route('pegawai.notifications.index')
            ->with('success', 'Notifikasi berhasil dihapus.');
    }

    public function destroyAll(): RedirectResponse
    {
        $pegawai = $this->currentPegawai();

        $pegawai->notifications()->delete();

        return redirect()
            ->route('pegawai.notifications.index')
            ->with('success', 'Semua riwayat notifikasi berhasil dibersihkan.');
    }

    /**
     * Memastikan notifikasi database terkait langsung dengan akun pegawai.
     */
    private function ensureNotificationOwnership(DatabaseNotification $notification, User $pegawai): void
    {
        // Validasi tipe polymorphic dan ID pemilik sekaligus.
        abort_if(
            $notification->notifiable_type !== User::class || (int) $notification->notifiable_id !== $pegawai->id,
            404,
        );
    }
}
