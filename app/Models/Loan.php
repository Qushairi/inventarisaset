<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Loan extends Model
{
    use HasFactory;

    protected $fillable = [
        'asset_id',
        'user_id',
        'approved_by_user_id',
        'loan_date',
        'planned_return_date',
        'status',
        'status_note',
        'loan_letter_number',
        'loan_letter_svg',
        'loan_letter_generated_at',
    ];

    protected function casts(): array
    {
        return [
            'loan_date' => 'date',
            'planned_return_date' => 'date',
            'loan_letter_generated_at' => 'datetime',
        ];
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_user_id');
    }

    public function returnRecord(): HasOne
    {
        return $this->hasOne(AssetReturn::class);
    }

    public function beritaAcara(): HasOne
    {
        return $this->hasOne(BeritaAcara::class);
    }

    public function hasLoanLetter(): bool
    {
        if ($this->relationLoaded('beritaAcara') && $this->beritaAcara) {
            return true;
        }

        return $this->beritaAcara()->exists()
            || (filled($this->loan_letter_number) && (filled($this->loan_letter_svg) || filled($this->loan_letter_generated_at)));
    }
}
