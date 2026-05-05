@extends('layouts.app')

@section('title', 'Pengembalian Pegawai')

@section('content')
    <div class="page-heading">
        @include('admin.partials.page-header', [
            'title' => 'Pengembalian Aset',
            'subtitle' => 'Pantau riwayat pengembalian dan ajukan pengembalian aset yang sedang dipinjam.',
            'breadcrumb' => 'Pengembalian',
            'homeRoute' => 'pegawai.dashboard',
        ])
    </div>

    <div class="page-content">
        <section class="section">
            @if (session('success'))
                <div class="alert alert-light-success color-success">
                    <i class="bi bi-check-circle me-1"></i>{{ session('success') }}
                </div>
            @endif

            @if (session('error'))
                <div class="alert alert-light-danger color-danger">
                    <i class="bi bi-exclamation-circle me-1"></i>{{ session('error') }}
                </div>
            @endif

            <div class="card pegawai-panel pegawai-table-card">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-3">
                    <div>
                        <h4 class="card-title mb-1">Riwayat Pengembalian</h4>
                        <p class="mb-0 text-muted">Daftar pengembalian aset yang tercatat pada akun pegawai.</p>
                    </div>
                    <div class="d-flex align-items-center flex-wrap gap-2">
                        <span class="badge bg-light-primary">{{ $returnTotal }} data</span>
                        <button
                            type="button"
                            class="btn btn-primary btn-sm icon icon-left"
                            data-bs-toggle="modal"
                            data-bs-target="#returnRequestModal"
                            @disabled($returnableLoans->isEmpty())
                        >
                            <i class="bi bi-plus-circle"></i><span>Ajukan Pengembalian</span>
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    @if ($returnableLoans->isEmpty())
                        <div class="alert alert-light-warning color-warning">
                            <i class="bi bi-exclamation-triangle me-1"></i>Belum ada peminjaman yang siap diajukan untuk pengembalian.
                        </div>
                    @endif

                    <div class="table-responsive">
                        <table class="table table-hover table-lg mb-0">
                            <thead>
                                <tr>
                                    <th>Aset</th>
                                    <th>Pengembalian</th>
                                    <th>Kondisi</th>
                                    <th>Status</th>
                                    <th>Berita Acara</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($returns as $return)
                                    @php
                                        $conditionBadge = match ($return['condition_variant']) {
                                            'warning' => 'bg-light-warning',
                                            'danger' => 'bg-light-danger',
                                            default => 'bg-light-success',
                                        };
                                        $statusBadge = $return['status_variant'] === 'success' ? 'bg-light-success' : 'bg-light-info';
                                    @endphp
                                    <tr>
                                        <td>
                                            <div>{{ $return['asset_name'] }}</div>
                                            <small class="text-muted">{{ $return['asset_code'] }}</small>
                                        </td>
                                        <td>
                                            <div>{{ $return['returned_at'] }}</div>
                                            <small class="text-muted">{{ $return['verified_note'] ?: 'Menunggu catatan verifikasi admin.' }}</small>
                                        </td>
                                        <td>
                                            <span class="badge {{ $conditionBadge }}">{{ $return['condition'] }}</span>
                                        </td>
                                        <td>
                                            <span class="badge {{ $statusBadge }}">{{ $return['status'] }}</span>
                                            <div><small class="text-muted">{{ $return['status_note'] }}</small></div>
                                        </td>
                                        <td>
                                            <div>{{ $return['report_number'] }}</div>
                                            <small class="text-muted">{{ $return['report_note'] ?: 'Nomor berita acara dibuat saat pengajuan.' }}</small>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center text-muted">Belum ada data pengembalian.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    @include('admin.partials.table-footer', [
                        'from' => $returns->firstItem() ?? 0,
                        'to' => $returns->lastItem() ?? 0,
                        'total' => $returnTotal,
                        'label' => 'pengembalian',
                    ])
                </div>
            </div>
        </section>
    </div>

    <div class="modal fade" id="returnRequestModal" tabindex="-1" aria-labelledby="returnRequestModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title" id="returnRequestModalLabel">Ajukan Pengembalian Aset</h5>
                        <small class="text-muted">Pilih data peminjaman yang sudah selesai digunakan lalu kirim pengajuan pengembalian.</small>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="{{ route('pegawai.returns.store') }}" method="POST">
                    @csrf
                    <div class="modal-body">
                        @if ($errors->createReturn->any())
                            <div class="alert alert-light-danger color-danger">
                                <i class="bi bi-exclamation-circle me-1"></i>{{ $errors->createReturn->first() }}
                            </div>
                        @endif

                        <div class="row">
                            <div class="col-md-6 col-12">
                                <div class="form-group">
                                    <label for="loan_id">Data Peminjaman</label>
                                    <select id="loan_id" name="loan_id" class="form-select @error('loan_id', 'createReturn') is-invalid @enderror" @disabled($returnableLoans->isEmpty())>
                                        <option value="">Pilih peminjaman yang akan dikembalikan</option>
                                        @foreach ($returnableLoans as $loan)
                                            <option value="{{ $loan->id }}" @selected(old('loan_id') == $loan->id)>
                                                {{ $loan->asset?->name }} ({{ $loan->asset?->code }}) - Pinjam {{ optional($loan->loan_date)->format('d/m/Y') }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('loan_id', 'createReturn')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6 col-12">
                                <div class="form-group">
                                    <label for="return_status_info">Status Pengajuan</label>
                                    <input type="text" id="return_status_info" class="form-control" value="Menunggu verifikasi admin" readonly>
                                </div>
                            </div>
                            <div class="col-md-6 col-12">
                                <div class="form-group">
                                    <label for="returned_at">Tanggal Kembali</label>
                                    <input
                                        type="date"
                                        id="returned_at"
                                        name="returned_at"
                                        class="form-control @error('returned_at', 'createReturn') is-invalid @enderror"
                                        value="{{ old('returned_at', now()->format('Y-m-d')) }}"
                                        @disabled($returnableLoans->isEmpty())
                                    >
                                    @error('returned_at', 'createReturn')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6 col-12">
                                <div class="form-group">
                                    <label for="condition">Kondisi Aset</label>
                                    <select id="condition" name="condition" class="form-select @error('condition', 'createReturn') is-invalid @enderror" @disabled($returnableLoans->isEmpty())>
                                        <option value="">Pilih kondisi aset</option>
                                        @foreach ($conditions as $condition)
                                            <option value="{{ $condition }}" @selected(old('condition') === $condition)>{{ $condition }}</option>
                                        @endforeach
                                    </select>
                                    @error('condition', 'createReturn')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="form-group mb-0">
                                    <label for="report_note">Catatan Pengembalian</label>
                                    <textarea
                                        id="report_note"
                                        name="report_note"
                                        class="form-control @error('report_note', 'createReturn') is-invalid @enderror"
                                        rows="4"
                                        placeholder="Contoh: aset sudah selesai digunakan dan siap dicek admin"
                                        @disabled($returnableLoans->isEmpty())
                                    >{{ old('report_note') }}</textarea>
                                    @error('report_note', 'createReturn')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary icon icon-left" @disabled($returnableLoans->isEmpty())>
                            <i class="bi bi-check-circle"></i><span>Kirim Pengajuan</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const modalElement = document.getElementById('returnRequestModal');

            @if ($errors->createReturn->any())
                if (modalElement) {
                    new bootstrap.Modal(modalElement).show();
                }
            @endif
        });
    </script>
@endpush
