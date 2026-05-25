<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Notifications\DatabaseNotification;

class NotificationController extends Controller
{
    public function index()
    {
        $admin = $this->currentAdmin();

        return view('admin.notifications.index', [
            'notifications' => $admin->notifications()->latest()->paginate(12),
            'unreadNotificationCount' => $admin->unreadNotifications()->count(),
        ]);
    }

    public function show(DatabaseNotification $notification): RedirectResponse
    {
        $admin = $this->currentAdmin();

        $this->ensureNotificationOwnership($notification, $admin);

        if (is_null($notification->read_at)) {
            $notification->markAsRead();
        }

        return redirect($notification->data['action_url'] ?? route('admin.notifications.index'));
    }

    public function markAllAsRead(): RedirectResponse
    {
        $admin = $this->currentAdmin();

        $admin->unreadNotifications->markAsRead();

        return redirect()
            ->route('admin.notifications.index')
            ->with('success', 'Semua notifikasi berhasil ditandai sudah dibaca.');
    }

    private function currentAdmin(): User
    {
        $user = auth()->user();

        if ($user instanceof User && $user->role === 'admin') {
            return $user;
        }

        return User::query()
            ->where('role', 'admin')
            ->orderBy('name')
            ->firstOrFail();
    }

    private function ensureNotificationOwnership(DatabaseNotification $notification, User $admin): void
    {
        abort_if(
            $notification->notifiable_type !== User::class || (int) $notification->notifiable_id !== $admin->id,
            404,
        );
    }
}
