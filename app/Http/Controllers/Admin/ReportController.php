<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Models\AssetReturn;
use App\Models\Loan;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class ReportController extends Controller
{
    public function index()
    {
        $assetTotal = Asset::query()->count();
        $loanTotal = Loan::query()->count();
        $returnTotal = AssetReturn::query()->count();
        $availableAssetTotal = Asset::query()->where('status', 'Tersedia')->count();

        $loanPreview = Loan::query()
            ->with(['asset', 'user'])
            ->latest('loan_date')
            ->take(3)
            ->get()
            ->map(fn (Loan $loan) => [
                'asset_name' => $loan->asset?->name,
                'asset_code' => $loan->asset?->code,
                'employee_name' => $loan->user?->name,
                'employee_email' => $loan->user?->email,
                'loan_date' => optional($loan->loan_date)->format('d/m/Y'),
                'return_plan' => 'Rencana kembali ' . optional($loan->planned_return_date)->format('d/m/Y'),
            ]);

        $returnPreview = AssetReturn::query()
            ->with('asset')
            ->latest('returned_at')
            ->take(3)
            ->get()
            ->map(fn (AssetReturn $return) => [
                'asset_name' => $return->asset?->name,
                'asset_code' => $return->asset?->code,
                'returned_at' => optional($return->returned_at)->format('d/m/Y'),
                'status' => $return->status,
                'status_note' => $return->status_note,
                'report_number' => $return->report_number,
                'report_note' => $return->report_note,
            ]);

        return view('admin.reports.index', [
            'summaryCards' => [
                ['label' => 'Total Aset', 'value' => $assetTotal],
                ['label' => 'Total Peminjaman', 'value' => $loanTotal],
                ['label' => 'Total Pengembalian', 'value' => $returnTotal],
                ['label' => 'Aset Tersedia', 'value' => $availableAssetTotal],
            ],
            'loanPreview' => $loanPreview,
            'loanTotal' => $loanTotal,
            'returnPreview' => $returnPreview,
            'returnTotal' => $returnTotal,
        ]);
    }

    public function download(string $type): Response
    {
        $report = $this->reportPayload($type);

        abort_if($report === null, 404);

        return Pdf::loadView('admin.reports.pdf', $report)
            ->setPaper('a4', 'landscape')
            ->download($report['filename']);
    }

    private function reportPayload(string $type): ?array
    {
        return match ($type) {
            'inventaris' => [
                'title' => 'Laporan Inventaris Aset',
                'filename' => 'laporan-inventaris-aset.pdf',
                'columns' => ['No', 'Kode', 'Nama Aset', 'Kategori', 'Lokasi', 'Kondisi', 'Status', 'Harga', 'Tanggal Perolehan'],
                'rows' => $this->inventoryRows(),
            ],
            'peminjaman' => [
                'title' => 'Laporan Peminjaman Aset',
                'filename' => 'laporan-peminjaman-aset.pdf',
                'columns' => ['No', 'Kode Aset', 'Nama Aset', 'Pegawai', 'Email', 'Tanggal Pinjam', 'Rencana Kembali', 'Status', 'Catatan'],
                'rows' => $this->loanRows(),
            ],
            'pengembalian' => [
                'title' => 'Laporan Pengembalian Aset',
                'filename' => 'laporan-pengembalian-aset.pdf',
                'columns' => ['No', 'No. BA', 'Kode Aset', 'Nama Aset', 'Pegawai', 'Tanggal Kembali', 'Kondisi', 'Status', 'Catatan'],
                'rows' => $this->returnRows(),
            ],
            default => null,
        };
    }

    private function inventoryRows(): Collection
    {
        return Asset::query()
            ->with(['category', 'location'])
            ->orderBy('name')
            ->get()
            ->map(fn (Asset $asset, int $index) => [
                $index + 1,
                $asset->code,
                $asset->name,
                $asset->category?->name ?? '-',
                $asset->location?->name ?? '-',
                $asset->condition,
                $asset->status,
                'Rp ' . number_format((float) $asset->acquisition_price, 0, ',', '.'),
                optional($asset->acquired_at)->format('d/m/Y') ?? '-',
            ]);
    }

    private function loanRows(): Collection
    {
        return Loan::query()
            ->with(['asset', 'user'])
            ->latest('loan_date')
            ->get()
            ->map(fn (Loan $loan, int $index) => [
                $index + 1,
                $loan->asset?->code ?? '-',
                $loan->asset?->name ?? '-',
                $loan->user?->name ?? '-',
                $loan->user?->email ?? '-',
                optional($loan->loan_date)->format('d/m/Y') ?? '-',
                optional($loan->planned_return_date)->format('d/m/Y') ?? '-',
                $loan->status,
                Str::limit($loan->status_note ?: '-', 80),
            ]);
    }

    private function returnRows(): Collection
    {
        return AssetReturn::query()
            ->with(['asset', 'user'])
            ->latest('returned_at')
            ->get()
            ->map(fn (AssetReturn $return, int $index) => [
                $index + 1,
                $return->report_number,
                $return->asset?->code ?? '-',
                $return->asset?->name ?? '-',
                $return->user?->name ?? '-',
                optional($return->returned_at)->format('d/m/Y') ?? '-',
                $return->condition,
                $return->status,
                Str::limit($return->status_note ?: $return->report_note ?: '-', 80),
            ]);
    }
}
