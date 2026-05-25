<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

/**
 * @property int $id
 * @property int $category_id
 * @property int $location_id
 * @property string $name
 * @property string $code
 * @property string|null $note
 * @property string|null $image_path
 * @property string|null $serial_number
 * @property string|null $size
 * @property string|null $material
 * @property string $condition
 * @property string $status
 * @property int $quantity
 * @property string|float|null $acquisition_price
 * @property int|null $acquisition_year
 * @property \Illuminate\Support\Carbon|null $acquired_at
 */
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
        'serial_number',
        'size',
        'material',
        'condition',
        'status',
        'quantity',
        'acquisition_price',
        'acquisition_year',
        'acquired_at',
    ];

    protected function casts(): array
    {
        return [
            'acquired_at' => 'date',
            'acquisition_price' => 'decimal:2',
            'acquisition_year' => 'integer',
            'quantity' => 'integer',
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

    public function beritaAcaras(): HasMany
    {
        return $this->hasMany(BeritaAcara::class);
    }

    public function hasImage(): bool
    {
        return filled($this->image_path);
    }

    public function imageUrl(): ?string
    {
        if (! $this->hasImage()) {
            return null;
        }

        $path = (string) $this->image_path;

        if (str_starts_with($path, 'asset-images/')) {
            return $this->publicFileUrl($path);
        }

        return asset($path);
    }

    public function getRouteKeyName(): string
    {
        return 'code';
    }

    private function publicDisk(): FilesystemAdapter
    {
        /** @var FilesystemAdapter $disk */
        $disk = Storage::disk('public');

        return $disk;
    }

    private function publicFileUrl(string $path): string
    {
        if (Route::has('profile-media.show')) {
            return route('profile-media.show', ['path' => $path]);
        }

        return $this->publicDisk()->url($path);
    }
}
