<?php

namespace App\Support;

use App\Models\AssetReturn;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AssetReturnLetterService
{
    public function previewData(AssetReturn $return, ?User $admin = null): array
    {
        $return = $this->resolveAssetReturn($return);
        $payload = $this->viewPayload($return, $admin);

        return [
            'returnRecord' => $return,
            'loan' => $return->loan,
            'asset' => $return->asset,
            'approver' => $payload['approver'],
            'pegawai' => $payload['pegawai'],
            'documentData' => $payload,
            'missingSignatures' => $this->missingSignatureLabels($return, $admin),
        ];
    }

    public function pdfBinary(AssetReturn $return, ?User $admin = null): string
    {
        $return = $this->resolveAssetReturn($return);

        return Pdf::loadView('asset-return-letters.document', $this->viewPayload($return, $admin))
            ->setPaper('a4')
            ->output();
    }

    public function pdfFilename(AssetReturn $return): string
    {
        $baseName = Str::slug('berita-acara-serah-terima-aset-'.($return->report_number ?: $return->id));

        return ($baseName !== '' ? $baseName : 'berita-acara-serah-terima-aset').'.pdf';
    }

    public function missingSignatureLabels(AssetReturn $return, ?User $admin = null): array
    {
        $return = $this->resolveAssetReturn($return);
        $approver = $this->resolveApprover($return, $admin);
        $missing = [];

        if (! $return->user?->hasSignature()) {
            $missing[] = 'TTD pegawai belum tersedia';
        }

        if ($return->status === 'Terverifikasi' && ! $approver?->hasSignature()) {
            $missing[] = 'TTD admin belum tersedia';
        }

        return $missing;
    }

    private function resolveAssetReturn(AssetReturn $return): AssetReturn
    {
        return $return->loadMissing([
            'asset.category',
            'loan.asset.category',
            'loan.user',
            'loan.approvedBy',
            'user',
        ]);
    }

    private function viewPayload(AssetReturn $return, ?User $admin = null): array
    {
        $asset = $return->asset ?? $return->loan?->asset;
        $loan = $return->loan;
        $approver = $this->resolveApprover($return, $admin);
        $pegawai = $return->user ?? $loan?->user;

        return [
            'returnRecord' => $return,
            'asset' => $asset,
            'loan' => $loan,
            'approver' => $approver,
            'pegawai' => $pegawai,
            'office' => $this->officeProfile(),
            'logoDataUri' => $this->dataUriFromPublicPath(public_path('assets/logo/logobengkalis.png')),
            'approverSignatureDataUri' => $return->status === 'Terverifikasi'
                ? $this->dataUriFromStoragePath($approver?->signature_path)
                : null,
            'pegawaiSignatureDataUri' => $this->dataUriFromStoragePath($pegawai?->signature_path),
            'asalPengadaan' => $this->originLabel($asset?->acquired_at),
            'printedAt' => $return->returned_at ?? now(),
        ];
    }

    private function resolveApprover(AssetReturn $return, ?User $admin = null): ?User
    {
        if ($admin instanceof User && $admin->role === 'admin') {
            return $admin;
        }

        if ($return->loan?->approvedBy instanceof User && $return->loan->approvedBy->role === 'admin') {
            return $return->loan->approvedBy;
        }

        return User::query()
            ->where('role', 'admin')
            ->orderBy('name')
            ->first();
    }

    private function originLabel($acquiredAt): string
    {
        if (! $acquiredAt) {
            return '-';
        }

        return 'APBD '.$acquiredAt->format('Y');
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
