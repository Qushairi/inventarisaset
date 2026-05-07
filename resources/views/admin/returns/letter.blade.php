@extends('layouts.app')

@section('title', 'Surat Serah Terima Aset')

@push('styles')
    @include('surat-peminjaman.partials.styles')
    <style>
        .return-letter-preview-shell {
            overflow-x: auto;
            padding: 18px;
            background: #eef2f7;
            border-radius: 16px;
        }

        .return-letter-preview-sheet {
            width: 210mm;
            min-height: 297mm;
            margin: 0 auto;
            background: #fff;
            padding: 20mm 16mm;
            box-shadow: 0 12px 30px rgba(15, 23, 42, 0.12);
        }

        @media (max-width: 768px) {
            .return-letter-preview-shell {
                padding: 10px;
            }

            .return-letter-preview-sheet {
                width: 100%;
                min-height: auto;
                padding: 14px;
            }
        }
    </style>
@endpush

@section('content')
    <div class="page-heading">
        @include('admin.partials.page-header', [
            'title' => 'Surat Serah Terima Aset',
            'subtitle' => 'Preview berita acara pengembalian aset yang dapat diunduh dalam format PDF.',
            'breadcrumb' => 'Surat Pengembalian',
            'homeRoute' => 'admin.returns.index',
        ])
    </div>

    <div class="page-content">
        <section class="section">
            @if ($returnRecord->status !== 'Terverifikasi')
                <div class="alert alert-light-warning color-warning">
                    <i class="bi bi-exclamation-triangle me-1"></i>Dokumen ini masih bersifat draft karena pengembalian belum terverifikasi. Tanda tangan admin baru akan tampil setelah status diubah menjadi <strong>Terverifikasi</strong>.
                </div>
            @endif

            @if ($missingSignatures)
                <div class="alert alert-light-warning color-warning">
                    <i class="bi bi-exclamation-triangle me-1"></i>{{ implode(' | ', $missingSignatures) }}. Dokumen tetap bisa diunduh, namun area tanda tangan yang belum tersedia akan menampilkan placeholder.
                </div>
            @endif

            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div>
                        <h4 class="card-title mb-1">{{ $returnRecord->report_number }}</h4>
                        <p class="mb-0 text-muted">Status saat ini: {{ $returnRecord->status }}</p>
                    </div>
                    <div class="d-flex flex-wrap gap-2">
                        <a href="{{ $backUrl }}" class="btn btn-light-secondary btn-sm icon icon-left">
                            <i class="bi bi-arrow-left"></i><span>Kembali</span>
                        </a>
                        @if ($returnRecord->status !== 'Terverifikasi')
                            <a href="{{ $editUrl }}" class="btn btn-light-primary btn-sm icon icon-left">
                                <i class="bi bi-pencil-square"></i><span>Kelola Verifikasi</span>
                            </a>
                        @endif
                        <a href="{{ $downloadUrl }}" class="btn btn-primary btn-sm icon icon-left">
                            <i class="bi bi-download"></i><span>Download PDF</span>
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="return-letter-preview-shell">
                        <div class="return-letter-preview-sheet">
                            @include('asset-return-letters.partials.body', $documentData)
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
@endsection
