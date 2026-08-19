<?php

namespace App\Http\Controllers\Pegawai;

use App\Models\Loan;
use App\Support\SuratPeminjamanService;

/**
 * Menampilkan dan mengunduh surat peminjaman milik pegawai.
 */
class SuratPeminjamanController extends BasePegawaiController
{
    /**
     * Menyuntikkan layanan pembuat data dan PDF surat peminjaman.
     */
    public function __construct(
        private readonly SuratPeminjamanService $suratPeminjamanService,
    ) {
    }

    /**
     * Menampilkan pratinjau surat untuk peminjaman yang berhak diakses pegawai.
     */
    public function show(Loan $loan, \Illuminate\Http\Request $request)
    {
        // Verifikasi kepemilikan sebelum mengambil atau membuat surat.
        $loan = $this->authorizedLoan($loan);
        $suratPeminjaman = $this->suratPeminjamanService->ensureForLoan($loan);

        // Surat hanya tersedia untuk status peminjaman yang memenuhi syarat layanan.
        abort_if(! $suratPeminjaman, 404);

        $fromReturns = $request->query('from') === 'returns';
        $backUrl = $fromReturns ? route('pegawai.returns.index') : route('pegawai.loans.index');
        $homeRoute = $fromReturns ? 'pegawai.returns.index' : 'pegawai.loans.index';
        $homeLabel = $fromReturns ? 'Pengembalian' : 'Peminjaman';
        $letterNote = $fromReturns
            ? 'Dokumen ini tersambung pada riwayat pengembalian aset Anda.'
            : 'Dokumen ini tersimpan pada riwayat peminjaman aset Anda.';

        // Gabungkan isi surat, tautan unduh, navigasi, dan data layout pegawai.
        return view('pegawai.loans.letter', $this->layoutData(array_merge(
            $this->suratPeminjamanService->previewData($suratPeminjaman),
            [
                'downloadUrl' => route('pegawai.loans.letter.download', $loan),
                'backUrl' => $backUrl,
                'homeRoute' => $homeRoute,
                'homeLabel' => $homeLabel,
                'letterNote' => $letterNote,
            ],
        )));
    }

    /**
     * Mengirim PDF surat sebagai berkas unduhan.
     */
    public function download(Loan $loan)
    {
        // Terapkan pemeriksaan kepemilikan yang sama seperti halaman pratinjau.
        $loan = $this->authorizedLoan($loan);
        $suratPeminjaman = $this->suratPeminjamanService->ensureForLoan($loan);

        // Jangan mengungkap data peminjaman jika surat tidak tersedia.
        abort_if(! $suratPeminjaman, 404);

        // Bentuk respons PDF dengan nama file yang ditentukan oleh layanan surat.
        return response($this->suratPeminjamanService->pdfBinary($suratPeminjaman))
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'attachment; filename="'.$this->suratPeminjamanService->pdfFilename($suratPeminjaman).'"');
    }

    /**
     * Memastikan peminjaman dimiliki pegawai aktif dan memuat relasi surat.
     */
    private function authorizedLoan(Loan $loan): Loan
    {
        // Gunakan 404 agar keberadaan peminjaman pengguna lain tidak terungkap.
        abort_if($loan->user_id !== $this->currentPegawai()->id, 404);

        // Muat relasi yang diperlukan hanya jika belum tersedia pada model.
        return $loan->loadMissing(['asset.category', 'user', 'approvedBy', 'suratPeminjaman']);
    }
}
