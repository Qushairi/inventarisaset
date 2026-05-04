<?php

namespace App\Support;

use App\Models\User;

class DashboardRedirector
{
    public static function routeNameFor(?User $user): string
    {
        return match ($user?->role) {
            'admin' => 'admin.dashboard',
            'pegawai' => 'pegawai.dashboard',
            default => 'login',
        };
    }

    public static function pathFor(?User $user): string
    {
        return route(self::routeNameFor($user), absolute: false);
    }
}
