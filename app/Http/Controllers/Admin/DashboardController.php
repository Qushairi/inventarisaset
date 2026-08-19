<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Models\Category;
use App\Models\Loan;
use App\Models\Location;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Support\Collection;
use App\Models\AssetReturn;

/**
 * Menyediakan seluruh data ringkasan dan grafik untuk dashboard admin.
 */
class DashboardController extends Controller
{
    /**
     * Menampilkan dashboard admin dengan statistik, grafik, dan daftar aktivitas.
     */
    public function index()
    {
        // Setiap bagian dashboard dibangun oleh method terpisah agar sumber datanya terisolasi.
        return view('admin.dashboard', [
            'statCards' => $this->statCards(),
            'activityChart' => $this->activityChart(),
            'assetConditionChart' => $this->assetConditionChart(),
            'popularAssets' => $this->popularAssets(),
            'returnDueLoans' => $this->returnDueLoans(),
            'maintenanceAssets' => $this->maintenanceAssets(),
            'recentLoans' => $this->recentLoans(),
        ]);
    }

    /**
     * Menyusun kartu statistik utama untuk aset dan transaksi.
     *
     * @return array<int, array<string, mixed>>
     */
    private function statCards(): array
    {
        // Hitung jumlah data langsung dari masing-masing model untuk nilai terkini.
        return [
            [
                'label' => 'Total Aset',
                'value' => Asset::query()->count(),
                'helper' => 'Aset aktif yang tercatat saat ini.',
                'icon' => 'box-seam',
                'variant' => 'primary',
            ],
            [
                'label' => 'Kategori',
                'value' => Category::query()->count(),
                'helper' => 'Kelompok aset yang tersedia.',
                'icon' => 'tags',
                'variant' => 'success',
            ],
            [
                'label' => 'Peminjaman',
                'value' => Loan::query()->count(),
                'helper' => 'Riwayat pengajuan peminjaman aset.',
                'icon' => 'clipboard-check',
                'variant' => 'warning',
            ],
            [
                'label' => 'Pengembalian',
                'value' => AssetReturn::query()->count(),
                'helper' => 'Data pengembalian yang sudah diverifikasi.',
                'icon' => 'arrow-counterclockwise',
                'variant' => 'info',
            ],
        ];
    }

    /**
     * Menyusun seri aktivitas aset dan peminjaman selama lima tahun terakhir.
     *
     * @return array<string, array<int, mixed>>
     */
    private function activityChart(): array
    {
        // Bangun koleksi awal tahun dari empat tahun lalu hingga tahun berjalan.
        $years = collect(range(4, 0))
            ->map(fn(int $offset) => now()->subYears($offset)->startOfYear());

        // Pasangkan label tahun dengan jumlah aset dan peminjaman pada setiap tahun.
        return [
            'labels' => $years
                ->map(fn(Carbon $year) => $year->format('Y'))
                ->all(),
            'asset_series' => $this->buildYearlySeries(Asset::class, 'created_at', $years),
            'loan_series' => $this->buildYearlySeries(Loan::class, 'loan_date', $years),
        ];
    }

    /**
     * Menyusun distribusi jumlah aset berdasarkan kondisi fisiknya.
     *
     * @return array<string, array<int, mixed>>
     */
    private function assetConditionChart(): array
    {
        // Urutan nilai seri harus sama dengan urutan label kondisi.
        return [
            'labels' => ['Baik', 'Rusak Ringan', 'Rusak Berat'],
            'series' => [
                Asset::query()->where('condition', 'Baik')->count(),
                Asset::query()->where('condition', 'Rusak Ringan')->count(),
                Asset::query()->where('condition', 'Rusak Berat')->count(),
            ],
        ];
    }

    /**
     * Mengambil maksimal lima aset yang memerlukan perhatian atau perbaikan.
     *
     * @return Collection<int, array<string, mixed>>
     */
    private function maintenanceAssets(): Collection
    {
        return Asset::query()
            // Muat kategori dan lokasi untuk melengkapi informasi setiap aset.
            ->with(['category', 'location'])
            // Aset masuk daftar bila rusak atau secara eksplisit berstatus perbaikan.
            ->where(function ($query) {
                $query->whereIn('condition', ['Rusak Ringan', 'Rusak Berat'])
                    ->orWhere('status', 'Perbaikan');
            })
            // Dahulukan kerusakan berat, kemudian ringan, sebelum mengurutkan nama.
            ->orderByRaw("case when `condition` = ? then 0 when `condition` = ? then 1 else 2 end", ['Rusak Berat', 'Rusak Ringan'])
            ->orderBy('name')
            ->take(5)
            ->get()
            ->map(function (Asset $asset) {
                // Bentuk data tampilan, termasuk avatar dan varian warna status.
                return [
                    'name' => $asset->name,
                    'code' => $asset->code,
                    'avatar_type' => $asset->hasImage() ? 'image' : 'initial',
                    'avatar_value' => $asset->imageUrl() ?: Str::upper(Str::substr($asset->name, 0, 1)),
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
                        'Perbaikan' => 'danger',
                        'Dipinjam' => 'warning',
                        default => 'info',
                    },
                ];
            });
    }

    /**
     * Mengambil empat peminjaman terbaru untuk panel aktivitas dashboard.
     *
     * @return Collection<int, array<string, mixed>>
     */
    private function recentLoans(): Collection
    {
        return Loan::query()
            // Muat aset dan pegawai sekaligus agar detail transaksi siap ditampilkan.
            ->with(['asset', 'user'])
            ->latest('loan_date')
            ->take(4)
            ->get()
            ->map(function (Loan $loan) {
                // Ubah status bisnis menjadi label data dan varian warna antarmuka.
                return [
                    'asset_name' => $loan->asset?->name,
                    'asset_code' => $loan->asset?->code,
                    'employee_name' => $loan->user?->name,
                    'employee_email' => $loan->user?->email,
                    'loan_date' => optional($loan->loan_date)->format('d/m/Y'),
                    'return_plan' => 'Rencana kembali ' . optional($loan->planned_return_date)->format('d/m/Y'),
                    'status' => $loan->status,
                    'status_variant' => match ($loan->status) {
                        'Ditolak' => 'danger',
                        'Menunggu' => 'warning',
                        default => 'success',
                    },
                ];
            });
    }

    /**
     * Mengambil lima aset yang paling sering dipinjam secara sah.
     *
     * @return Collection<int, array<string, mixed>>
     */
    private function popularAssets(): Collection
    {
        return Asset::query()
            ->with(['category'])
            // Hitung hanya peminjaman yang disetujui atau telah selesai.
            ->withCount([
                'loans as loan_count' => fn($query) => $query->whereIn('status', ['Disetujui', 'Selesai']),
            ])
            // Abaikan aset tanpa riwayat peminjaman yang memenuhi kriteria.
            ->having('loan_count', '>', 0)
            ->orderByDesc('loan_count')
            ->orderBy('name')
            ->take(5)
            ->get()
            ->values()
            ->map(function (Asset $asset, int $index) {
                // Indeks hasil dipakai untuk menentukan peringkat aset.
                return [
                    'rank' => $index + 1,
                    'name' => $asset->name,
                    'code' => $asset->code,
                    'category' => $asset->category?->name ?? 'Tanpa kategori',
                    'loan_count' => $asset->loan_count,
                ];
            });
    }

    /**
     * Mengambil peminjaman yang paling dekat dengan batas waktu pengembalian.
     *
     * @return Collection<int, array<string, mixed>>
     */
    private function returnDueLoans(): Collection
    {
        // Normalisasi waktu hari ini agar perbandingan hanya mempertimbangkan tanggal.
        $today = now()->startOfDay();

        return Loan::query()
            ->with(['asset', 'user'])
            // Hanya peminjaman aktif dengan rencana pengembalian yang perlu dipantau.
            ->where('status', 'Disetujui')
            ->whereNotNull('planned_return_date')
            // Keluarkan pinjaman yang sudah memiliki pengembalian terverifikasi.
            ->whereDoesntHave('returnRecord', function ($query) {
                $query->where('status', 'Terverifikasi');
            })
            ->orderBy('planned_return_date')
            ->take(5)
            ->get()
            ->map(function (Loan $loan) use ($today) {
                // Normalisasi rencana kembali sebelum menentukan apakah sudah terlambat.
                $plannedReturnDate = $loan->planned_return_date
                    ? Carbon::parse($loan->planned_return_date)->startOfDay()
                    : null;
                $isOverdue = $plannedReturnDate?->lt($today) ?? false;
                $isDueToday = $plannedReturnDate?->isSameDay($today) ?? false;

                // Terjemahkan hasil perbandingan tanggal menjadi label dan warna status.
                return [
                    'asset_name' => $loan->asset?->name,
                    'asset_code' => $loan->asset?->code,
                    'employee_name' => $loan->user?->name,
                    'planned_return_date' => optional($loan->planned_return_date)->format('d/m/Y'),
                    'status_label' => $isOverdue ? 'Terlambat' : ($isDueToday ? 'Hari ini' : 'Akan kembali'),
                    'status_variant' => $isOverdue ? 'danger' : ($isDueToday ? 'warning' : 'info'),
                ];
            });
    }

    /**
     * Menghitung jumlah record model tertentu untuk setiap tahun yang diberikan.
     *
     * @param  class-string  $modelClass  Nama class model Eloquent yang akan dihitung.
     * @param  string  $column  Kolom tanggal yang menjadi dasar perhitungan.
     * @param  Collection<int, Carbon>  $years  Daftar awal tahun yang akan dihitung.
     * @return array<int, int>
     */
    private function buildYearlySeries(string $modelClass, string $column, Collection $years): array
    {
        return $years
            ->map(function (Carbon $year) use ($modelClass, $column) {
                // Hitung record yang tanggalnya berada di awal hingga akhir tahun terkait.
                return $modelClass::query()
                    ->whereBetween($column, [
                        $year->copy()->startOfYear(),
                        $year->copy()->endOfYear(),
                    ])
                    ->count();
            })
            ->all();
    }
}
