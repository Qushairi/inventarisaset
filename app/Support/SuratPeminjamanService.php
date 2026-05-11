<?php

namespace App\Support;

use Carbon\CarbonInterface;
use App\Models\BeritaAcara;
use App\Models\Loan;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SuratPeminjamanService
{
    public function ensureForLoan(Loan $loan, ?User $approver = null, bool $force = false): ?BeritaAcara
    {
        $loan->loadMissing([
            'asset.category',
            'user',
            'approvedBy',
            'suratPeminjaman.firstParty',
            'suratPeminjaman.secondParty',
        ]);

        if (! in_array($loan->status, ['Disetujui', 'Selesai'], true)) {
            return null;
        }

        $asset = $loan->asset;
        $pegawai = $loan->user;
        $resolvedApprover = $this->resolveApprover($loan, $approver);

        if (! $asset || ! $pegawai) {
            return null;
        }

        $issuedAt = now();
        $suratPeminjaman = $loan->suratPeminjaman ?? $loan->beritaAcara ?? new BeritaAcara();
        $isNewRecord = ! $suratPeminjaman->exists;

        $suratPeminjaman->fill([
            'asset_id' => $asset->id,
            'first_party_user_id' => $resolvedApprover?->id,
            'second_party_user_id' => $pegawai->id,
            'location' => $suratPeminjaman->location ?: 'Bengkalis',
            'asset_condition' => $this->normalizeCondition($asset->condition),
            'handover_statement' => $this->handoverStatement(),
            'closing_statement' => $this->closingStatement(),
            'issued_at' => $suratPeminjaman->issued_at ?? $issuedAt,
            'number' => $this->resolveDocumentNumber($suratPeminjaman, $issuedAt),
            'verification_token' => $suratPeminjaman->verification_token ?: $this->generateVerificationToken(),
        ]);

        $suratPeminjaman->loan()->associate($loan);
        $suratPeminjaman->save();

        if ($force || $isNewRecord || $this->needsPdfRegeneration($loan, $resolvedApprover, $suratPeminjaman)) {
            $pdfBinary = $this->renderPdfBinary($suratPeminjaman->fresh([
                'loan.asset.category',
                'loan.user',
                'firstParty',
                'secondParty',
                'asset.category',
            ]));

            $pdfPath = $this->pdfStoragePath($suratPeminjaman->number);

            Storage::disk('public')->put($pdfPath, $pdfBinary);

            $suratPeminjaman->forceFill([
                'pdf_path' => $pdfPath,
                'pdf_generated_at' => $issuedAt,
            ])->saveQuietly();
        }

        $loan->forceFill([
            'loan_letter_number' => $suratPeminjaman->number,
            'loan_letter_generated_at' => $suratPeminjaman->pdf_generated_at ?? $issuedAt,
        ])->saveQuietly();

        return $suratPeminjaman->fresh([
            'loan.asset.category',
            'loan.user',
            'firstParty',
            'secondParty',
            'asset.category',
        ]);
    }

    public function previewData(Loan|BeritaAcara $source): array
    {
        $suratPeminjaman = $this->resolveDocumentRecord($source);
        $payload = $this->viewPayload($suratPeminjaman);

        return [
            'suratPeminjaman' => $suratPeminjaman,
            'beritaAcara' => $suratPeminjaman,
            'loan' => $suratPeminjaman->loan,
            'asset' => $suratPeminjaman->asset,
            'approver' => $suratPeminjaman->firstParty,
            'pegawai' => $suratPeminjaman->secondParty,
            'documentData' => $payload,
            'missingSignatures' => $this->missingSignatureLabels($suratPeminjaman),
        ];
    }

    public function pdfBinary(Loan|BeritaAcara $source): string
    {
        $suratPeminjaman = $this->resolveDocumentRecord($source);
        $pdfPath = $suratPeminjaman->pdf_path;

        if ($pdfPath && Storage::disk('public')->exists($pdfPath) && ! $this->needsPdfRegeneration($suratPeminjaman->loan, $suratPeminjaman->firstParty, $suratPeminjaman)) {
            return Storage::disk('public')->get($pdfPath);
        }

        $suratPeminjaman = $this->ensureForLoan($suratPeminjaman->loan, $suratPeminjaman->firstParty, force: true) ?? $suratPeminjaman;

        return $suratPeminjaman->pdf_path && Storage::disk('public')->exists($suratPeminjaman->pdf_path)
            ? Storage::disk('public')->get($suratPeminjaman->pdf_path)
            : $this->renderPdfBinary($suratPeminjaman);
    }

    public function pdfFilename(Loan|BeritaAcara $source): string
    {
        $suratPeminjaman = $this->resolveDocumentRecord($source);
        $baseName = Str::slug('surat-peminjaman-aset-'.$suratPeminjaman->loan_id);

        return ($baseName !== '' ? $baseName : 'surat-peminjaman-aset').'.pdf';
    }

    public function missingSignatureLabels(Loan|BeritaAcara $source): array
    {
        $suratPeminjaman = $this->resolveDocumentRecord($source);

        $missing = [];

        if (! $suratPeminjaman->secondParty?->hasSignature()) {
            $missing[] = 'TTD pegawai belum tersedia';
        }

        if (! $suratPeminjaman->firstParty?->hasSignature()) {
            $missing[] = 'TTD admin belum tersedia';
        }

        return $missing;
    }

    private function resolveDocumentRecord(Loan|BeritaAcara $source): BeritaAcara
    {
        if ($source instanceof BeritaAcara) {
            return $source->loadMissing([
                'loan.asset.category',
                'loan.user',
                'firstParty',
                'secondParty',
                'asset.category',
            ]);
        }

        $suratPeminjaman = $this->ensureForLoan($source);

        if (! $suratPeminjaman) {
            abort(404);
        }

        return $suratPeminjaman;
    }

    private function renderPdfBinary(BeritaAcara $suratPeminjaman): string
    {
        return Pdf::loadView('surat-peminjaman.document', $this->viewPayload($suratPeminjaman))
            ->setPaper('a4')
            ->output();
    }

    private function viewPayload(BeritaAcara $suratPeminjaman): array
    {
        $suratPeminjaman->loadMissing([
            'loan.asset.category',
            'loan.user',
            'firstParty',
            'secondParty',
            'asset.category',
        ]);

        $asset = $suratPeminjaman->asset ?? $suratPeminjaman->loan?->asset;
        $loan = $suratPeminjaman->loan;
        $approver = $suratPeminjaman->firstParty;
        $pegawai = $suratPeminjaman->secondParty;

        return [
            'suratPeminjaman' => $suratPeminjaman,
            'beritaAcara' => $suratPeminjaman,
            'asset' => $asset,
            'loan' => $loan,
            'approver' => $approver,
            'pegawai' => $pegawai,
            'office' => $this->officeProfile(),
            'logoDataUri' => $this->dataUriFromPublicPath(public_path('assets/logo/logobengkalis.png')),
            'approverSignatureDataUri' => $this->dataUriFromStoragePath($approver?->signature_path),
            'pegawaiSignatureDataUri' => $this->dataUriFromStoragePath($pegawai?->signature_path),
            'asalPengadaan' => $this->originLabel($asset?->acquired_at),
            'printedAt' => $suratPeminjaman->issued_at ?? now(),
        ];
    }

    private function needsPdfRegeneration(Loan $loan, ?User $approver, BeritaAcara $suratPeminjaman): bool
    {
        if (! $suratPeminjaman->pdf_generated_at || blank($suratPeminjaman->pdf_path)) {
            return true;
        }

        if (! Storage::disk('public')->exists($suratPeminjaman->pdf_path)) {
            return true;
        }

        $generatedAt = $suratPeminjaman->pdf_generated_at;
        $pegawaiSignatureUpdatedAt = $loan->user?->signature_updated_at;
        $approverSignatureUpdatedAt = $approver?->signature_updated_at;

        if (($pegawaiSignatureUpdatedAt && $pegawaiSignatureUpdatedAt->gt($generatedAt))
            || ($approverSignatureUpdatedAt && $approverSignatureUpdatedAt->gt($generatedAt))) {
            return true;
        }

        if ($this->usesLegacyNumberPrefix($suratPeminjaman) || $this->usesLegacyStoragePath($suratPeminjaman)) {
            return true;
        }

        return (int) $suratPeminjaman->asset_id !== (int) $loan->asset_id
            || (int) $suratPeminjaman->second_party_user_id !== (int) $loan->user_id
            || (int) $suratPeminjaman->first_party_user_id !== (int) ($approver?->id ?? 0)
            || $suratPeminjaman->asset_condition !== $this->normalizeCondition($loan->asset?->condition);
    }

    private function resolveApprover(Loan $loan, ?User $approver = null): ?User
    {
        if ($approver instanceof User && $approver->role === 'admin') {
            return $approver;
        }

        if ($loan->approvedBy instanceof User) {
            return $loan->approvedBy;
        }

        return User::query()
            ->where('role', 'admin')
            ->orderBy('name')
            ->first();
    }

    private function generateNumber(CarbonInterface $issuedAt): string
    {
        return 'SPA-'.$issuedAt->format('YmdHis').'-'.str_pad((string) random_int(1, 9999), 4, '0', STR_PAD_LEFT);
    }

    private function generateVerificationToken(): string
    {
        return Str::upper(Str::random(12));
    }

    private function normalizeCondition(?string $condition): string
    {
        if (blank($condition)) {
            return 'BAIK';
        }

        return Str::of($condition)
            ->upper()
            ->replaceMatches('/[^A-Z0-9]+/', '_')
            ->trim('_')
            ->value();
    }

    private function handoverStatement(): string
    {
        return 'Dengan diterbitkannya surat peminjaman ini, PIHAK PERTAMA memberikan persetujuan kepada PIHAK KEDUA untuk menggunakan aset inventaris sesuai kebutuhan kedinasan dan ketentuan yang berlaku.';
    }

    private function closingStatement(): string
    {
        return 'Demikian surat peminjaman aset ini dibuat untuk dipergunakan sebagaimana mestinya. Setelah masa peminjaman berakhir, proses pengembalian dan berita acara pengembalian akan ditindaklanjuti oleh admin.';
    }

    private function originLabel($acquiredAt): string
    {
        if (! $acquiredAt) {
            return '-';
        }

        return 'APBD '.$acquiredAt->format('Y');
    }

    private function pdfStoragePath(string $number): string
    {
        return 'surat-peminjaman/'.Str::slug($number).'.pdf';
    }

    private function resolveDocumentNumber(BeritaAcara $suratPeminjaman, CarbonInterface $issuedAt): string
    {
        if (filled($suratPeminjaman->number) && ! $this->usesLegacyNumberPrefix($suratPeminjaman)) {
            return (string) $suratPeminjaman->number;
        }

        return $this->generateNumber($issuedAt);
    }

    private function usesLegacyNumberPrefix(BeritaAcara $suratPeminjaman): bool
    {
        return filled($suratPeminjaman->number) && Str::startsWith((string) $suratPeminjaman->number, 'BA-');
    }

    private function usesLegacyStoragePath(BeritaAcara $suratPeminjaman): bool
    {
        return filled($suratPeminjaman->pdf_path) && Str::startsWith((string) $suratPeminjaman->pdf_path, 'berita-acara/');
    }

    private function dataUriFromPublicPath(string $absolutePath): ?string
    {
        if (! is_file($absolutePath)) {
            return null;
        }

        $contents = file_get_contents($absolutePath);

        if ($contents === false) {
            return null;
        }

        $mimeType = mime_content_type($absolutePath) ?: 'image/png';

        return 'data:'.$mimeType.';base64,'.base64_encode($contents);
    }

    private function dataUriFromStoragePath(?string $relativePath): ?string
    {
        if (blank($relativePath)) {
            return null;
        }

        $absolutePath = Storage::disk('public')->path($relativePath);

        return $this->dataUriFromPublicPath($absolutePath);
    }

    private function officeProfile(): array
    {
        return [
            'government_name' => 'Pemerintah Kabupaten Bengkalis',
            'agency_name' => 'Dinas Pendidikan',
            'address' => 'Jalan Pertanian Nomor : 012 Bengkalis Kode Pos 28714',
            'phone' => '0821-6976-5430',
            'fax' => '(0766) 8001009',
            'email' => 'bengkalisdisdik884@gmail.com',
            'website' => 'www.disdik.bengkaliskab.go.id',
        ];
    }
}
