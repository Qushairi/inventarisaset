<?php

namespace App\Models;

use App\Models\AssetReturn;
use App\Models\Loan;
// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

#[Fillable(['name', 'email', 'password', 'role', 'profile_photo_path', 'signature_path', 'signature_updated_at'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'signature_updated_at' => 'datetime',
        ];
    }

    public function loans(): HasMany
    {
        return $this->hasMany(Loan::class);
    }

    public function returns(): HasMany
    {
        return $this->hasMany(AssetReturn::class);
    }

    public function approvedBeritaAcaras(): HasMany
    {
        return $this->hasMany(BeritaAcara::class, 'first_party_user_id');
    }

    public function receivedBeritaAcaras(): HasMany
    {
        return $this->hasMany(BeritaAcara::class, 'second_party_user_id');
    }

    public function hasProfilePhoto(): bool
    {
        return filled($this->profile_photo_path);
    }

    public function profilePhotoUrl(): ?string
    {
        return $this->hasProfilePhoto()
            ? Storage::disk('public')->url($this->profile_photo_path)
            : null;
    }

    public function hasSignature(): bool
    {
        return filled($this->signature_path);
    }

    public function signatureUrl(): ?string
    {
        return $this->hasSignature()
            ? Storage::disk('public')->url($this->signature_path)
            : null;
    }

    public function initials(): string
    {
        $initials = Str::of($this->name)
            ->explode(' ')
            ->filter()
            ->take(2)
            ->map(fn (string $part) => Str::substr($part, 0, 1))
            ->join('');

        return Str::upper($initials ?: Str::substr($this->name, 0, 1));
    }
}
