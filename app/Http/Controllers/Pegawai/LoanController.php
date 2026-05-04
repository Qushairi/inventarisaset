<?php

namespace App\Http\Controllers\Pegawai;

use App\Models\Loan;

class LoanController extends BasePegawaiController
{
    public function index()
    {
        $pegawai = $this->currentPegawai();

        $loans = Loan::query()
            ->with('asset')
            ->where('user_id', $pegawai->id)
            ->latest('loan_date')
            ->paginate(10)
            ->through(function (Loan $loan) {
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

        return view('pegawai.loans.index', $this->layoutData([
            'loans' => $loans,
            'loanTotal' => Loan::query()->where('user_id', $pegawai->id)->count(),
        ]));
    }
}
