<?php

namespace App\Http\Controllers\Pegawai;

use App\Models\Asset;
use Illuminate\Support\Str;

class AssetController extends BasePegawaiController
{
    public function index()
    {
        $assets = Asset::query()
            ->with(['category', 'location'])
            ->orderBy('name')
            ->paginate(10)
            ->through(function (Asset $asset) {
                return [
                    'name' => $asset->name,
                    'code' => $asset->code,
                    'note' => $asset->note,
                    'avatar_type' => $asset->image_path ? 'image' : 'initial',
                    'avatar_value' => $asset->image_path ?: Str::upper(Str::substr($asset->name, 0, 1)),
                    'category' => $asset->category?->name,
                    'category_note' => $asset->category?->description ?? 'Kategori aset aktif pada sistem inventaris.',
                    'location' => $asset->location?->name,
                    'location_note' => $asset->location?->address ?? 'Lokasi aset tersimpan pada sistem.',
                    'condition' => $asset->condition,
                    'condition_variant' => match ($asset->condition) {
                        'Rusak Ringan' => 'warning',
                        'Rusak Berat' => 'danger',
                        default => 'success',
                    },
                    'status' => $asset->status,
                    'status_variant' => match ($asset->status) {
                        'Dipinjam' => 'warning',
                        'Perbaikan' => 'danger',
                        'Diverifikasi' => 'info',
                        default => 'success',
                    },
                    'price' => 'Rp ' . number_format((float) $asset->acquisition_price, 0, ',', '.'),
                    'acquired_at' => optional($asset->acquired_at)->format('d/m/Y'),
                ];
            });

        return view('pegawai.assets.index', $this->layoutData([
            'assets' => $assets,
        ]));
    }
}
