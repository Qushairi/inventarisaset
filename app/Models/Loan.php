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
        'loan_date',
        'planned_return_date',
        'status',
        'status_note',
    ];

    protected function casts(): array
    {
        return [
            'loan_date' => 'date',
            'planned_return_date' => 'date',
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

    public function returnRecord(): HasOne
    {
        return $this->hasOne(AssetReturn::class);
    }
}
