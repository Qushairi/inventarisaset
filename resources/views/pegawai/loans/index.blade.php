@extends('layouts.app')

@section('title', 'Peminjaman Pegawai')

@section('content')
    <div class="page-heading">
        @include('admin.partials.page-header', [
            'title' => 'Peminjaman Aset',
            'subtitle' => 'Pantau riwayat pengajuan peminjaman aset Anda.',
            'breadcrumb' => 'Peminjaman',
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
                        <h4 class="card-title mb-1">Riwayat Peminjaman</h4>
                        <p class="mb-0 text-muted">Daftar pengajuan peminjaman aset yang terkait dengan akun pegawai.</p>
                    </div>
                    <div class="d-flex align-items-center flex-wrap gap-2">
                        <span class="badge bg-light-primary">{{ $loanTotal }} pengajuan</span>
                        <button
                            type="button"
                            class="btn btn-primary btn-sm icon icon-left"
                            data-bs-toggle="modal"
                            data-bs-target="#loanRequestModal"
                            @disabled($availableAssets->isEmpty())
                        >
                            <i class="bi bi-plus-circle"></i><span>Ajukan Peminjaman</span>
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    @if ($availableAssets->isEmpty())
                        <div class="alert alert-light-warning color-warning">
                            <i class="bi bi-exclamation-triangle me-1"></i>Belum ada aset yang tersedia untuk diajukan saat ini.
                        </div>
                    @endif

                    <div class="table-responsive">
                        <table class="table table-hover table-lg mb-0">
                            <thead>
                                <tr>
                                    <th>Aset</th>
                                    <th>Periode</th>
                                    <th>Status</th>
                                    <th>Catatan</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($loans as $loan)
                                    @php
                                        $loanBadge = match ($loan['status_variant']) {
                                            'danger' => 'bg-light-danger',
                                            'warning' => 'bg-light-warning',
                                            default => 'bg-light-success',
                                        };
                                    @endphp
                                    <tr>
                                        <td>
                                            <div>{{ $loan['asset_name'] }}</div>
                                            <small class="text-muted">{{ $loan['asset_code'] }}</small>
                                        </td>
                                        <td>
                                            <div>Pinjam: {{ $loan['loan_date'] }}</div>
                                            <small class="text-muted">{{ $loan['return_plan'] }}</small>
                                        </td>
                                        <td>
                                            <span class="badge {{ $loanBadge }}">{{ $loan['status'] }}</span>
                                        </td>
                                        <td><small class="text-muted">{{ $loan['status_note'] }}</small></td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center text-muted">Belum ada data peminjaman.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    @include('admin.partials.table-footer', [
                        'from' => $loans->firstItem() ?? 0,
                        'to' => $loans->lastItem() ?? 0,
                        'total' => $loanTotal,
                        'label' => 'peminjaman',
                    ])
                </div>
            </div>
        </section>
    </div>

    <div class="modal fade" id="loanRequestModal" tabindex="-1" aria-labelledby="loanRequestModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title" id="loanRequestModalLabel">Ajukan Peminjaman Aset</h5>
                        <small class="text-muted">Lengkapi form pengajuan sesuai kebutuhan pemakaian aset.</small>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="{{ route('pegawai.loans.store') }}" method="POST">
                    @csrf
                    <div class="modal-body">
                        @if ($errors->createLoan->any())
                            <div class="alert alert-light-danger color-danger">
                                <i class="bi bi-exclamation-circle me-1"></i>{{ $errors->createLoan->first() }}
                            </div>
                        @endif

                        <div class="row">
                            <div class="col-md-6 col-12">
                                <div class="form-group">
                                    <label for="asset_id">Aset</label>
                                    <select id="asset_id" name="asset_id" class="form-select @error('asset_id', 'createLoan') is-invalid @enderror" @disabled($availableAssets->isEmpty())>
                                        <option value="">Pilih aset yang tersedia</option>
                                        @foreach ($availableAssets as $asset)
                                            <option value="{{ $asset->id }}" @selected(old('asset_id') == $asset->id)>
                                                {{ $asset->name }} ({{ $asset->code }}) - {{ $asset->location?->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('asset_id', 'createLoan')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6 col-12">
                                <div class="form-group">
                                    <label for="status_info">Status Pengajuan</label>
                                    <input type="text" id="status_info" class="form-control" value="Menunggu persetujuan admin" readonly>
                                </div>
                            </div>
                            <div class="col-md-6 col-12">
                                <div class="form-group">
                                    <label for="loan_date">Tanggal Pinjam</label>
                                    <input
                                        type="date"
                                        id="loan_date"
                                        name="loan_date"
                                        class="form-control @error('loan_date', 'createLoan') is-invalid @enderror"
                                        value="{{ old('loan_date', now()->format('Y-m-d')) }}"
                                        @disabled($availableAssets->isEmpty())
                                    >
                                    @error('loan_date', 'createLoan')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6 col-12">
                                <div class="form-group">
                                    <label for="planned_return_date">Rencana Kembali</label>
                                    <input
                                        type="date"
                                        id="planned_return_date"
                                        name="planned_return_date"
                                        class="form-control @error('planned_return_date', 'createLoan') is-invalid @enderror"
                                        value="{{ old('planned_return_date') }}"
                                        @disabled($availableAssets->isEmpty())
                                    >
                                    @error('planned_return_date', 'createLoan')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="form-group mb-0">
                                    <label for="status_note">Keperluan Peminjaman</label>
                                    <textarea
                                        id="status_note"
                                        name="status_note"
                                        class="form-control @error('status_note', 'createLoan') is-invalid @enderror"
                                        rows="4"
                                        placeholder="Contoh: digunakan untuk kegiatan operasional bidang"
                                        @disabled($availableAssets->isEmpty())
                                    >{{ old('status_note') }}</textarea>
                                    @error('status_note', 'createLoan')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary icon icon-left" @disabled($availableAssets->isEmpty())>
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
            const loanDateInput = document.getElementById('loan_date');
            const plannedReturnInput = document.getElementById('planned_return_date');
            const modalElement = document.getElementById('loanRequestModal');

            if (loanDateInput && plannedReturnInput) {
                const syncReturnDateMin = () => {
                    plannedReturnInput.min = loanDateInput.value || '';
                };

                syncReturnDateMin();
                loanDateInput.addEventListener('change', syncReturnDateMin);
            }

            @if ($errors->createLoan->any())
                if (modalElement) {
                    new bootstrap.Modal(modalElement).show();
                }
            @endif
        });
    </script>
@endpush
