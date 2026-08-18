<?php

namespace App\Support;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Support\Facades\Request;

class ActivityLogger
{
    public static function log(string $event, string $title, ?string $description = null, ?User $user = null): ?ActivityLog
    {
        $targetUser = $user ?? auth()->user();

        if (! $targetUser instanceof User) {
            return null;
        }

        return ActivityLog::query()->create([
            'user_id' => $targetUser->id,
            'role' => $targetUser->role ?? 'pegawai',
            'event' => $event,
            'title' => $title,
            'description' => $description,
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
        ]);
    }
}
