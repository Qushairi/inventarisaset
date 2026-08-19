<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AssetImportRequest;
use App\Models\Asset;
use App\Models\Category;
use App\Models\Location;
use App\Support\AssetStateService;
use App\Support\KirAssetImportService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Menangani penayangan dan operasi CRUD data aset pada area admin.
 */
class AssetController extends Controller
{
    /**
     * Menyediakan layanan penyelarasan kondisi dan status aset.
     */
    public function __construct(
        private readonly AssetStateService $assetStateService,
    ) {}

    /**
     * Menampilkan daftar aset beserta filter dan data referensi formulir.
     */
    public function index(Request $request): View
    {
        // Parameter edit dipakai untuk menampilkan satu aset pada mode penyuntingan.
        $editId = $request->integer('edit');

        // Validasi seluruh filter sebelum nilainya diterapkan ke kueri aset.
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'location_id' => ['nullable', 'exists:locations,id'],
            'condition' => ['nullable', Rule::in($this->conditionOptions())],
            'status' => ['nullable', Rule::in($this->statusOptions())],
        ]);

        // Susun data aset yang telah difilter dalam bentuk paginator.
        $assets = $this->paginatedAssets($filters, $editId);

        // Kirim daftar aset, pilihan referensi, dan status filter ke tampilan.
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

    /**
     * Membentuk paginator aset berdasarkan filter dan konteks penyuntingan.
     *
     * @param  array<string, mixed>  $filters
     */
    private function paginatedAssets(array $filters, int $editId): LengthAwarePaginator
    {
        // Muat relasi kategori dan lokasi sekaligus untuk mencegah kueri berulang.
        $query = Asset::query()->with(['category', 'location']);

        // Mode edit tetap mengambil aset target, sedangkan daftar umum hanya menampilkan stok positif.
        if ($editId) {
            $query->whereKey($editId);
        } else {
            $query->where('quantity', '>', 0);
        }

        // Terapkan pencarian dan filter pilihan pada kueri dasar.
        $this->applyAssetFilters($query, $filters);

        // Setiap record aset ditampilkan terpisah agar perbedaan spesifikasi tetap terlihat.
        return $query
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString()
            ->through(fn (Asset $asset) => $this->assetRow($asset));
    }

    /**
     * Menerapkan filter pencarian dan atribut pada kueri aset.
     *
     * @param  array<string, mixed>  $filters
     */
    private function applyAssetFilters(Builder $query, array $filters): void
    {
        // Kelompokkan pencarian bebas agar cocok dengan nama, kode, atau catatan.
        if (filled($filters['search'] ?? null)) {
            $search = (string) $filters['search'];

            $query->where(function (Builder $query) use ($search): void {
                $query->where('name', 'like', '%'.$search.'%')
                    ->orWhere('code', 'like', '%'.$search.'%')
                    ->orWhere('note', 'like', '%'.$search.'%');
            });
        }

        // Filter pilihan menggunakan kecocokan tepat pada kolom terkait.
        foreach (['category_id', 'location_id', 'condition', 'status'] as $field) {
            if (filled($filters[$field] ?? null)) {
                $query->where($field, $filters[$field]);
            }
        }
    }

    /**
     * Mengubah model aset menjadi struktur data yang dibutuhkan tabel admin.
     *
     * @return array<string, mixed>
     */
    private function assetRow(Asset $asset, $group = null, bool $includeRecords = true): array
    {
        $group ??= collect([$asset]);

        // Gunakan layanan status agar kondisi dan status yang ditampilkan sudah tersinkronisasi.
        $resolvedState = $this->assetStateService->resolveState($asset);

        // Sediakan nilai tampilan, varian badge, dan nilai asli untuk formulir edit.
        return [
            'id' => $asset->id,
            'name' => $asset->name,
            'code' => $asset->code,
            'brand_model' => $asset->brand_model,
            'note' => $asset->note,
            'details' => $group->map(fn (Asset $item) => collect([
                filled($item->brand_model) ? 'Merk/Model: '.$item->brand_model : null,
                filled($item->serial_number) ? 'No. Seri Pabrik: '.$item->serial_number : null,
                'Ukuran: '.($item->size ?: '-').' | Bahan: '.($item->material ?: '-'),
                filled($item->note) ? 'Keterangan: '.$item->note : null,
            ])->filter()->join(' · '))->unique()->values()->all(),
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
            'quantity' => $group->sum('quantity'),
            'price' => 'Rp '.number_format((float) $asset->acquisition_price, 0, ',', '.'),
            'acquisition_year' => $asset->acquisition_year ?: optional($asset->acquired_at)->format('Y'),
            'acquisitions' => $group->map(fn (Asset $item) => [
                'price' => 'Rp '.number_format((float) $item->acquisition_price, 0, ',', '.'),
                'year' => $item->acquisition_year ?: optional($item->acquired_at)->format('Y'),
            ])->unique(fn (array $item) => $item['price'].'|'.$item['year'])->values()->all(),
            'category_id' => $asset->category_id,
            'location_id' => $asset->location_id,
            'edit_condition' => $asset->condition,
            'edit_status' => $asset->status,
            'acquisition_price' => $asset->acquisition_price,
            'has_image' => $asset->hasImage(),
            'image_url' => $asset->imageUrl(),
            'records' => $includeRecords
                ? $group->map(fn (Asset $item) => $this->assetRow($item, collect([$item]), false))->values()->all()
                : [],
        ];
    }

    /**
     * Mengarahkan halaman pembuatan ke daftar aset dengan penanda formulir baru.
     */
    public function create()
    {
        // Form tambah ditampilkan pada halaman indeks melalui parameter create.
        return redirect()->route('admin.assets.index', ['create' => 1]);
    }

    /**
     * Memvalidasi dan menyimpan aset baru.
     */
    public function store(Request $request)
    {
        // Validasi data aset menggunakan aturan yang dipakai bersama proses pembaruan.
        $validated = $this->validateAsset($request);

        // Hapus field kontrol unggahan karena keduanya bukan kolom model aset.
        unset($validated['image_file'], $validated['remove_image']);

        // Simpan gambar baru ke disk publik jika pengguna mengunggah berkas.
        if ($request->hasFile('image_file')) {
            $validated['image_path'] = $request->file('image_file')->store('asset-images', 'public');
        }

        // Tambahkan jumlah ke baris lama bila seluruh identitas aset sudah terdaftar.
        $result = $this->assetStateService->addOrMergeAsset($validated);
        $asset = $result['asset'];

        // Unggahan baru yang tidak digunakan saat penggabungan tidak boleh menjadi berkas yatim.
        if ($result['merged']
            && filled($validated['image_path'] ?? null)
            && $asset->image_path !== $validated['image_path']) {
            $this->deleteStoredAssetImage($validated['image_path']);
        }

        $message = $result['merged']
            ? 'Aset "'.$asset->name.'" sudah ada. Jumlah berhasil digabung menjadi '.$asset->quantity.' unit.'
            : 'Aset "'.$asset->name.'" berhasil disimpan.';

        // Kembali ke daftar aset sambil menampilkan notifikasi keberhasilan.
        return redirect()
            ->route('admin.assets.index')
            ->with('success', $message);
    }

    /**
     * Mengimpor seluruh baris aset pada workbook KIR ke kategori yang dipilih.
     */
    public function import(AssetImportRequest $request, KirAssetImportService $importService)
    {
        $file = $request->file('import_file');
        $result = $importService->import($file);

        return redirect()
            ->route('admin.assets.index')
            ->with(
                'success',
                "Impor selesai: {$result['assets']} data aset ({$result['quantity']} barang) "
                ."dari {$result['locations']} lokasi dengan {$result['categories']} kategori otomatis berhasil ditambahkan.",
            );
    }

    /**
     * Mengarahkan penyuntingan ke halaman indeks untuk aset terpilih.
     */
    public function edit(Asset $asset)
    {
        // ID aset pada query string mengaktifkan mode edit di halaman indeks.
        return redirect()->route('admin.assets.index', ['edit' => $asset->id]);
    }

    /**
     * Memvalidasi dan memperbarui data aset beserta gambarnya.
     */
    public function update(Request $request, Asset $asset)
    {
        // Simpan kondisi lama sebagai acuan penyelarasan status setelah pembaruan manual.
        $previousCondition = $asset->condition;

        // Validasi input lalu keluarkan field kontrol yang tidak disimpan langsung.
        $validated = $this->validateAsset($request, $asset);
        unset($validated['image_file'], $validated['remove_image']);

        // Hapus gambar lama dan kosongkan path ketika pengguna meminta penghapusan.
        if ($request->boolean('remove_image')) {
            $this->deleteStoredAssetImage($asset->image_path);
            $validated['image_path'] = null;
        }

        // Ganti gambar lama dengan unggahan baru jika berkas disertakan.
        if ($request->hasFile('image_file')) {
            $this->deleteStoredAssetImage($asset->image_path);
            $validated['image_path'] = $request->file('image_file')->store('asset-images', 'public');
        }

        // Simpan perubahan lalu selaraskan status aset dengan kondisi terbaru.
        $asset->update($validated);
        $this->assetStateService->mergeAssetAfterManualUpdate($asset, $previousCondition);

        // Kembali ke daftar aset dengan pesan bahwa pembaruan berhasil.
        return redirect()
            ->route('admin.assets.index')
            ->with('success', 'Aset "'.$validated['name'].'" berhasil diperbarui.');
    }

    /**
     * Menghapus aset yang belum memiliki riwayat transaksi.
     */
    public function destroy(Asset $asset)
    {
        // Pertahankan integritas riwayat dengan menolak penghapusan aset yang pernah ditransaksikan.
        if ($asset->loans()->exists() || $asset->returns()->exists()) {
            return redirect()
                ->route('admin.assets.index')
                ->with('error', 'Aset tidak bisa dihapus karena sudah memiliki riwayat peminjaman atau pengembalian.');
        }

        // Simpan nama untuk notifikasi, lalu hapus gambar dan record aset.
        $name = $asset->name;
        $this->deleteStoredAssetImage($asset->image_path);
        $asset->delete();

        // Informasikan keberhasilan setelah proses penghapusan selesai.
        return redirect()
            ->route('admin.assets.index')
            ->with('success', 'Aset "'.$name.'" berhasil dihapus.');
    }

    /**
     * Menentukan varian warna badge berdasarkan kondisi aset.
     */
    private function conditionVariant(string $condition): string
    {
        // Kondisi rusak memperoleh penekanan visual sesuai tingkat kerusakannya.
        return match ($condition) {
            'Rusak Ringan' => 'warning',
            'Rusak Berat' => 'danger',
            default => 'success',
        };
    }

    /**
     * Menentukan varian warna badge berdasarkan status aset.
     */
    private function statusVariant(string $status): string
    {
        // Status khusus dipetakan ke warna badge, sementara status normal memakai success.
        return match ($status) {
            'Dipinjam' => 'warning',
            'Perbaikan' => 'danger',
            'Diverifikasi' => 'info',
            default => 'success',
        };
    }

    /**
     * Mengembalikan daftar kondisi aset yang diizinkan.
     *
     * @return array<int, string>
     */
    private function conditionOptions(): array
    {
        return ['Baik', 'Rusak Ringan', 'Rusak Berat'];
    }

    /**
     * Mengembalikan daftar status aset yang dapat dipilih admin.
     *
     * @return array<int, string>
     */
    private function statusOptions(): array
    {
        return ['Tersedia', 'Dipinjam', 'Perbaikan'];
    }

    /**
     * Memvalidasi seluruh atribut aset untuk proses simpan atau pembaruan.
     *
     * @return array<string, mixed>
     */
    private function validateAsset(Request $request, ?Asset $asset = null): array
    {
        // Batasi relasi, format berkas, nilai numerik, serta pilihan status dan kondisi.
        return $request->validate([
            'category_id' => ['required', 'exists:categories,id'],
            'location_id' => ['required', 'exists:locations,id'],
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50'],
            'brand_model' => ['nullable', 'string', 'max:255'],
            'note' => ['nullable', 'string', 'max:255'],
            'image_file' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'remove_image' => ['nullable', 'boolean'],
            'serial_number' => ['nullable', 'string', 'max:255'],
            'size' => ['nullable', 'string', 'max:255'],
            'material' => ['nullable', 'string', 'max:255'],
            'condition' => ['required', Rule::in($this->conditionOptions())],
            'status' => ['required', Rule::in($this->statusOptions())],
            'quantity' => ['required', 'integer', 'min:1'],
            'acquisition_price' => ['required', 'numeric', 'min:0'],
            'acquisition_year' => ['nullable', 'integer', 'min:1900', 'max:'.((int) date('Y') + 1)],
            'acquired_at' => ['nullable', 'date'],
        ]);
    }

    /**
     * Menghapus gambar aset hanya jika path berada di direktori aset yang dikelola aplikasi.
     */
    private function deleteStoredAssetImage(?string $path): void
    {
        // Abaikan path kosong atau path di luar direktori asset-images demi keamanan berkas.
        if (! str_starts_with((string) $path, 'asset-images/')) {
            return;
        }

        // Hapus berkas dari disk publik setelah path dinyatakan sesuai.
        Storage::disk('public')->delete($path);
    }
}
