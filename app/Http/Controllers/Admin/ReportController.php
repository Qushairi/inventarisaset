<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Models\AssetReturn;
use App\Models\Loan;

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
}
