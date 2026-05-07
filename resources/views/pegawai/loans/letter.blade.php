@extends('layouts.app')

@section('title', 'Surat Peminjaman Aset')

@push('styles')
    @include('surat-peminjaman.partials.styles')
@endpush

@section('content')
    <div class="page-heading">
        @include('admin.partials.page-header', [
            'title' => 'Surat Peminjaman Aset',
            'subtitle' => 'Preview surat peminjaman aset yang dapat diunduh dalam format PDF.',
            'breadcrumb' => 'Surat Peminjaman',
            'homeRoute' => 'pegawai.loans.index',
        ])
    </div>

    <div class="page-content">
        <section class="section">
            @if ($missingSignatures)
                <div class="alert alert-light-warning color-warning">
                    <i class="bi bi-exclamation-triangle me-1"></i>{{ implode(' | ', $missingSignatures) }}. Dokumen tetap bisa dibuka, namun area tanda tangan akan menampilkan placeholder.
                </div>
            @endif

            <div class="card pegawai-panel">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div>
                        <h4 class="card-title mb-1">{{ $suratPeminjaman->number }}</h4>
                        <p class="mb-0 text-muted">Dokumen ini tersimpan pada riwayat peminjaman aset Anda.</p>
                    </div>
                    <div class="d-flex flex-wrap gap-2">
                        <a href="{{ $backUrl }}" class="btn btn-light-secondary btn-sm icon icon-left">
                            <i class="bi bi-arrow-left"></i><span>Kembali</span>
                        </a>
                        <a href="{{ $downloadUrl }}" class="btn btn-primary btn-sm icon icon-left">
                            <i class="bi bi-download"></i><span>Download PDF</span>
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="surat-peminjaman-preview-shell">
                        <div class="surat-peminjaman-preview-sheet">
                            @include('surat-peminjaman.partials.body', $documentData)
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
@endsection
