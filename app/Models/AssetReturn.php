<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssetReturn extends Model
{
    use HasFactory;

    protected $table = 'asset_returns';

    protected $fillable = [
        'loan_id',
        'asset_id',
        'user_id',
        'returned_at',
        'verified_note',
        'condition',
        'status',
        'status_note',
        'report_number',
        'report_note',
    ];

    protected function casts(): array
    {
        return [
            'returned_at' => 'date',
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

    public function loan(): BelongsTo
    {
        return $this->belongsTo(Loan::class);
    }
}
