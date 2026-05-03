<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Models\Category;
use App\Models\Location;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class AssetController extends Controller
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
                    'condition_variant' => $this->conditionVariant($asset->condition),
                    'status' => $asset->status,
                    'status_variant' => $this->statusVariant($asset->status),
                    'price' => 'Rp ' . number_format((float) $asset->acquisition_price, 0, ',', '.'),
                    'acquired_at' => optional($asset->acquired_at)->format('d/m/Y'),
                ];
            });

        return view('admin.assets.index', [
            'assets' => $assets,
        ]);
    }

    public function create()
    {
        return view('admin.assets.create', [
            'categories' => Category::query()->orderBy('name')->get(),
            'locations' => Location::query()->orderBy('name')->get(),
            'conditions' => $this->conditionOptions(),
            'statuses' => $this->statusOptions(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validateAsset($request);

        Asset::query()->create($validated);

        return redirect()
            ->route('admin.assets.index')
            ->with('success', 'Aset "' . $validated['name'] . '" berhasil disimpan.');
    }

    public function edit(Asset $asset)
    {
        return view('admin.assets.edit', [
            'asset' => $asset,
            'categories' => Category::query()->orderBy('name')->get(),
            'locations' => Location::query()->orderBy('name')->get(),
            'conditions' => $this->conditionOptions(),
            'statuses' => $this->statusOptions(),
        ]);
    }

    public function update(Request $request, Asset $asset)
    {
        $validated = $this->validateAsset($request, $asset);

        $asset->update($validated);

        return redirect()
            ->route('admin.assets.index')
            ->with('success', 'Aset "' . $validated['name'] . '" berhasil diperbarui.');
    }

    public function destroy(Asset $asset)
    {
        if ($asset->loans()->exists() || $asset->returns()->exists()) {
            return redirect()
                ->route('admin.assets.index')
                ->with('error', 'Aset tidak bisa dihapus karena sudah memiliki riwayat peminjaman atau pengembalian.');
        }

        $name = $asset->name;
        $asset->delete();

        return redirect()
            ->route('admin.assets.index')
            ->with('success', 'Aset "' . $name . '" berhasil dihapus.');
    }

    private function conditionVariant(string $condition): string
    {
        return match ($condition) {
            'Rusak Ringan' => 'warning',
            'Rusak Berat' => 'danger',
            default => 'success',
        };
    }

    private function statusVariant(string $status): string
    {
        return match ($status) {
            'Dipinjam' => 'warning',
            'Perbaikan' => 'danger',
            'Diverifikasi' => 'info',
            default => 'success',
        };
    }

    private function conditionOptions(): array
    {
        return ['Baik', 'Rusak Ringan', 'Rusak Berat'];
    }

    private function statusOptions(): array
    {
        return ['Tersedia', 'Dipinjam', 'Perbaikan', 'Diverifikasi'];
    }

    private function validateAsset(Request $request, ?Asset $asset = null): array
    {
        return $request->validate([
            'category_id' => ['required', 'exists:categories,id'],
            'location_id' => ['required', 'exists:locations,id'],
            'name' => ['required', 'string', 'max:100'],
            'code' => ['required', 'string', 'max:50', Rule::unique('assets', 'code')->ignore($asset?->id)],
            'note' => ['nullable', 'string', 'max:255'],
            'image_path' => ['nullable', 'string', 'max:255'],
            'condition' => ['required', Rule::in($this->conditionOptions())],
            'status' => ['required', Rule::in($this->statusOptions())],
            'acquisition_price' => ['required', 'numeric', 'min:0'],
            'acquired_at' => ['nullable', 'date'],
        ]);
    }
}
