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

class DashboardController extends Controller
{
    public function index()
    {
        $assetTotal = Asset::query()->count();
        $categoryTotal = Category::query()->count();
        $loanTotal = Loan::query()->count();
        $returnTotal = AssetReturn::query()->count();
        $availableAssetTotal = Asset::query()->where('status', 'Tersedia')->count();
        $attentionAssetTotal = Asset::query()->whereIn('condition', ['Rusak Ringan', 'Rusak Berat'])->count();
        $locationTotal = Location::query()->count();
        $employeeTotal = User::query()->where('role', 'pegawai')->count();
        $pendingLoanTotal = Loan::query()->where('status', 'Menunggu')->count();

        $months = collect(range(5, 0))
            ->map(fn (int $offset) => now()->subMonths($offset)->startOfMonth());

        $chartLabels = $months
            ->map(fn (Carbon $month) => $month->translatedFormat('M'))
            ->all();

        $assetTrend = $this->buildMonthlySeries(Asset::class, 'created_at', $months);
        $loanTrend = $this->buildMonthlySeries(Loan::class, 'loan_date', $months);
        $returnTrend = $this->buildMonthlySeries(AssetReturn::class, 'returned_at', $months);

        $assetConditionChart = [
            'labels' => ['Baik', 'Rusak Ringan', 'Rusak Berat'],
            'series' => [
                Asset::query()->where('condition', 'Baik')->count(),
                Asset::query()->where('condition', 'Rusak Ringan')->count(),
                Asset::query()->where('condition', 'Rusak Berat')->count(),
            ],
        ];

        $recentAssets = Asset::query()
            ->with(['category', 'location'])
            ->latest()
            ->take(3)
            ->get()
            ->map(function (Asset $asset) {
                return [
                    'name' => $asset->name,
                    'code' => $asset->code,
                    'avatar_type' => $asset->image_path ? 'image' : 'initial',
                    'avatar_value' => $asset->image_path ?: Str::upper(Str::substr($asset->name, 0, 1)),
                    'category' => $asset->category?->name,
                    'category_note' => $asset->category?->description ?? 'Kategori aset aktif pada sistem inventaris.',
                    'location' => $asset->location?->name,
                    'location_note' => $asset->location?->address ?? 'Lokasi aset tersimpan pada sistem.',
                    'status' => $asset->status,
                    'status_variant' => $asset->status === 'Tersedia' ? 'success' : 'warning',
                ];
            });

        $recentLoans = Loan::query()
            ->with(['asset', 'user'])
            ->latest('loan_date')
            ->take(4)
            ->get()
            ->map(function (Loan $loan) {
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

        return view('admin.dashboard', [
            'statCards' => [
                [
                    'label' => 'Total Aset',
                    'value' => $assetTotal,
                    'helper' => 'Aset aktif yang tercatat saat ini.',
                    'icon' => 'boxes',
                    'variant' => 'primary',
                ],
                [
                    'label' => 'Kategori',
                    'value' => $categoryTotal,
                    'helper' => 'Kelompok aset yang tersedia.',
                    'icon' => 'tags',
                    'variant' => 'success',
                ],
                [
                    'label' => 'Peminjaman',
                    'value' => $loanTotal,
                    'helper' => 'Riwayat pengajuan peminjaman aset.',
                    'icon' => 'clipboard-check',
                    'variant' => 'warning',
                ],
                [
                    'label' => 'Pengembalian',
                    'value' => $returnTotal,
                    'helper' => 'Data pengembalian yang sudah diverifikasi.',
                    'icon' => 'arrow-counterclockwise',
                    'variant' => 'info',
                ],
            ],
            'highlights' => [
                [
                    'title' => 'Aset tersedia',
                    'value' => $availableAssetTotal,
                    'note' => 'Siap digunakan atau dipinjam saat ini.',
                ],
                [
                    'title' => 'Butuh perhatian',
                    'value' => $attentionAssetTotal,
                    'note' => 'Perlu pengecekan kondisi fisik.',
                ],
                [
                    'title' => 'Peminjaman menunggu',
                    'value' => $pendingLoanTotal,
                    'note' => 'Masih menunggu tindak lanjut admin.',
                ],
                [
                    'title' => 'Akun pegawai',
                    'value' => $employeeTotal,
                    'note' => 'Sudah dapat mengakses sistem.',
                ],
            ],
            'quickLinks' => [
                [
                    'title' => 'Kelola Kategori',
                    'description' => 'Atur kelompok aset agar data inventaris rapi.',
                    'route' => 'admin.categories.index',
                    'icon' => 'tags',
                ],
                [
                    'title' => 'Kelola Lokasi',
                    'description' => 'Tetapkan posisi dan ruangan penyimpanan aset.',
                    'route' => 'admin.locations.index',
                    'icon' => 'geo-alt',
                ],
                [
                    'title' => 'Kelola Aset',
                    'description' => 'Pantau kondisi, status, dan nilai perolehan barang.',
                    'route' => 'admin.assets.index',
                    'icon' => 'boxes',
                ],
                [
                    'title' => 'Kelola Pegawai',
                    'description' => 'Atur akun user pegawai yang memakai sistem.',
                    'route' => 'admin.employees.index',
                    'icon' => 'people',
                ],
                [
                    'title' => 'Peminjaman',
                    'description' => 'Setujui atau tolak pengajuan peminjaman aset.',
                    'route' => 'admin.loans.index',
                    'icon' => 'journal-check',
                ],
                [
                    'title' => 'Laporan',
                    'description' => 'Unduh rekap inventaris, peminjaman, dan pengembalian.',
                    'route' => 'admin.reports.index',
                    'icon' => 'bar-chart',
                ],
            ],
            'activityChart' => [
                'labels' => $chartLabels,
                'asset_series' => $assetTrend,
                'loan_series' => $loanTrend,
                'return_series' => $returnTrend,
            ],
            'assetConditionChart' => $assetConditionChart,
            'trendCards' => [
                [
                    'title' => 'Aset Masuk',
                    'value' => array_sum($assetTrend),
                    'color' => '#435ebe',
                    'chart_id' => 'chart-assets-trend',
                    'series' => $assetTrend,
                ],
                [
                    'title' => 'Peminjaman',
                    'value' => array_sum($loanTrend),
                    'color' => '#55c6e8',
                    'chart_id' => 'chart-loans-trend',
                    'series' => $loanTrend,
                ],
                [
                    'title' => 'Pengembalian',
                    'value' => array_sum($returnTrend),
                    'color' => '#00b894',
                    'chart_id' => 'chart-returns-trend',
                    'series' => $returnTrend,
                ],
            ],
            'recentAssets' => $recentAssets,
            'recentLoans' => $recentLoans,
        ]);
    }

    private function buildMonthlySeries(string $modelClass, string $column, Collection $months): array
    {
        return $months
            ->map(function (Carbon $month) use ($modelClass, $column) {
                return $modelClass::query()
                    ->whereBetween($column, [
                        $month->copy()->startOfMonth(),
                        $month->copy()->endOfMonth(),
                    ])
                    ->count();
            })
            ->all();
    }
}
