<?php

namespace App\Http\Controllers\Pegawai;

use App\Models\Asset;
use App\Models\AssetReturn;
use App\Models\Loan;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Menyusun ringkasan statistik dan aktivitas untuk dashboard pegawai.
 */
class DashboardController extends BasePegawaiController
{
    /**
     * Menampilkan metrik, grafik enam bulan, dan aktivitas terbaru pegawai.
     */
    public function index()
    {
        // Gunakan akun aktif sebagai batas data peminjaman dan pengembalian pribadi.
        $pegawai = $this->currentPegawai();

        // Hitung nilai ringkas untuk kartu statistik di bagian atas dashboard.
        $assetTotal = Asset::query()->count();
        $availableAssetTotal = Asset::query()->where('status', 'Tersedia')->count();
        $loanTotal = Loan::query()->where('user_id', $pegawai->id)->count();
        $returnTotal = AssetReturn::query()->where('user_id', $pegawai->id)->count();

        // Susun enam awal bulan, dari lima bulan lalu hingga bulan berjalan.
        $months = collect(range(5, 0))
            ->map(fn (int $offset) => now()->subMonths($offset)->startOfMonth());

        // Ubah objek bulan menjadi label singkat lokal untuk sumbu grafik.
        $chartLabels = $months
            ->map(fn (Carbon $month) => $month->translatedFormat('M'))
            ->all();

        // Hitung jumlah peminjaman pegawai pada masing-masing bulan.
        $loanTrend = $this->buildMonthlySeries(
            Loan::query()->where('user_id', $pegawai->id),
            'loan_date',
            $months,
        );

        // Hitung jumlah pengembalian pegawai pada rentang bulan yang sama.
        $returnTrend = $this->buildMonthlySeries(
            AssetReturn::query()->where('user_id', $pegawai->id),
            'returned_at',
            $months,
        );

        // Ambil empat aset terbaru beserta kategori dan lokasinya.
        $recentAssets = Asset::query()
            ->with(['category', 'location'])
            ->latest()
            ->take(4)
            ->get()
            ->map(function (Asset $asset) {
                // Bentuk nilai tampilan, avatar, serta variasi warna status.
                return [
                    'name' => $asset->name,
                    'code' => $asset->code,
                    'avatar_type' => $asset->hasImage() ? 'image' : 'initial',
                    'avatar_value' => $asset->imageUrl() ?: Str::upper(Str::substr($asset->name, 0, 1)),
                    'category' => $asset->category?->name,
                    'category_note' => $asset->category?->description ?? 'Kategori aset aktif pada sistem inventaris.',
                    'location' => $asset->location?->name,
                    'location_note' => $asset->location?->address ?? 'Lokasi aset tersimpan pada sistem.',
                    'status' => $asset->status,
                    'status_variant' => match ($asset->status) {
                        'Dipinjam' => 'warning',
                        'Perbaikan' => 'danger',
                        'Diverifikasi' => 'info',
                        default => 'success',
                    },
                ];
            });

        // Ambil empat peminjaman terbaru milik pegawai untuk ringkasan aktivitas.
        $recentLoans = Loan::query()
            ->with('asset')
            ->where('user_id', $pegawai->id)
            ->latest('created_at')
            ->latest('id')
            ->take(4)
            ->get()
            ->map(function (Loan $loan) {
                // Format tanggal dan status agar view tidak perlu membaca model langsung.
                return [
                    'asset_name' => $loan->asset?->name,
                    'asset_code' => $loan->asset?->code,
                    'loan_date' => optional($loan->loan_date)->translatedFormat('d F Y'),
                    'return_plan' => 'Rencana kembali ' . optional($loan->planned_return_date)->translatedFormat('d F Y'),
                    'status' => $loan->status,
                    'status_variant' => match ($loan->status) {
                        'Ditolak' => 'danger',
                        'Menunggu' => 'warning',
                        default => 'success',
                    },
                    'status_note' => $loan->status_note,
                ];
            });

        $recentReturns = AssetReturn::query()
            ->with(['loan.asset', 'asset'])
            ->where('user_id', $pegawai->id)
            ->latest('created_at')
            ->latest('id')
            ->take(4)
            ->get()
            ->map(function (AssetReturn $returnItem) {
                $asset = $returnItem->asset ?? $returnItem->loan?->asset;
                $statusText = match ($returnItem->status) {
                    'Terverifikasi', 'Disetujui' => 'Sudah Terverifikasi',
                    'Ditolak' => 'Pengembalian Ditolak',
                    default => 'Menunggu Verifikasi',
                };
                $statusVariant = match ($returnItem->status) {
                    'Terverifikasi', 'Disetujui' => 'success',
                    'Ditolak' => 'danger',
                    default => 'warning',
                };

                return [
                    'asset_name' => $asset?->name ?? 'Aset Inventaris',
                    'asset_code' => $asset?->code ?? '-',
                    'returned_at' => optional($returnItem->returned_at)->translatedFormat('d F Y'),
                    'condition' => $returnItem->condition ?? 'Baik',
                    'status' => $statusText,
                    'status_variant' => $statusVariant,
                ];
            });

        $activeLoans = Loan::query()
            ->with('asset')
            ->where('user_id', $pegawai->id)
            ->where('status', 'Disetujui')
            ->latest('loan_date')
            ->take(3)
            ->get()
            ->map(function (Loan $loan) {
                return [
                    'asset_name' => $loan->asset?->name,
                    'asset_code' => $loan->asset?->code,
                    'loan_date' => optional($loan->loan_date)->translatedFormat('d F Y'),
                    'planned_return_date' => optional($loan->planned_return_date)->translatedFormat('d F Y'),
                    'quantity' => $loan->quantity,
                ];
            });

        $statusApproved = Loan::query()->where('user_id', $pegawai->id)->where('status', 'Disetujui')->count();
        $statusPending = Loan::query()->where('user_id', $pegawai->id)->where('status', 'Menunggu')->count();
        $statusRejected = Loan::query()->where('user_id', $pegawai->id)->where('status', 'Ditolak')->count();

        // Kirim seluruh komponen dashboard melalui data layout standar pegawai.
        return view('pegawai.dashboard', $this->layoutData([
            // Definisi kartu mencakup label, angka, bantuan, ikon, dan warna.
            'statCards' => [
                [
                    'label' => 'Total Aset',
                    'value' => $assetTotal,
                    'helper' => 'Jumlah seluruh aset yang tercatat di sistem.',
                    'icon' => 'box-seam',
                    'variant' => 'primary',
                ],
                [
                    'label' => 'Aset Tersedia',
                    'value' => $availableAssetTotal,
                    'helper' => 'Aset yang siap dipinjam atau digunakan.',
                    'icon' => 'check2-circle',
                    'variant' => 'success',
                ],
                [
                    'label' => 'Peminjaman Saya',
                    'value' => $loanTotal,
                    'helper' => 'Riwayat pengajuan peminjaman akun Anda.',
                    'icon' => 'journal-check',
                    'variant' => 'warning',
                ],
                [
                    'label' => 'Pengembalian Saya',
                    'value' => $returnTotal,
                    'helper' => 'Data pengembalian yang sudah Anda catat.',
                    'icon' => 'arrow-counterclockwise',
                    'variant' => 'info',
                ],
            ],
            // Grafik membandingkan seri peminjaman dan pengembalian per bulan.
            'activityChart' => [
                'labels' => $chartLabels,
                'loan_series' => $loanTrend,
                'return_series' => $returnTrend,
            ],
            'statusChart' => [
                'labels' => ['Disetujui', 'Menunggu', 'Ditolak'],
                'series' => [$statusApproved, $statusPending, $statusRejected],
            ],
            'recentAssets' => $recentAssets,
            'recentLoans' => $recentLoans,
            'recentReturns' => $recentReturns,
        ]));
    }

    /**
     * Menghitung jumlah record query untuk setiap bulan yang diberikan.
     *
     * Query dikloning pada tiap iterasi agar filter bulan sebelumnya tidak menumpuk.
     *
     * @return list<int>
     */
    private function buildMonthlySeries($query, string $column, Collection $months): array
    {
        // Petakan setiap bulan menjadi satu angka untuk seri grafik.
        return $months
            ->map(function (Carbon $month) use ($query, $column) {
                // Batasi kolom tanggal dari awal sampai akhir bulan terkait.
                return (clone $query)
                    ->whereBetween($column, [
                        $month->copy()->startOfMonth(),
                        $month->copy()->endOfMonth(),
                    ])
                    ->count();
            })
            ->all();
    }
}
