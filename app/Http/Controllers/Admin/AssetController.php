<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Models\Category;
use App\Models\Location;
use App\Support\AssetStateService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class AssetController extends Controller
{
    public function __construct(
        private readonly AssetStateService $assetStateService,
    ) {
    }

    public function index(Request $request)
    {
        $editId = $request->integer('edit');
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'location_id' => ['nullable', 'exists:locations,id'],
            'condition' => ['nullable', Rule::in($this->conditionOptions())],
            'status' => ['nullable', Rule::in($this->statusOptions())],
        ]);

        $assets = Asset::query()
            ->with(['category', 'location'])
            ->when(! $editId, fn ($query) => $query->where('quantity', '>', 0))
            ->when($editId, fn ($query, int $id) => $query->whereKey($id))
            ->when($filters['search'] ?? null, function ($query, string $search) {
                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', '%'.$search.'%')
                        ->orWhere('code', 'like', '%'.$search.'%')
                        ->orWhere('note', 'like', '%'.$search.'%');
                });
            })
            ->when($filters['category_id'] ?? null, fn ($query, string $categoryId) => $query->where('category_id', $categoryId))
            ->when($filters['location_id'] ?? null, fn ($query, string $locationId) => $query->where('location_id', $locationId))
            ->when($filters['condition'] ?? null, fn ($query, string $condition) => $query->where('condition', $condition))
            ->when($filters['status'] ?? null, fn ($query, string $status) => $query->where('status', $status))
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString()
            ->through(function (Asset $asset) {
                $resolvedState = $this->assetStateService->resolveState($asset);

                return [
                    'id' => $asset->id,
                    'name' => $asset->name,
                    'code' => $asset->code,
                    'note' => $asset->note,
                    'serial_number' => $asset->serial_number,
                    'size' => $asset->size,
                    'material' => $asset->material,
                    'avatar_type' => $asset->hasImage() ? 'image' : 'initial',
                    'avatar_value' => $asset->imageUrl() ?: Str::upper(Str::substr($asset->name, 0, 1)),
                    'category' => $asset->category?->name,
                    'category_note' => $asset->category?->description ?? 'Kategori aset aktif pada sistem inventaris.',
                    'location' => $asset->location?->name,
                    'location_note' => $asset->location?->address ?? 'Lokasi aset tersimpan pada sistem.',
                    'condition' => $resolvedState['condition'],
                    'condition_variant' => $this->conditionVariant($resolvedState['condition']),
                    'status' => $resolvedState['status'],
                    'status_variant' => $this->statusVariant($resolvedState['status']),
                    'quantity' => $asset->quantity,
                    'price' => 'Rp ' . number_format((float) $asset->acquisition_price, 0, ',', '.'),
                    'acquisition_year' => $asset->acquisition_year ?: optional($asset->acquired_at)->format('Y'),
                    'category_id' => $asset->category_id,
                    'location_id' => $asset->location_id,
                    'edit_condition' => $asset->condition,
                    'edit_status' => $asset->status,
                    'acquisition_price' => $asset->acquisition_price,
                    'has_image' => $asset->hasImage(),
                    'image_url' => $asset->imageUrl(),
                ];
            });

        return view('admin.assets.index', [
            'assets' => $assets,
            'categories' => Category::query()->orderBy('name')->get(),
            'locations' => Location::query()->orderBy('name')->get(),
            'conditions' => $this->conditionOptions(),
            'statuses' => $this->statusOptions(),
            'filters' => $filters,
            'hasActiveFilters' => collect($filters)->filter(fn ($value) => filled($value))->isNotEmpty(),
        ]);
    }

    public function create()
    {
        return redirect()->route('admin.assets.index', ['create' => 1]);
    }

    public function store(Request $request)
    {
        $validated = $this->validateAsset($request);
        unset($validated['image_file'], $validated['remove_image']);

        if ($request->hasFile('image_file')) {
            $validated['image_path'] = $request->file('image_file')->store('asset-images', 'public');
        }

        Asset::query()->create($validated);

        return redirect()
            ->route('admin.assets.index')
            ->with('success', 'Aset "' . $validated['name'] . '" berhasil disimpan.');
    }

    public function edit(Asset $asset)
    {
        return redirect()->route('admin.assets.index', ['edit' => $asset->id]);
    }

    public function update(Request $request, Asset $asset)
    {
        $previousCondition = $asset->condition;
        $validated = $this->validateAsset($request, $asset);
        unset($validated['image_file'], $validated['remove_image']);

        if ($request->boolean('remove_image')) {
            $this->deleteStoredAssetImage($asset->image_path);
            $validated['image_path'] = null;
        }

        if ($request->hasFile('image_file')) {
            $this->deleteStoredAssetImage($asset->image_path);
            $validated['image_path'] = $request->file('image_file')->store('asset-images', 'public');
        }

        $asset->update($validated);
        $this->assetStateService->mergeAssetAfterManualUpdate($asset, $previousCondition);

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
        $this->deleteStoredAssetImage($asset->image_path);
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
        return ['Tersedia', 'Dipinjam', 'Perbaikan'];
    }

    private function validateAsset(Request $request, ?Asset $asset = null): array
    {
        return $request->validate([
            'category_id' => ['required', 'exists:categories,id'],
            'location_id' => ['required', 'exists:locations,id'],
            'name' => ['required', 'string', 'max:100'],
            'code' => ['required', 'string', 'max:50', Rule::unique('assets', 'code')->ignore($asset?->id)],
            'note' => ['nullable', 'string', 'max:255'],
            'image_file' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'remove_image' => ['nullable', 'boolean'],
            'serial_number' => ['nullable', 'string', 'max:100'],
            'size' => ['nullable', 'string', 'max:100'],
            'material' => ['nullable', 'string', 'max:100'],
            'condition' => ['required', Rule::in($this->conditionOptions())],
            'status' => ['required', Rule::in($this->statusOptions())],
            'quantity' => ['required', 'integer', 'min:1'],
            'acquisition_price' => ['required', 'numeric', 'min:0'],
            'acquisition_year' => ['nullable', 'integer', 'min:1900', 'max:' . ((int) date('Y') + 1)],
            'acquired_at' => ['nullable', 'date'],
        ]);
    }

    private function deleteStoredAssetImage(?string $path): void
    {
        if (! str_starts_with((string) $path, 'asset-images/')) {
            return;
        }

        Storage::disk('public')->delete($path);
    }
}
