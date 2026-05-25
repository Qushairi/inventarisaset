@extends('layouts.app')

@section('title', 'Pengembalian Pegawai')

@section('content')
    <div class="page-heading">
        @include('pegawai.partials.page-heading', [
            'title' => 'Pengembalian',
            'breadcrumb' => 'Pengembalian',
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

            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-3">
                    <div>
                        <h3 class="mb-0">Daftar Pengembalian</h3>
                    </div>
                </div>
                <div class="card-body">
                    @php
                        $returnStatusFilters = ['Menunggu Verifikasi', 'Terverifikasi'];
                    @endphp

                    @if ($returnableLoans->isEmpty())
                        <div class="alert alert-light-warning color-warning">
                            <i class="bi bi-exclamation-triangle me-1"></i>Belum ada peminjaman yang siap diajukan untuk pengembalian.
                        </div>
                    @endif

                    <div class="row g-3 align-items-end mb-4">
                        <div class="col-lg-6 col-12">
                            <button
                                type="button"
                                class="btn btn-primary btn-sm icon icon-left pegawai-toolbar-add"
                                data-bs-toggle="modal"
                                data-bs-target="#returnRequestModal"
                                @disabled($returnableLoans->isEmpty())
                            >
                                <i class="bi bi-plus-circle"></i><span>Ajukan Pengembalian</span>
                            </button>
                        </div>
                        <div class="col-lg-6 col-12">
                            <div class="row g-2 align-items-end justify-content-lg-end">
                                <div class="col-md-8 col-12">
                                    <div class="input-group">
                                        <span class="input-group-text">
                                            <i class="bi bi-search"></i>
                                        </span>
                                        <input
                                            type="search"
                                            id="pegawaiReturnSearchInput"
                                            class="form-control"
                                            placeholder="Cari aset atau nomor berita acara"
                                        >
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <button
                                        class="btn btn-light-secondary pegawai-filter-toggle"
                                        type="button"
                                        id="pegawaiReturnFilterButton"
                                        aria-expanded="false"
                                        aria-controls="pegawaiReturnFilterPanel"
                                        aria-label="Filter pengembalian"
                                        title="Filter pengembalian"
                                    >
                                        <i class="bi bi-funnel"></i>
                                        <span class="visually-hidden">Filter</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div id="pegawaiReturnFilterPanel" class="pegawai-filter-panel border rounded p-3 mb-4 d-none">
                        <div class="row g-3 align-items-end">
                            <div class="col-lg-5 col-md-6 col-12">
                                <label for="pegawaiReturnStatusFilter" class="form-label">Status</label>
                                <select id="pegawaiReturnStatusFilter" class="form-select">
                                    <option value="">Semua Status</option>
                                    @foreach ($returnStatusFilters as $returnStatusFilter)
                                        <option value="{{ $returnStatusFilter }}">{{ $returnStatusFilter }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-lg-5 col-md-6 col-12">
                                <label for="pegawaiReturnConditionFilter" class="form-label">Kondisi</label>
                                <select id="pegawaiReturnConditionFilter" class="form-select">
                                    <option value="">Semua Kondisi</option>
                                    @foreach ($conditions as $condition)
                                        <option value="{{ $condition }}">{{ $condition }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-lg-2 col-md-6 col-12">
                                <button type="button" id="pegawaiReturnResetFilter" class="btn btn-light-secondary icon w-100" aria-label="Reset filter pengembalian">
                                    <i class="bi bi-arrow-counterclockwise"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover table-lg mb-0">
                            <thead>
                                <tr>
                                    <th>Aset</th>
                                    <th>Tanggal Kembali</th>
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
                                        $searchSource = strtolower(trim(($return['asset_name'] ?? '').' '.($return['asset_code'] ?? '').' '.($return['report_number'] ?? '')));
                                    @endphp
                                    <tr
                                        data-pegawai-return-row
                                        data-pegawai-return-search="{{ $searchSource }}"
                                        data-pegawai-return-status="{{ strtolower($return['status'] ?? '') }}"
                                        data-pegawai-return-condition="{{ strtolower($return['condition'] ?? '') }}"
                                    >
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
                                @if ($returns->count() > 0)
                                    <tr id="pegawaiReturnEmptyRow" class="d-none">
                                        <td colspan="5" class="text-center text-muted">Tidak ada data yang sesuai</td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                    @include('pegawai.partials.table-footer', ['paginator' => $returns])
                </div>
            </div>
        </section>
    </div>

    <div class="modal fade" id="returnRequestModal" tabindex="-1" aria-labelledby="returnRequestModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content transaction-modal is-return">
                <div class="modal-header transaction-modal-header">
                    <div class="transaction-form-title">
                        <span class="transaction-form-icon">
                            <i class="bi bi-arrow-return-left"></i>
                        </span>
                        <div>
                            <h5 class="modal-title" id="returnRequestModalLabel">Ajukan Pengembalian Aset</h5>
                            <small class="text-muted">Pilih peminjaman yang sudah selesai digunakan.</small>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="{{ route('pegawai.returns.store') }}" method="POST">
                    @csrf
                    <div class="modal-body transaction-modal-body">
                        @if ($errors->createReturn->any())
                            <div class="alert alert-light-danger color-danger">
                                <i class="bi bi-exclamation-circle me-1"></i>{{ $errors->createReturn->first() }}
                            </div>
                        @endif

                        <div class="transaction-form-section">
                            <div class="transaction-section-heading">
                                <span><i class="bi bi-clipboard-data"></i></span>
                                <div>
                                    <h5>Data Pengembalian</h5>
                                    <small>Peminjaman dan status pengajuan</small>
                                </div>
                            </div>
                            <div class="row g-3">
                                <div class="col-md-6 col-12">
                                    <div class="form-group transaction-field">
                                        <label for="loan_id">Data Peminjaman</label>
                                        <div class="transaction-input-shell">
                                            <span class="transaction-input-icon"><i class="bi bi-link-45deg"></i></span>
                                            <select id="loan_id" name="loan_id" class="form-select @error('loan_id', 'createReturn') is-invalid @enderror" @disabled($returnableLoans->isEmpty())>
                                                <option value="">Pilih peminjaman yang akan dikembalikan</option>
                                                @foreach ($returnableLoans as $loan)
                                                    <option value="{{ $loan->id }}" @selected(old('loan_id') == $loan->id)>
                                                        {{ $loan->asset?->name }} ({{ $loan->asset?->code }}) - Pinjam {{ optional($loan->loan_date)->format('d/m/Y') }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                        @error('loan_id', 'createReturn')
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-6 col-12">
                                    <div class="form-group transaction-field">
                                        <label for="return_status_info">Status Pengajuan</label>
                                        <div class="transaction-input-shell">
                                            <span class="transaction-input-icon"><i class="bi bi-hourglass-split"></i></span>
                                            <input type="text" id="return_status_info" class="form-control" value="Menunggu verifikasi admin" readonly>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="transaction-form-section">
                            <div class="transaction-section-heading">
                                <span><i class="bi bi-clipboard-check"></i></span>
                                <div>
                                    <h5>Pemeriksaan</h5>
                                    <small>Tanggal kembali dan kondisi aset</small>
                                </div>
                            </div>
                            <div class="row g-3">
                                <div class="col-md-6 col-12">
                                    <div class="form-group transaction-field">
                                        <label for="returned_at">Tanggal Kembali</label>
                                        <div class="transaction-input-shell">
                                            <span class="transaction-input-icon"><i class="bi bi-calendar-check"></i></span>
                                            <input
                                                type="date"
                                                id="returned_at"
                                                name="returned_at"
                                                class="form-control @error('returned_at', 'createReturn') is-invalid @enderror"
                                                value="{{ old('returned_at', now()->format('Y-m-d')) }}"
                                                @disabled($returnableLoans->isEmpty())
                                            >
                                        </div>
                                        @error('returned_at', 'createReturn')
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-6 col-12">
                                    <div class="form-group transaction-field">
                                        <label for="condition">Kondisi Aset</label>
                                        <div class="transaction-input-shell">
                                            <span class="transaction-input-icon"><i class="bi bi-tools"></i></span>
                                            <select id="condition" name="condition" class="form-select @error('condition', 'createReturn') is-invalid @enderror" @disabled($returnableLoans->isEmpty())>
                                                <option value="">Pilih kondisi aset</option>
                                                @foreach ($conditions as $condition)
                                                    <option value="{{ $condition }}" @selected(old('condition') === $condition)>{{ $condition }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        @error('condition', 'createReturn')
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="transaction-form-section mb-0">
                            <div class="transaction-section-heading">
                                <span><i class="bi bi-chat-left-text"></i></span>
                                <div>
                                    <h5>Catatan</h5>
                                    <small>Informasi tambahan pengembalian</small>
                                </div>
                            </div>
                            <div class="form-group transaction-field mb-0">
                                <label for="report_note">Catatan Pengembalian</label>
                                <div class="transaction-input-shell transaction-textarea-shell">
                                    <span class="transaction-input-icon"><i class="bi bi-pencil-square"></i></span>
                                    <textarea
                                        id="report_note"
                                        name="report_note"
                                        class="form-control @error('report_note', 'createReturn') is-invalid @enderror"
                                        rows="4"
                                        placeholder="Contoh: aset sudah selesai digunakan dan siap dicek admin"
                                        @disabled($returnableLoans->isEmpty())
                                    >{{ old('report_note') }}</textarea>
                                </div>
                                @error('report_note', 'createReturn')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer transaction-modal-footer">
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
            const searchInput = document.getElementById('pegawaiReturnSearchInput');
            const statusFilter = document.getElementById('pegawaiReturnStatusFilter');
            const conditionFilter = document.getElementById('pegawaiReturnConditionFilter');
            const filterButton = document.getElementById('pegawaiReturnFilterButton');
            const filterPanel = document.getElementById('pegawaiReturnFilterPanel');
            const resetFilterButton = document.getElementById('pegawaiReturnResetFilter');
            const rows = Array.from(document.querySelectorAll('[data-pegawai-return-row]'));
            const emptyRow = document.getElementById('pegawaiReturnEmptyRow');

            if (filterButton && filterPanel) {
                filterButton.addEventListener('click', function () {
                    const isOpening = filterPanel.classList.contains('d-none');

                    filterPanel.classList.toggle('d-none', !isOpening);
                    filterButton.classList.toggle('btn-primary', isOpening);
                    filterButton.classList.toggle('btn-light-secondary', !isOpening);
                    filterButton.setAttribute('aria-expanded', isOpening ? 'true' : 'false');
                });
            }

            if (searchInput && statusFilter && conditionFilter && rows.length > 0) {
                const normalize = (value) => (value || '').toString().trim().toLowerCase();

                const applyFilters = () => {
                    const keyword = normalize(searchInput.value);
                    const status = normalize(statusFilter.value);
                    const condition = normalize(conditionFilter.value);
                    let visibleCount = 0;

                    rows.forEach((row) => {
                        const matchesKeyword = row.dataset.pegawaiReturnSearch.includes(keyword);
                        const matchesStatus = !status || row.dataset.pegawaiReturnStatus === status;
                        const matchesCondition = !condition || row.dataset.pegawaiReturnCondition === condition;
                        const isVisible = matchesKeyword && matchesStatus && matchesCondition;

                        row.classList.toggle('d-none', !isVisible);

                        if (isVisible) {
                            visibleCount += 1;
                        }
                    });

                    if (emptyRow) {
                        emptyRow.classList.toggle('d-none', visibleCount > 0);
                    }
                };

                searchInput.addEventListener('input', applyFilters);
                statusFilter.addEventListener('change', applyFilters);
                conditionFilter.addEventListener('change', applyFilters);

                if (resetFilterButton) {
                    resetFilterButton.addEventListener('click', function () {
                        searchInput.value = '';
                        statusFilter.value = '';
                        conditionFilter.value = '';
                        applyFilters();
                    });
                }
            }

            @if ($errors->createReturn->any())
                if (modalElement) {
                    new bootstrap.Modal(modalElement).show();
                }
            @endif
        });
    </script>
@endpush
