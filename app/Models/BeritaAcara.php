<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BeritaAcara extends Model
{
    use HasFactory;

    protected $fillable = [
        'loan_id',
        'asset_id',
        'first_party_user_id',
        'second_party_user_id',
        'number',
        'verification_token',
        'location',
        'asset_condition',
        'handover_statement',
        'closing_statement',
        'pdf_path',
        'issued_at',
        'pdf_generated_at',
    ];

    protected function casts(): array
    {
        return [
            'issued_at' => 'datetime',
            'pdf_generated_at' => 'datetime',
        ];
    }

    public function loan(): BelongsTo
    {
        return $this->belongsTo(Loan::class);
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    public function firstParty(): BelongsTo
    {
        return $this->belongsTo(User::class, 'first_party_user_id');
    }

    public function secondParty(): BelongsTo
    {
        return $this->belongsTo(User::class, 'second_party_user_id');
    }
}
