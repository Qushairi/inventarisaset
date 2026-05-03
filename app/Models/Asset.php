<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Asset extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id',
        'location_id',
        'name',
        'code',
        'note',
        'image_path',
        'condition',
        'status',
        'acquisition_price',
        'acquired_at',
    ];

    protected function casts(): array
    {
        return [
            'acquired_at' => 'date',
            'acquisition_price' => 'decimal:2',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function loans(): HasMany
    {
        return $this->hasMany(Loan::class);
    }

    public function returns(): HasMany
    {
        return $this->hasMany(AssetReturn::class);
    }

    public function getRouteKeyName(): string
    {
        return 'code';
    }
}
