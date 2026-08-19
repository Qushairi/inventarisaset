<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Notifications\DatabaseNotification;

/**
 * Menangani daftar dan status baca notifikasi milik admin.
 */
class NotificationController extends Controller
{
    /**
     * Menampilkan notifikasi admin beserta jumlah notifikasi yang belum dibaca.
     */
    public function index()
    {
        // Tentukan akun admin yang menjadi pemilik notifikasi.
        $admin = $this->currentAdmin();

        // Urutkan notifikasi terbaru dan batasi hasil per halaman.
        return view('admin.notifications.index', [
            'notifications' => $admin->notifications()->latest()->paginate(12),
            'unreadNotificationCount' => $admin->unreadNotifications()->count(),
        ]);
    }

    /**
     * Menandai satu notifikasi sebagai dibaca lalu membuka tujuan aksinya.
     *
     * @param  DatabaseNotification  $notification  Notifikasi dari route model binding.
     */
    public function show(DatabaseNotification $notification): RedirectResponse
    {
        // Dapatkan admin aktif sebagai acuan pemeriksaan kepemilikan.
        $admin = $this->currentAdmin();

        // Tolak akses jika notifikasi bukan milik admin yang sedang digunakan.
        $this->ensureNotificationOwnership($notification, $admin);

        // Hindari penulisan ulang waktu baca bila notifikasi sudah pernah dibaca.
        if (is_null($notification->read_at)) {
            $notification->markAsRead();
        }

        // Buka URL aksi notifikasi atau kembali ke daftar bila URL tidak tersedia.
        return redirect($notification->data['action_url'] ?? route('admin.notifications.index'));
    }

    /**
     * Menandai seluruh notifikasi admin sebagai sudah dibaca.
     */
    public function markAllAsRead(): RedirectResponse
    {
        // Ambil admin yang notifikasinya akan diperbarui.
        $admin = $this->currentAdmin();

        // Terapkan status baca hanya pada koleksi notifikasi yang masih belum dibaca.
        $admin->unreadNotifications->markAsRead();

        // Kembali ke daftar notifikasi dengan pesan konfirmasi.
        return redirect()
            ->route('admin.notifications.index')
            ->with('success', 'Semua notifikasi berhasil ditandai sudah dibaca.');
    }

    /**
     * Mendapatkan admin yang sedang login atau admin pertama sebagai cadangan.
     */
    private function currentAdmin(): User
    {
        // Prioritaskan pengguna terautentikasi bila merupakan admin.
        $user = auth()->user();

        if ($user instanceof User && $user->role === 'admin') {
            return $user;
        }

        // Gunakan akun admin pertama agar halaman tetap memiliki pemilik notifikasi yang valid.
        return User::query()
            ->where('role', 'admin')
            ->orderBy('name')
            ->firstOrFail();
    }

    /**
     * Memastikan notifikasi terkait dengan model dan ID admin yang benar.
     *
     * @param  DatabaseNotification  $notification  Notifikasi yang akan diakses.
     * @param  User  $admin  Admin yang harus memiliki notifikasi.
     */
    private function ensureNotificationOwnership(DatabaseNotification $notification, User $admin): void
    {
        // Samarkan notifikasi milik pengguna lain sebagai resource yang tidak ditemukan.
        abort_if(
            $notification->notifiable_type !== User::class || (int) $notification->notifiable_id !== $admin->id,
            404,
        );
    }
}
