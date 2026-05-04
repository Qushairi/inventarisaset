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
        $pendingLoanTotal = Loan::query()
            ->where('user_id', $pegawai->id)
            ->where('status', 'Menunggu')
            ->count();
        $approvedLoanTotal = Loan::query()
            ->where('user_id', $pegawai->id)
            ->where('status', 'Disetujui')
            ->count();
        $verifiedReturnTotal = AssetReturn::query()
            ->where('user_id', $pegawai->id)
            ->where('status', 'Terverifikasi')
            ->count();

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
                    'avatar_type' => $asset->image_path ? 'image' : 'initial',
                    'avatar_value' => $asset->image_path ?: Str::upper(Str::substr($asset->name, 0, 1)),
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
            ->latest('loan_date')
            ->take(4)
            ->get()
            ->map(function (Loan $loan) {
                return [
                    'asset_name' => $loan->asset?->name,
                    'asset_code' => $loan->asset?->code,
                    'loan_date' => optional($loan->loan_date)->format('d/m/Y'),
                    'return_plan' => 'Rencana kembali ' . optional($loan->planned_return_date)->format('d/m/Y'),
                    'status' => $loan->status,
                    'status_variant' => match ($loan->status) {
                        'Ditolak' => 'danger',
                        'Menunggu' => 'warning',
                        default => 'success',
                    },
                    'status_note' => $loan->status_note,
                ];
            });

        return view('pegawai.dashboard', $this->layoutData([
            'statCards' => [
                [
                    'label' => 'Total Aset',
                    'value' => $assetTotal,
                    'helper' => 'Jumlah seluruh aset yang tercatat di sistem.',
                    'icon' => 'boxes',
                    'variant' => 'primary',
                ],
                [
                    'label' => 'Aset Tersedia',
                    'value' => $availableAssetTotal,
                    'helper' => 'Aset yang siap dipinjam atau digunakan.',
                    'icon' => 'box2-heart',
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
            'highlights' => [
                [
                    'title' => 'Peminjaman menunggu',
                    'value' => $pendingLoanTotal,
                    'note' => 'Masih menunggu persetujuan admin.',
                ],
                [
                    'title' => 'Peminjaman disetujui',
                    'value' => $approvedLoanTotal,
                    'note' => 'Sudah dapat ditindaklanjuti oleh pegawai.',
                ],
                [
                    'title' => 'Pengembalian terverifikasi',
                    'value' => $verifiedReturnTotal,
                    'note' => 'Sudah diverifikasi oleh admin.',
                ],
            ],
            'quickLinks' => [
                [
                    'title' => 'Data Aset',
                    'description' => 'Lihat daftar aset dan status ketersediaannya.',
                    'route' => 'pegawai.assets.index',
                    'icon' => 'boxes',
                ],
                [
                    'title' => 'Peminjaman',
                    'description' => 'Pantau riwayat pengajuan peminjaman Anda.',
                    'route' => 'pegawai.loans.index',
                    'icon' => 'journal-check',
                ],
                [
                    'title' => 'Pengembalian',
                    'description' => 'Pantau status pengembalian dan verifikasi aset.',
                    'route' => 'pegawai.returns.index',
                    'icon' => 'arrow-counterclockwise',
                ],
                [
                    'title' => 'Profile',
                    'description' => 'Lihat informasi akun pegawai dan aktivitas terbaru.',
                    'route' => 'pegawai.profile.index',
                    'icon' => 'person-circle',
                ],
            ],
            'activityChart' => [
                'labels' => $chartLabels,
                'loan_series' => $loanTrend,
                'return_series' => $returnTrend,
            ],
            'recentAssets' => $recentAssets,
            'recentLoans' => $recentLoans,
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
