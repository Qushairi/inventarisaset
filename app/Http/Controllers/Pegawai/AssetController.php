<?php

namespace App\Http\Controllers\Pegawai;

use App\Models\Asset;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AssetController extends BasePegawaiController
{
    public function index(Request $request)
    {
        $perPage = $this->perPage($request);

        $assets = Asset::query()
            ->with(['category', 'location'])
            ->where('quantity', '>', 0)
            ->orderBy('name')
            ->paginate($perPage)
            ->withQueryString()
            ->through(function (Asset $asset) {
                $name = (string) $asset->getAttribute('name');
                $condition = (string) $asset->getAttribute('condition');
                $status = (string) $asset->getAttribute('status');

                return [
                    'name' => $name,
                    'code' => $asset->getAttribute('code'),
                    'note' => $asset->getAttribute('note'),
                    'serial_number' => $asset->getAttribute('serial_number'),
                    'size' => $asset->getAttribute('size'),
                    'material' => $asset->getAttribute('material'),
                    'avatar_type' => $asset->hasImage() ? 'image' : 'initial',
                    'avatar_value' => $asset->imageUrl() ?: Str::upper(Str::substr($name, 0, 1)),
                    'category' => $asset->category?->name,
                    'category_note' => $asset->category?->description ?? 'Kategori aset aktif pada sistem inventaris.',
                    'location' => $asset->location?->name,
                    'location_note' => $asset->location?->address ?? 'Lokasi aset tersimpan pada sistem.',
                    'condition' => $condition,
                    'condition_variant' => match ($condition) {
                        'Rusak Ringan' => 'warning',
                        'Rusak Berat' => 'danger',
                        default => 'success',
                    },
                    'status' => $status,
                    'status_variant' => match ($status) {
                        'Dipinjam' => 'warning',
                        'Perbaikan' => 'danger',
                        'Diverifikasi' => 'info',
                        default => 'success',
                    },
                    'price' => 'Rp ' . number_format((float) $asset->getAttribute('acquisition_price'), 0, ',', '.'),
                    'acquisition_year' => $asset->getAttribute('acquisition_year') ?: optional($asset->getAttribute('acquired_at'))->format('Y'),
                ];
            });

        return view('pegawai.assets.index', $this->layoutData([
            'assets' => $assets,
        ]));
    }

    private function perPage(Request $request): int
    {
        $perPage = (int) $request->query('per_page', 10);

        return in_array($perPage, [10, 25, 50], true) ? $perPage : 10;
    }
}
