<?php

namespace App\Http\Controllers\Pegawai;

use App\Models\Asset;
use App\Models\AssetReturn;
use App\Models\Loan;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class DashboardController extends BasePegawaiController
{
    public function index()
    {
        $pegawai = $this->currentPegawai();

        $assetTotal = Asset::query()->count();
        $availableAssetTotal = Asset::query()->where('status', 'Tersedia')->count();
        $loanTotal = Loan::query()->where('user_id', $pegawai->id)->count();
        $returnTotal = AssetReturn::query()->where('user_id', $pegawai->id)->count();

        $months = collect(range(5, 0))
            ->map(fn (int $offset) => now()->subMonths($offset)->startOfMonth());

        $chartLabels = $months
            ->map(fn (Carbon $month) => $month->translatedFormat('M'))
            ->all();

        $loanTrend = $this->buildMonthlySeries(
            Loan::query()->where('user_id', $pegawai->id),
            'loan_date',
            $months,
        );

        $returnTrend = $this->buildMonthlySeries(
            AssetReturn::query()->where('user_id', $pegawai->id),
            'returned_at',
            $months,
        );

        $recentAssets = Asset::query()
            ->with(['category', 'location'])
            ->latest()
            ->take(4)
            ->get()
            ->map(function (Asset $asset) {
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

        $recentLoans = Loan::query()
            ->with('asset')
            ->where('user_id', $pegawai->id)
            ->latest('created_at')
            ->latest('id')
            ->take(4)
            ->get()
            ->map(function (Loan $loan) {
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

        return view('pegawai.dashboard', $this->layoutData([
            'pegawaiName' => $pegawai->name,
            'activeLoans' => $activeLoans,
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

    private function buildMonthlySeries($query, string $column, Collection $months): array
    {
        return $months
            ->map(function (Carbon $month) use ($query, $column) {
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
