<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'role',
        'event',
        'title',
        'description',
        'ip_address',
        'user_agent',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getEventBadgeAttribute(): string
    {
        return match ($this->event) {
            'login' => 'bg-light-success text-success',
            'logout' => 'bg-light-secondary text-secondary',
            'loan_created' => 'bg-light-primary text-primary',
            'return_created' => 'bg-light-info text-info',
            'profile_updated' => 'bg-light-warning text-warning',
            'password_changed' => 'bg-light-danger text-danger',
            default => 'bg-light-primary text-primary',
        };
    }

    public function getEventLabelAttribute(): string
    {
        return match ($this->event) {
            'login' => 'Login',
            'logout' => 'Logout',
            'loan_created' => 'Peminjaman',
            'return_created' => 'Pengembalian',
            'profile_updated' => 'Foto Profil',
            'password_changed' => 'Ganti Password',
            default => ucfirst(str_replace('_', ' ', $this->event)),
        };
    }
}
