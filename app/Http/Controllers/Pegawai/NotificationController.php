<?php

namespace App\Http\Controllers\Pegawai;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Notifications\DatabaseNotification;

class NotificationController extends BasePegawaiController
{
    public function index()
    {
        $pegawai = $this->currentPegawai();

        return view('pegawai.notifications.index', $this->layoutData([
            'notifications' => $pegawai->notifications()->latest()->paginate(12),
            'unreadNotificationCount' => $pegawai->unreadNotifications()->count(),
        ]));
    }

    public function show(DatabaseNotification $notification): RedirectResponse
    {
        $pegawai = $this->currentPegawai();

        $this->ensureNotificationOwnership($notification, $pegawai);

        if (is_null($notification->read_at)) {
            $notification->markAsRead();
        }

        return redirect($notification->data['action_url'] ?? route('pegawai.notifications.index'));
    }

    public function markAllAsRead(): RedirectResponse
    {
        $pegawai = $this->currentPegawai();

        $pegawai->unreadNotifications->markAsRead();

        return redirect()
            ->route('pegawai.notifications.index')
            ->with('success', 'Semua notifikasi berhasil ditandai sudah dibaca.');
    }

    private function ensureNotificationOwnership(DatabaseNotification $notification, User $pegawai): void
    {
        abort_if(
            $notification->notifiable_type !== User::class || (int) $notification->notifiable_id !== $pegawai->id,
            404,
        );
    }
}
