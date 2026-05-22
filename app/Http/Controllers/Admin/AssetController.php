<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Models\Category;
use App\Models\Location;
use App\Support\AssetStateService;
use Illuminate\Http\Request;
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
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'location_id' => ['nullable', 'exists:locations,id'],
            'condition' => ['nullable', Rule::in($this->conditionOptions())],
            'status' => ['nullable', Rule::in($this->statusOptions())],
        ]);

        $assets = Asset::query()
            ->with(['category', 'location'])
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
                    'name' => $asset->name,
                    'code' => $asset->code,
                    'note' => $asset->note,
                    'avatar_type' => $asset->image_path ? 'image' : 'initial',
                    'avatar_value' => $asset->image_path ?: Str::upper(Str::substr($asset->name, 0, 1)),
                    'category' => $asset->category?->name,
                    'category_note' => $asset->category?->description ?? 'Kategori aset aktif pada sistem inventaris.',
                    'location' => $asset->location?->name,
                    'location_note' => $asset->location?->address ?? 'Lokasi aset tersimpan pada sistem.',
                    'condition' => $resolvedState['condition'],
                    'condition_variant' => $this->conditionVariant($resolvedState['condition']),
                    'status' => $resolvedState['status'],
                    'status_variant' => $this->statusVariant($resolvedState['status']),
                    'price' => 'Rp ' . number_format((float) $asset->acquisition_price, 0, ',', '.'),
                    'acquired_at' => optional($asset->acquired_at)->format('d/m/Y'),
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
