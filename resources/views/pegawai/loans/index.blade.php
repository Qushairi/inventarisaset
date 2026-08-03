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
                        <button
                            type="button"
                            class="btn btn-primary icon icon-left"
                            data-bs-toggle="modal"
                            data-bs-target="#loanRequestModal"
                            @disabled($availableAssets->isEmpty())
                        >
                            <i class="bi bi-plus-circle-fill"></i>
                            <span>Ajukan Peminjaman</span>
                        </button>
                    </div>

                    <div class="pegawai-toolbar-wrapper ms-auto">
                        <!-- Sleek Search Input Box -->
                        <div class="pegawai-search-box">
                            <i class="bi bi-search pegawai-search-icon"></i>
                            <input
                                type="search"
                                id="pegawaiLoanSearchInput"
                                class="form-control pegawai-search-input"
                                placeholder="Cari aset atau kode aset..."
                            >
                        </div>

                        @php
                            $loanStatusFilters = ['Menunggu', 'Disetujui', 'Ditolak'];
                        @endphp

                        <!-- Floating Filter Dropdown Popover -->
                        <div class="dropdown">
                            <button
                                class="btn pegawai-filter-btn d-inline-flex align-items-center gap-2"
                                type="button"
                                id="pegawaiLoanFilterDropdownBtn"
                                data-bs-toggle="dropdown"
                                data-bs-auto-close="outside"
                                aria-expanded="false"
                                title="Filter Peminjaman"
                            >
                                <i class="bi bi-funnel-fill text-primary"></i>
                                <span class="fw-semibold">Filter</span>
                            </button>

                            <div class="dropdown-menu dropdown-menu-end p-3 shadow-lg border-0 pegawai-asset-filter-menu" aria-labelledby="pegawaiLoanFilterDropdownBtn">
                                <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
                                    <h6 class="mb-0 fw-bold text-dark"><i class="bi bi-sliders me-1 text-primary"></i> Filter Peminjaman</h6>
                                    <button type="button" id="pegawaiLoanResetFilter" class="btn btn-sm btn-link text-danger p-0 text-decoration-none d-none">
                                        <i class="bi bi-arrow-counterclockwise me-1"></i>Reset
                                    </button>
                                </div>

                                <div class="row g-2">
                                    <div class="col-12">
                                        <label for="pegawaiLoanStatusFilter" class="form-label text-muted small font-semibold">Status Peminjaman</label>
                                        <select id="pegawaiLoanStatusFilter" class="form-select form-select-sm">
                                            <option value="">Semua Status</option>
                                            @foreach ($loanStatusFilters as $loanStatusFilter)
                                                <option value="{{ $loanStatusFilter }}">{{ $loanStatusFilter }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    @if ($availableAssets->isEmpty())
                        <div class="alert alert-light-warning color-warning mb-4">
                            <i class="bi bi-exclamation-triangle me-1"></i>Belum ada aset yang tersedia untuk diajukan saat ini.
                        </div>
                    @endif

                    <div class="table-responsive">
                        <table class="table table-hover align-middle table-lg mb-0">
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
                                            'danger' => 'pegawai-badge-danger',
                                            'warning' => 'pegawai-badge-warning',
                                            default => 'pegawai-badge-success',
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
                                            <span class="pegawai-badge {{ $loanBadge }}">
                                                <span class="pegawai-badge-dot"></span>
                                                <span>{{ $loan['status'] }}</span>
                                            </span>
                                        </td>
                                        <td><small class="text-muted">{{ $loan['status_note'] }}</small></td>
                                        <td>
                                            @if ($loan['letter_url'])
                                                <div class="fw-semibold text-secondary small mb-1.5">{{ $loan['letter_number'] }}</div>
                                                <div class="d-flex flex-wrap gap-2">
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
                <form action="{{ route('pegawai.loans.store') }}" method="POST" data-swal-confirm data-swal-icon="question" data-swal-title="Kirim pengajuan peminjaman?" data-swal-text="Pastikan aset, jumlah, dan periode peminjaman sudah benar." data-swal-confirm-text="Ya, kirim pengajuan" data-swal-confirm-color="#435ebe">
                    @csrf
                    <div class="modal-body transaction-modal-body">
                        @if ($errors->createLoan->any())
                            <div class="alert alert-light-danger color-danger">
                                <i class="bi bi-exclamation-circle me-1"></i>{{ $errors->createLoan->first() }}
                            </div>
                        @endif

                        <div class="transaction-form-section">
                            <div class="transaction-section-heading d-flex justify-content-between align-items-center flex-wrap gap-2">
                                <div class="d-flex align-items-center gap-2">
                                    <span><i class="bi bi-boxes"></i></span>
                                    <div>
                                        <h5 class="mb-0">Daftar Barang yang Dipinjam</h5>
                                        <small class="text-muted">Tambahkan satu atau lebih barang dalam transaksi ini</small>
                                    </div>
                                </div>
                                <button type="button" id="btnAddLoanItem" class="btn btn-sm btn-light-primary icon icon-left" @disabled($availableAssets->isEmpty())>
                                    <i class="bi bi-plus-circle"></i><span>Tambah Barang</span>
                                </button>
                            </div>

                            <div id="loanItemsContainer" class="d-flex flex-column gap-3 mt-3">
                                <div class="loan-item-row border rounded-3 p-3 bg-light position-relative">
                                    <div class="row g-3 align-items-end">
                                        <div class="col-md-7 col-12">
                                            <label class="form-label font-semibold small mb-1">Pilih Barang Aset</label>
                                            <div class="transaction-input-shell">
                                                <span class="transaction-input-icon"><i class="bi bi-archive"></i></span>
                                                <select name="items[0][asset_id]" class="form-select loan-asset-select" @disabled($availableAssets->isEmpty()) required>
                                                    <option value="">Pilih barang aset yang tersedia...</option>
                                                    @foreach ($availableAssets as $asset)
                                                        <option value="{{ $asset->id }}">
                                                            {{ $asset->name }} ({{ $asset->code }}) - Stok {{ $asset->quantity }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-3 col-8">
                                            <label class="form-label font-semibold small mb-1">Jumlah</label>
                                            <div class="transaction-input-shell">
                                                <span class="transaction-input-icon"><i class="bi bi-123"></i></span>
                                                <input
                                                    type="number"
                                                    min="1"
                                                    step="1"
                                                    name="items[0][quantity]"
                                                    class="form-control loan-quantity-input"
                                                    value="1"
                                                    @disabled($availableAssets->isEmpty())
                                                    required
                                                >
                                            </div>
                                        </div>
                                        <div class="col-md-2 col-4 text-end">
                                            <button type="button" class="btn btn-light-danger icon btn-remove-item w-100" title="Hapus Barang" disabled>
                                                <i class="bi bi-trash"></i>
                                            </button>
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
            const resetFilterButton = document.getElementById('pegawaiLoanResetFilter');
            const filterCountBadge = document.getElementById('pegawaiLoanFilterCountBadge');
            const filterMenu = document.querySelector('.pegawai-asset-filter-menu');
            const rows = Array.from(document.querySelectorAll('[data-pegawai-loan-row]'));
            const emptyRow = document.getElementById('pegawaiLoanEmptyRow');

            if (filterMenu) {
                ['click', 'mousedown', 'pointerdown'].forEach((evtType) => {
                    filterMenu.addEventListener(evtType, function (e) {
                        e.stopPropagation();
                    });
                });
            }

            if (loanDateInput && plannedReturnInput) {
                const syncReturnDateMin = () => {
                    plannedReturnInput.min = loanDateInput.value || '';
                };

                syncReturnDateMin();
                loanDateInput.addEventListener('change', syncReturnDateMin);
            }

            // Dynamic Multi-Item Loan Builder Script
            let itemRowIndex = 1;
            const btnAddLoanItem = document.getElementById('btnAddLoanItem');
            const loanItemsContainer = document.getElementById('loanItemsContainer');

            if (btnAddLoanItem && loanItemsContainer) {
                const updateRemoveButtons = () => {
                    const rows = loanItemsContainer.querySelectorAll('.loan-item-row');
                    rows.forEach(r => {
                        const btnRemove = r.querySelector('.btn-remove-item');
                        if (btnRemove) {
                            btnRemove.disabled = rows.length <= 1;
                        }
                    });
                };

                btnAddLoanItem.addEventListener('click', function () {
                    const firstRow = loanItemsContainer.querySelector('.loan-item-row');
                    if (!firstRow) return;

                    const newRow = firstRow.cloneNode(true);
                    
                    const select = newRow.querySelector('.loan-asset-select');
                    const qtyInput = newRow.querySelector('.loan-quantity-input');
                    const removeBtn = newRow.querySelector('.btn-remove-item');

                    if (select) {
                        select.name = `items[${itemRowIndex}][asset_id]`;
                        select.value = '';
                    }
                    if (qtyInput) {
                        qtyInput.name = `items[${itemRowIndex}][quantity]`;
                        qtyInput.value = '1';
                    }
                    if (removeBtn) {
                        removeBtn.disabled = false;
                        removeBtn.addEventListener('click', function () {
                            newRow.remove();
                            updateRemoveButtons();
                        });
                    }

                    loanItemsContainer.appendChild(newRow);
                    itemRowIndex++;
                    updateRemoveButtons();
                });

                loanItemsContainer.querySelectorAll('.btn-remove-item').forEach(btn => {
                    btn.addEventListener('click', function () {
                        const row = btn.closest('.loan-item-row');
                        if (row && loanItemsContainer.querySelectorAll('.loan-item-row').length > 1) {
                            row.remove();
                            updateRemoveButtons();
                        }
                    });
                });
            }

            if (searchInput && statusFilter && rows.length > 0) {
                const normalize = (value) => (value || '').toString().trim().toLowerCase();

                const applyFilters = () => {
                    const keyword = normalize(searchInput.value);
                    const status = normalize(statusFilter.value);

                    let visibleCount = 0;
                    let activeFilterCount = 0;

                    if (statusFilter.value) activeFilterCount++;

                    if (filterCountBadge) {
                        filterCountBadge.textContent = activeFilterCount;
                        filterCountBadge.classList.toggle('d-none', activeFilterCount === 0);
                    }

                    if (resetFilterButton) {
                        resetFilterButton.classList.toggle('d-none', activeFilterCount === 0 && !searchInput.value);
                    }

                    rows.forEach((row) => {
                        const matchesKeyword = row.dataset.pegawaiLoanSearch.includes(keyword);
                        const matchesStatus = !status || row.dataset.pegawaiLoanStatus === status;
                        const isVisible = matchesKeyword && matchesStatus;

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
                    resetFilterButton.addEventListener('click', function (e) {
                        e.stopPropagation();
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
