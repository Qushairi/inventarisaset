<?php

namespace App\Http\Controllers\Pegawai;

use App\Models\Loan;
use App\Support\BeritaAcaraService;

class BeritaAcaraController extends BasePegawaiController
{
    public function __construct(
        private readonly BeritaAcaraService $beritaAcaraService,
    ) {
    }

    public function show(Loan $loan)
    {
        $loan = $this->authorizedLoan($loan);
        $beritaAcara = $this->beritaAcaraService->ensureForLoan($loan);

        abort_if(! $beritaAcara, 404);

        return view('pegawai.loans.letter', $this->layoutData(array_merge(
            $this->beritaAcaraService->previewData($beritaAcara),
            [
                'downloadUrl' => route('pegawai.loans.letter.download', $loan),
                'backUrl' => route('pegawai.loans.index'),
            ],
        )));
    }

    public function download(Loan $loan)
    {
        $loan = $this->authorizedLoan($loan);
        $beritaAcara = $this->beritaAcaraService->ensureForLoan($loan);

        abort_if(! $beritaAcara, 404);

        return response($this->beritaAcaraService->pdfBinary($beritaAcara))
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'attachment; filename="'.$this->beritaAcaraService->pdfFilename($beritaAcara).'"');
    }

    private function authorizedLoan(Loan $loan): Loan
    {
        abort_if($loan->user_id !== $this->currentPegawai()->id, 404);

        return $loan->loadMissing(['asset.category', 'user', 'approvedBy', 'beritaAcara']);
    }
}
