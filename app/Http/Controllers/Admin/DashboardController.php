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

    private function statCards(): array
    {
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

    private function activityChart(): array
    {
        $years = collect(range(4, 0))
            ->map(fn(int $offset) => now()->subYears($offset)->startOfYear());

        return [
            'labels' => $years
                ->map(fn(Carbon $year) => $year->format('Y'))
                ->all(),
            'asset_series' => $this->buildYearlySeries(Asset::class, 'created_at', $years),
            'loan_series' => $this->buildYearlySeries(Loan::class, 'loan_date', $years),
        ];
    }

    private function assetConditionChart(): array
    {
        return [
            'labels' => ['Baik', 'Rusak Ringan', 'Rusak Berat'],
            'series' => [
                Asset::query()->where('condition', 'Baik')->count(),
                Asset::query()->where('condition', 'Rusak Ringan')->count(),
                Asset::query()->where('condition', 'Rusak Berat')->count(),
            ],
        ];
    }

    private function maintenanceAssets(): Collection
    {
        return Asset::query()
            ->with(['category', 'location'])
            ->where(function ($query) {
                $query->whereIn('condition', ['Rusak Ringan', 'Rusak Berat'])
                    ->orWhere('status', 'Perbaikan');
            })
            ->orderByRaw("case when `condition` = ? then 0 when `condition` = ? then 1 else 2 end", ['Rusak Berat', 'Rusak Ringan'])
            ->orderBy('name')
            ->take(5)
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

    private function recentLoans(): Collection
    {
        return Loan::query()
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
    }

    private function popularAssets(): Collection
    {
        return Asset::query()
            ->with(['category'])
            ->withCount([
                'loans as loan_count' => fn($query) => $query->whereIn('status', ['Disetujui', 'Selesai']),
            ])
            ->having('loan_count', '>', 0)
            ->orderByDesc('loan_count')
            ->orderBy('name')
            ->take(5)
            ->get()
            ->values()
            ->map(function (Asset $asset, int $index) {
                return [
                    'rank' => $index + 1,
                    'name' => $asset->name,
                    'code' => $asset->code,
                    'category' => $asset->category?->name ?? 'Tanpa kategori',
                    'loan_count' => $asset->loan_count,
                ];
            });
    }

    private function returnDueLoans(): Collection
    {
        $today = now()->startOfDay();

        return Loan::query()
            ->with(['asset', 'user'])
            ->where('status', 'Disetujui')
            ->whereNotNull('planned_return_date')
            ->whereDoesntHave('returnRecord', function ($query) {
                $query->where('status', 'Terverifikasi');
            })
            ->orderBy('planned_return_date')
            ->take(5)
            ->get()
            ->map(function (Loan $loan) use ($today) {
                $plannedReturnDate = $loan->planned_return_date
                    ? Carbon::parse($loan->planned_return_date)->startOfDay()
                    : null;
                $isOverdue = $plannedReturnDate?->lt($today) ?? false;
                $isDueToday = $plannedReturnDate?->isSameDay($today) ?? false;

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

    private function buildYearlySeries(string $modelClass, string $column, Collection $years): array
    {
        return $years
            ->map(function (Carbon $year) use ($modelClass, $column) {
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
