<?php

namespace App\Listeners;

use App\Support\ActivityLogger;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;

class LogAuthenticationEventListener
{
    public function handleLogin(Login $event): void
    {
        if ($event->user) {
            $name = $event->user->name ?? 'Pengguna';
            $role = ucfirst($event->user->role ?? 'pegawai');

            ActivityLogger::log(
                'login',
                'Login Sistem',
                "Pengguna {$name} ({$role}) berhasil masuk ke dalam sistem.",
                $event->user
            );
        }
    }

    public function handleLogout(Logout $event): void
    {
        if ($event->user) {
            $name = $event->user->name ?? 'Pengguna';
            $role = ucfirst($event->user->role ?? 'pegawai');

            ActivityLogger::log(
                'logout',
                'Logout Sistem',
                "Pengguna {$name} ({$role}) keluar dari sistem.",
                $event->user
            );
        }
    }
}
