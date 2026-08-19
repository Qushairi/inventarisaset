<?php

namespace App\Http\Controllers\Pegawai;

use App\Models\Asset;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Menampilkan katalog aset yang dapat dilihat oleh pegawai.
 */
class AssetController extends BasePegawaiController
{
    /**
     * Menampilkan daftar aset aktif dalam bentuk data siap-render.
     */
    public function index(Request $request)
    {
        // Batasi jumlah baris per halaman pada pilihan yang didukung antarmuka.
        $perPage = $this->perPage($request);
        $search = $request->query('search');
        $category = $request->query('category');
        $location = $request->query('location');
        $condition = $request->query('condition');
        $status = $request->query('status');
        $year = $request->query('year');

        $query = Asset::query()->with(['category', 'location']);

        if (filled($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhere('serial_number', 'like', "%{$search}%");
            });
        }

        if (filled($category)) {
            $query->whereHas('category', function ($q) use ($category) {
                $q->where('name', $category);
            });
        }

        if (filled($location)) {
            $query->whereHas('location', function ($q) use ($location) {
                $q->where('name', $location);
            });
        }

        if (filled($condition)) {
            $query->where('condition', $condition);
        }

        if (filled($status)) {
            $query->where('status', $status);
        }

        if (filled($year)) {
            $query->where(function ($q) use ($year) {
                $q->where('acquisition_year', $year)
                    ->orWhereYear('acquired_at', $year);
            });
        }

        $assets = $query->orderBy('name')
            ->paginate($perPage)
            ->withQueryString()
            ->through(function (Asset $asset) {
                // Normalisasi nilai utama menjadi string untuk kebutuhan tampilan.
                $name = (string) $asset->getAttribute('name');
                $condition = (string) $asset->getAttribute('condition');
                $status = (string) $asset->getAttribute('status');

                // Ubah model menjadi struktur presentasi yang langsung dipakai view.
                return [
                    'id' => $asset->id,
                    'name' => $name,
                    'code' => $asset->getAttribute('code'),
                    'note' => $asset->getAttribute('note'),
                    'serial_number' => $asset->getAttribute('serial_number'),
                    'size' => $asset->getAttribute('size'),
                    'material' => $asset->getAttribute('material'),
                    'quantity' => max(1, (int) ($asset->quantity ?: 1)),
                    'avatar_type' => $asset->hasImage() ? 'image' : 'initial',
                    'avatar_value' => $asset->imageUrl() ?: Str::upper(Str::substr($name, 0, 1)),
                    'category' => $asset->category?->name,
                    'category_note' => $asset->category?->description ?? 'Kategori aset inventaris.',
                    'location' => $asset->location?->name,
                    'location_note' => $asset->location?->address ?? 'Lokasi aset tersimpan.',
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
                    'is_borrowable' => $status === 'Tersedia' && max(1, (int) ($asset->quantity ?: 1)) > 0,
                    'price' => 'Rp '.number_format((float) $asset->getAttribute('acquisition_price'), 0, ',', '.'),
                    'acquisition_year' => $asset->getAttribute('acquisition_year') ?: optional($asset->getAttribute('acquired_at'))->format('Y'),
                ];
            });

        $categoryOptions = \App\Models\Category::query()->orderBy('name')->pluck('name')->filter()->values()->all();
        $locationOptions = \App\Models\Location::query()->orderBy('name')->pluck('name')->filter()->values()->all();
        $conditionOptions = Asset::query()->whereNotNull('condition')->where('condition', '!=', '')->distinct()->pluck('condition')->filter()->values()->all();
        $statusOptions = Asset::query()->whereNotNull('status')->where('status', '!=', '')->distinct()->pluck('status')->filter()->values()->all();

        $yearOptions = Asset::query()
            ->whereNotNull('acquisition_year')
            ->orWhereNotNull('acquired_at')
            ->get(['acquisition_year', 'acquired_at'])
            ->map(fn ($asset) => (string) ($asset->acquisition_year ?: optional($asset->acquired_at)->format('Y')))
            ->filter()
            ->unique()
            ->sortDesc()
            ->values()
            ->all();

        $totalAssetsCount = Asset::query()->count();
        $availableAssetsCount = Asset::query()->where('status', 'Tersedia')->sum('quantity') ?: Asset::query()->where('status', 'Tersedia')->count();
        $borrowedAssetsCount = Asset::query()->where('status', 'Dipinjam')->count();
        return view('pegawai.assets.index', $this->layoutData([
            'assets' => $assets,
            'totalAssetsCount' => $totalAssetsCount,
            'availableAssetsCount' => $availableAssetsCount,
            'borrowedAssetsCount' => $borrowedAssetsCount,
            'categoryOptions' => $categoryOptions,
            'locationOptions' => $locationOptions,
            'conditionOptions' => $conditionOptions,
            'statusOptions' => $statusOptions,
            'yearOptions' => $yearOptions,
            'selectedSearch' => $search,
            'selectedCategory' => $category,
            'selectedLocation' => $location,
            'selectedCondition' => $condition,
            'selectedStatus' => $status,
            'selectedYear' => $year,
        ]));
    }

    /**
     * Menentukan ukuran halaman yang aman dari parameter query.
     */
    private function perPage(Request $request): int
    {
        // Gunakan 10 saat parameter tidak diberikan.
        $perPage = (int) $request->query('per_page', 10);

        // Abaikan ukuran arbitrer agar beban query tetap terkendali.
        return in_array($perPage, [10, 25, 50], true) ? $perPage : 10;
    }
}
