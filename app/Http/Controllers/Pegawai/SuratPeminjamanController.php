<?php

namespace App\Http\Controllers\Pegawai;

use App\Models\Loan;
use App\Support\SuratPeminjamanService;

class SuratPeminjamanController extends BasePegawaiController
{
    public function __construct(
        private readonly SuratPeminjamanService $suratPeminjamanService,
    ) {
    }

    public function show(Loan $loan)
    {
        $loan = $this->authorizedLoan($loan);
        $suratPeminjaman = $this->suratPeminjamanService->ensureForLoan($loan);

        abort_if(! $suratPeminjaman, 404);

        return view('pegawai.loans.letter', $this->layoutData(array_merge(
            $this->suratPeminjamanService->previewData($suratPeminjaman),
            [
                'downloadUrl' => route('pegawai.loans.letter.download', $loan),
                'backUrl' => route('pegawai.loans.index'),
            ],
        )));
    }

    public function download(Loan $loan)
    {
        $loan = $this->authorizedLoan($loan);
        $suratPeminjaman = $this->suratPeminjamanService->ensureForLoan($loan);

        abort_if(! $suratPeminjaman, 404);

        return response($this->suratPeminjamanService->pdfBinary($suratPeminjaman))
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'attachment; filename="'.$this->suratPeminjamanService->pdfFilename($suratPeminjaman).'"');
    }

    private function authorizedLoan(Loan $loan): Loan
    {
        abort_if($loan->user_id !== $this->currentPegawai()->id, 404);

        return $loan->loadMissing(['asset.category', 'user', 'approvedBy', 'suratPeminjaman']);
    }
}
