@extends('layouts.app')

@section('title', 'Peminjaman Pegawai')

@section('content')
    <div class="page-heading">
        @include('pegawai.partials.page-heading', [
            'title' => 'Peminjaman',
            'breadcrumb' => 'Peminjaman',
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
                        <h3 class="mb-0">Daftar Peminjaman</h3>
                    </div>
                </div>
                <div class="card-body">
                    @php
                        $loanStatusFilters = ['Menunggu', 'Disetujui', 'Ditolak'];
                    @endphp

                    @if ($availableAssets->isEmpty())
                        <div class="alert alert-light-warning color-warning">
                            <i class="bi bi-exclamation-triangle me-1"></i>Belum ada aset yang tersedia untuk diajukan saat ini.
                        </div>
                    @endif

                    <div class="row g-3 align-items-end mb-4">
                        <div class="col-lg-6 col-12">
                            <button
                                type="button"
                                class="btn btn-primary btn-sm icon icon-left pegawai-toolbar-add"
                                data-bs-toggle="modal"
                                data-bs-target="#loanRequestModal"
                                @disabled($availableAssets->isEmpty())
                            >
                                <i class="bi bi-plus-circle"></i><span>Ajukan Peminjaman</span>
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
                                            id="pegawaiLoanSearchInput"
                                            class="form-control"
                                            placeholder="Cari aset atau kode aset"
                                        >
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <button
                                        class="btn btn-light-secondary pegawai-filter-toggle"
                                        type="button"
                                        id="pegawaiLoanFilterButton"
                                        aria-expanded="false"
                                        aria-controls="pegawaiLoanFilterPanel"
                                        aria-label="Filter peminjaman"
                                        title="Filter peminjaman"
                                    >
                                        <i class="bi bi-funnel"></i>
                                        <span class="visually-hidden">Filter</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div id="pegawaiLoanFilterPanel" class="pegawai-filter-panel border rounded p-3 mb-4 d-none">
                        <div class="row g-3 align-items-end">
                            <div class="col-lg-5 col-md-6 col-12">
                                <label for="pegawaiLoanStatusFilter" class="form-label">Status</label>
                                <select id="pegawaiLoanStatusFilter" class="form-select">
                                    <option value="">Semua Status</option>
                                    @foreach ($loanStatusFilters as $loanStatusFilter)
                                        <option value="{{ $loanStatusFilter }}">{{ $loanStatusFilter }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-lg-5 col-md-6 col-12">
                                <label for="pegawaiLoanLetterFilter" class="form-label">Surat</label>
                                <select id="pegawaiLoanLetterFilter" class="form-select">
                                    <option value="">Semua Surat</option>
                                    <option value="tersedia">Surat tersedia</option>
                                    <option value="belum tersedia">Belum tersedia</option>
                                </select>
                            </div>
                            <div class="col-lg-2 col-md-6 col-12">
                                <button type="button" id="pegawaiLoanResetFilter" class="btn btn-light-secondary icon w-100" aria-label="Reset filter peminjaman">
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
                                    <th>Periode</th>
                                    <th>Status</th>
                                    <th>Catatan</th>
                                    <th>Surat</th>
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
                                        $searchSource = strtolower(trim(($loan['asset_name'] ?? '').' '.($loan['asset_code'] ?? '').' '.($loan['letter_number'] ?? '')));
                                        $statusValue = strtolower($loan['status'] ?? '');
                                        $letterValue = $loan['letter_url'] ? 'tersedia' : 'belum tersedia';
                                    @endphp
                                    <tr
                                        data-pegawai-loan-row
                                        data-pegawai-loan-search="{{ $searchSource }}"
                                        data-pegawai-loan-status="{{ $statusValue }}"
                                        data-pegawai-loan-letter="{{ $letterValue }}"
                                    >
                                        <td>
                                            <div>{{ $loan['asset_name'] }}</div>
                                            <small class="text-muted">{{ $loan['asset_code'] }}</small>
                                            <div><small class="text-muted">Jumlah: {{ $loan['quantity'] }}</small></div>
                                        </td>
                                        <td>
                                            <div>Pinjam: {{ $loan['loan_date'] }}</div>
                                            <small class="text-muted">{{ $loan['return_plan'] }}</small>
                                        </td>
                                        <td>
                                            <span class="badge {{ $loanBadge }}">{{ $loan['status'] }}</span>
                                        </td>
                                        <td><small class="text-muted">{{ $loan['status_note'] }}</small></td>
                                        <td>
                                            @if ($loan['letter_url'])
                                                <div class="fw-semibold">{{ $loan['letter_number'] }}</div>
                                                <div class="d-flex flex-wrap gap-2 mt-2">
                                                    <a href="{{ $loan['letter_url'] }}" class="btn btn-sm btn-light-primary icon icon-left">
                                                        <i class="bi bi-file-earmark-text"></i><span>Lihat Surat</span>
                                                    </a>
                                                    <a href="{{ $loan['letter_download_url'] }}" class="btn btn-sm btn-light-secondary icon icon-left">
                                                        <i class="bi bi-download"></i><span>Download PDF</span>
                                                    </a>
                                                </div>
                                            @else
                                                <small class="text-muted">Belum tersedia</small>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center text-muted">Belum ada data peminjaman.</td>
                                    </tr>
                                @endforelse
                                @if ($loans->count() > 0)
                                    <tr id="pegawaiLoanEmptyRow" class="d-none">
                                        <td colspan="5" class="text-center text-muted">Tidak ada data yang sesuai</td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                    @include('pegawai.partials.table-footer', ['paginator' => $loans])
                </div>
            </div>
        </section>
    </div>

    <div class="modal fade" id="loanRequestModal" tabindex="-1" aria-labelledby="loanRequestModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content transaction-modal is-loan">
                <div class="modal-header transaction-modal-header">
                    <div class="transaction-form-title">
                        <span class="transaction-form-icon">
                            <i class="bi bi-box-arrow-up-right"></i>
                        </span>
                        <div>
                            <h5 class="modal-title" id="loanRequestModalLabel">Ajukan Peminjaman Aset</h5>
                            <small class="text-muted">Lengkapi pengajuan sesuai kebutuhan pemakaian aset.</small>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="{{ route('pegawai.loans.store') }}" method="POST">
                    @csrf
                    <div class="modal-body transaction-modal-body">
                        @if ($errors->createLoan->any())
                            <div class="alert alert-light-danger color-danger">
                                <i class="bi bi-exclamation-circle me-1"></i>{{ $errors->createLoan->first() }}
                            </div>
                        @endif

                        <div class="transaction-form-section">
                            <div class="transaction-section-heading">
                                <span><i class="bi bi-clipboard-data"></i></span>
                                <div>
                                    <h5>Data Pengajuan</h5>
                                    <small>Aset dan status pengajuan</small>
                                </div>
                            </div>
                            <div class="row g-3">
                                <div class="col-md-6 col-12">
                                    <div class="form-group transaction-field">
                                        <label for="asset_id">Aset</label>
                                        <div class="transaction-input-shell">
                                            <span class="transaction-input-icon"><i class="bi bi-archive"></i></span>
                                            <select id="asset_id" name="asset_id" class="form-select @error('asset_id', 'createLoan') is-invalid @enderror" @disabled($availableAssets->isEmpty())>
                                                <option value="">Pilih aset yang tersedia</option>
                                                @foreach ($availableAssets as $asset)
                                                    <option value="{{ $asset->id }}" @selected(old('asset_id') == $asset->id)>
                                                        {{ $asset->name }} ({{ $asset->code }}) - Stok {{ $asset->quantity }} - {{ $asset->location?->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                        @error('asset_id', 'createLoan')
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-3 col-12">
                                    <div class="form-group transaction-field">
                                        <label for="quantity">Jumlah</label>
                                        <div class="transaction-input-shell">
                                            <span class="transaction-input-icon"><i class="bi bi-123"></i></span>
                                            <input
                                                type="number"
                                                min="1"
                                                step="1"
                                                id="quantity"
                                                name="quantity"
                                                class="form-control @error('quantity', 'createLoan') is-invalid @enderror"
                                                value="{{ old('quantity', 1) }}"
                                                @disabled($availableAssets->isEmpty())
                                            >
                                        </div>
                                        @error('quantity', 'createLoan')
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-3 col-12">
                                    <div class="form-group transaction-field">
                                        <label for="status_info">Status Pengajuan</label>
                                        <div class="transaction-input-shell">
                                            <span class="transaction-input-icon"><i class="bi bi-hourglass-split"></i></span>
                                            <input type="text" id="status_info" class="form-control" value="Menunggu persetujuan admin" readonly>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="transaction-form-section">
                            <div class="transaction-section-heading">
                                <span><i class="bi bi-calendar-week"></i></span>
                                <div>
                                    <h5>Periode</h5>
                                    <small>Tanggal pinjam dan rencana kembali</small>
                                </div>
                            </div>
                            <div class="row g-3">
                                <div class="col-md-6 col-12">
                                    <div class="form-group transaction-field">
                                        <label for="loan_date">Tanggal Pinjam</label>
                                        <div class="transaction-input-shell">
                                            <span class="transaction-input-icon"><i class="bi bi-calendar-event"></i></span>
                                            <input
                                                type="date"
                                                id="loan_date"
                                                name="loan_date"
                                                class="form-control @error('loan_date', 'createLoan') is-invalid @enderror"
                                                value="{{ old('loan_date', now()->format('Y-m-d')) }}"
                                                @disabled($availableAssets->isEmpty())
                                            >
                                        </div>
                                        @error('loan_date', 'createLoan')
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-6 col-12">
                                    <div class="form-group transaction-field">
                                        <label for="planned_return_date">Rencana Kembali</label>
                                        <div class="transaction-input-shell">
                                            <span class="transaction-input-icon"><i class="bi bi-calendar-check"></i></span>
                                            <input
                                                type="date"
                                                id="planned_return_date"
                                                name="planned_return_date"
                                                class="form-control @error('planned_return_date', 'createLoan') is-invalid @enderror"
                                                value="{{ old('planned_return_date') }}"
                                                @disabled($availableAssets->isEmpty())
                                            >
                                        </div>
                                        @error('planned_return_date', 'createLoan')
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
                                    <h5>Keperluan</h5>
                                    <small>Catatan pemakaian aset</small>
                                </div>
                            </div>
                            <div class="form-group transaction-field mb-0">
                                <label for="status_note">Keperluan Peminjaman</label>
                                <div class="transaction-input-shell transaction-textarea-shell">
                                    <span class="transaction-input-icon"><i class="bi bi-pencil-square"></i></span>
                                    <textarea
                                        id="status_note"
                                        name="status_note"
                                        class="form-control @error('status_note', 'createLoan') is-invalid @enderror"
                                        rows="4"
                                        placeholder="Contoh: digunakan untuk kegiatan operasional bidang"
                                        @disabled($availableAssets->isEmpty())
                                    >{{ old('status_note') }}</textarea>
                                </div>
                                @error('status_note', 'createLoan')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer transaction-modal-footer">
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
            const searchInput = document.getElementById('pegawaiLoanSearchInput');
            const statusFilter = document.getElementById('pegawaiLoanStatusFilter');
            const letterFilter = document.getElementById('pegawaiLoanLetterFilter');
            const filterButton = document.getElementById('pegawaiLoanFilterButton');
            const filterPanel = document.getElementById('pegawaiLoanFilterPanel');
            const resetFilterButton = document.getElementById('pegawaiLoanResetFilter');
            const rows = Array.from(document.querySelectorAll('[data-pegawai-loan-row]'));
            const emptyRow = document.getElementById('pegawaiLoanEmptyRow');

            if (filterButton && filterPanel) {
                filterButton.addEventListener('click', function () {
                    const isOpening = filterPanel.classList.contains('d-none');

                    filterPanel.classList.toggle('d-none', !isOpening);
                    filterButton.classList.toggle('btn-primary', isOpening);
                    filterButton.classList.toggle('btn-light-secondary', !isOpening);
                    filterButton.setAttribute('aria-expanded', isOpening ? 'true' : 'false');
                });
            }

            if (loanDateInput && plannedReturnInput) {
                const syncReturnDateMin = () => {
                    plannedReturnInput.min = loanDateInput.value || '';
                };

                syncReturnDateMin();
                loanDateInput.addEventListener('change', syncReturnDateMin);
            }

            if (searchInput && statusFilter && letterFilter && rows.length > 0) {
                const normalize = (value) => (value || '').toString().trim().toLowerCase();

                const applyFilters = () => {
                    const keyword = normalize(searchInput.value);
                    const status = normalize(statusFilter.value);
                    const letter = normalize(letterFilter.value);
                    let visibleCount = 0;

                    rows.forEach((row) => {
                        const matchesKeyword = row.dataset.pegawaiLoanSearch.includes(keyword);
                        const matchesStatus = !status || row.dataset.pegawaiLoanStatus === status;
                        const matchesLetter = !letter || row.dataset.pegawaiLoanLetter === letter;
                        const isVisible = matchesKeyword && matchesStatus && matchesLetter;

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
                letterFilter.addEventListener('change', applyFilters);

                if (resetFilterButton) {
                    resetFilterButton.addEventListener('click', function () {
                        searchInput.value = '';
                        statusFilter.value = '';
                        letterFilter.value = '';
                        applyFilters();
                    });
                }
            }

            @if ($errors->createLoan->any())
                if (modalElement) {
                    new bootstrap.Modal(modalElement).show();
                }
            @endif
        });
    </script>
@endpush
