@extends('layouts.app')

@section('title', 'Pengembalian Aset')

@section('content')
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

            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-3">
                    <div>
                        <h4 class="card-title mb-1">Riwayat Pengembalian</h4>
                        <p class="mb-0 text-muted">Riwayat peminjaman yang sudah dikembalikan beserta detail pengembaliannya.</p>
                    </div>
                    <button type="button" class="btn btn-primary btn-sm icon icon-left" data-bs-toggle="modal" data-bs-target="#adminReturnCreateModal">
                        <i class="bi bi-plus-circle"></i><span>Tambah Pengembalian</span>
                    </button>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.returns.index') }}" method="GET" class="mb-4">
                        <div class="row g-3 align-items-end">
                            <div class="col-lg-3 col-md-6 col-12">
                                <label for="search" class="form-label">Cari Pengembalian</label>
                                <input type="search" id="search" name="search" class="form-control" placeholder="Aset, kode, pegawai, atau BA" value="{{ $filters['search'] ?? '' }}">
                            </div>
                            <div class="col-lg-3 col-md-6 col-12">
                                <label for="condition" class="form-label">Kondisi</label>
                                <select id="condition" name="condition" class="form-select">
                                    <option value="">Semua kondisi</option>
                                    @foreach ($conditions as $condition)
                                        <option value="{{ $condition }}" @selected(($filters['condition'] ?? '') === $condition)>{{ $condition }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-lg-2 col-md-6 col-12 d-flex gap-2">
                                <button type="submit" class="btn btn-primary icon">
                                    <i class="bi bi-funnel"></i>
                                </button>
                                @if ($hasActiveFilters)
                                    <a href="{{ route('admin.returns.index') }}" class="btn btn-light-secondary icon">
                                        <i class="bi bi-arrow-counterclockwise"></i>
                                    </a>
                                @endif
                            </div>
                        </div>
                    </form>
                    <div class="table-responsive">
                        <table class="table table-hover table-lg">
                            <thead>
                                <tr>
                                    <th>Aset</th>
                                    <th>Pegawai</th>
                                    <th>Riwayat Peminjaman</th>
                                    <th>Pengembalian</th>
                                    <th>Kondisi</th>
                                    <th>Berita Acara</th>
                                    <th class="text-end">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($returns as $return)
                                    @php
                                        $conditionBadge = match ($return['condition_variant']) {
                                            'warning' => 'bg-light-warning text-warning',
                                            'danger' => 'bg-light-danger text-danger',
                                            default => 'bg-light-success text-success',
                                        };
                                        $statusBadge = match ($return['status_variant']) {
                                            'danger' => 'bg-light-danger text-danger',
                                            'success' => 'bg-light-success text-success',
                                            default => 'bg-light-warning text-warning',
                                        };
                                        $isPending = in_array($return['status'], ['Menunggu', 'Menunggu Verifikasi']);
                                    @endphp
                                    <tr>
                                        <td>
                                            @foreach ($return['assets'] as $asset)
                                                <div @class(['mb-2' => ! $loop->last])>
                                                    <div>{{ $asset['name'] }}</div>
                                                    <small class="text-muted">{{ $asset['code'] }} · Jumlah: {{ $asset['quantity'] }}</small>
                                                </div>
                                            @endforeach
                                        </td>
                                        <td>
                                            <div>{{ $return['employee_name'] }}</div>
                                            <small class="text-muted">{{ $return['employee_email'] }}</small>
                                        </td>
                                        <td>
                                            <div>Pinjam: {{ $return['loan_date'] ?: '-' }}</div>
                                            <small class="text-muted d-block">Rencana kembali: {{ $return['planned_return_date'] ?: '-' }}</small>
                                            <small class="text-muted d-block">Total jumlah: {{ $return['total_quantity'] }} | Durasi: {{ $return['loan_duration'] }}</small>
                                        </td>
                                        <td>
                                            <div>Kembali: {{ $return['returned_at'] }}</div>
                                            <span class="badge {{ $statusBadge }}">{{ $return['status'] }}</span>
                                            <small class="text-muted d-block">{{ $return['verified_note'] ?: $return['status_note'] }}</small>
                                        </td>
                                        <td>
                                            @foreach ($return['assets'] as $asset)
                                                @php
                                                    $assetConditionBadge = match ($asset['condition_variant']) {
                                                        'warning' => 'bg-light-warning',
                                                        'danger' => 'bg-light-danger',
                                                        default => 'bg-light-success',
                                                    };
                                                @endphp
                                                <div @class(['mb-2' => ! $loop->last])>
                                                    <small class="text-muted d-block">{{ $asset['name'] }}</small>
                                                    <span class="badge {{ $assetConditionBadge }}">{{ $asset['condition'] }}</span>
                                                </div>
                                            @endforeach
                                        </td>
                                        <td>
                                            @foreach ($return['assets'] as $asset)
                                                <div>{{ $asset['report_number'] }}</div>
                                            @endforeach
                                            <small class="text-muted">{{ $return['report_note'] }}</small>
                                        </td>
                                        <td class="text-end">
                                            <div class="d-flex flex-wrap justify-content-end gap-2">
                                                @if ($return['status'] === 'Menunggu Verifikasi')
                                                    <button type="button" class="btn btn-sm btn-success icon icon-left" data-bs-toggle="modal" data-bs-target="#adminReturnVerifyModal-{{ $return['id'] }}">
                                                        <i class="bi bi-patch-check"></i><span>Verifikasi</span>
                                                    </button>
                                                @endif
                                                <a href="{{ route('admin.returns.letter.show', $return['id']) }}" class="btn btn-sm btn-light-primary icon icon-left">
                                                    <i class="bi bi-file-earmark-pdf"></i><span>Lihat Surat</span>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center text-muted">Belum ada riwayat pengembalian.</td>
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
                        'paginator' => $returns,
                    ])
                </div>
            </div>
        </section>
    </div>

    @foreach ($returns as $return)
        @if ($return['status'] === 'Menunggu Verifikasi')
            <div class="modal fade" id="adminReturnVerifyModal-{{ $return['id'] }}" tabindex="-1" aria-labelledby="adminReturnVerifyModalLabel-{{ $return['id'] }}" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content transaction-modal is-return">
                        <div class="modal-header transaction-modal-header">
                            <div class="transaction-form-title">
                                <span class="transaction-form-icon"><i class="bi bi-patch-check"></i></span>
                                <div>
                                    <h5 class="modal-title" id="adminReturnVerifyModalLabel-{{ $return['id'] }}">Verifikasi Pengembalian</h5>
                                    <small class="text-muted">{{ collect($return['assets'])->pluck('name')->join(', ') }} · {{ $return['employee_name'] }}</small>
                                </div>
                            </div>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>

                        <form action="{{ route('admin.returns.verify', $return['id']) }}" method="POST" data-admin-return-verify-form>
                            @csrf
                            @method('PUT')

                            <div class="modal-body transaction-modal-body">
                                <div class="alert alert-light-warning color-warning mb-4">
                                    <i class="bi bi-exclamation-triangle me-1"></i>Pastikan aset fisik sudah diterima sebelum verifikasi.
                                </div>

                                <div class="form-group transaction-field">
                                    <label for="admin_return_verify_condition_{{ $return['id'] }}">Kondisi Setelah Diperiksa</label>
                                    <div class="transaction-input-shell">
                                        <select id="admin_return_verify_condition_{{ $return['id'] }}" name="condition" class="form-select" required>
                                            @foreach ($conditions as $condition)
                                                <option value="{{ $condition }}" @selected($return['condition'] === $condition)>{{ $condition }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>

                                <div class="form-group transaction-field mb-0">
                                    <label for="admin_return_verify_note_{{ $return['id'] }}">Catatan Verifikasi</label>
                                    <div class="transaction-input-shell transaction-textarea-shell">
                                        <textarea id="admin_return_verify_note_{{ $return['id'] }}" name="verified_note" class="form-control" rows="3" maxlength="255" placeholder="Contoh: aset sudah diterima dan diperiksa"></textarea>
                                    </div>
                                </div>
                            </div>

                            <div class="modal-footer transaction-modal-footer">
                                <button type="button" class="btn btn-light-secondary" data-bs-dismiss="modal">Batal</button>
                                <button type="submit" class="btn btn-success icon icon-left">
                                    <i class="bi bi-patch-check"></i><span>Verifikasi</span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endif
    @endforeach

    @include('admin.partials.create-modal', ['resource' => 'return'])
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('[data-admin-return-verify-form]').forEach(function (form) {
                form.addEventListener('submit', function (event) {
                    if (form.dataset.submitting === 'true') {
                        event.preventDefault();
                        return;
                    }

                    form.dataset.submitting = 'true';
                    const submitButton = form.querySelector('button[type="submit"]');

                    if (submitButton) {
                        submitButton.disabled = true;
                        submitButton.innerHTML = '<span class="spinner-border spinner-border-sm me-1" aria-hidden="true"></span><span>Memverifikasi...</span>';
                    }
                });
            });
        });
    </script>
@endpush
