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
                        <button
                            type="button"
                            class="btn btn-primary icon icon-left"
                            data-bs-toggle="modal"
                            data-bs-target="#returnRequestModal"
                            @disabled($returnableLoans->isEmpty())
                        >
                            <i class="bi bi-plus-circle-fill"></i>
                            <span>Ajukan Pengembalian</span>
                        </button>
                    </div>

                    <div class="pegawai-toolbar-wrapper ms-auto">
                        <!-- Sleek Search Input Box -->
                        <div class="pegawai-search-box">
                            <i class="bi bi-search pegawai-search-icon"></i>
                            <input
                                type="search"
                                id="pegawaiReturnSearchInput"
                                class="form-control pegawai-search-input"
                                placeholder="Cari aset atau kode aset..."
                            >
                        </div>

                        @php
                            $returnStatusFilters = ['Sudah Dikembalikan'];
                        @endphp

                        <!-- Floating Filter Dropdown Popover -->
                        <div class="dropdown">
                            <button
                                class="btn pegawai-filter-btn d-inline-flex align-items-center gap-2"
                                type="button"
                                id="pegawaiReturnFilterDropdownBtn"
                                data-bs-toggle="dropdown"
                                data-bs-auto-close="outside"
                                aria-expanded="false"
                                title="Filter Pengembalian"
                            >
                                <i class="bi bi-funnel-fill text-primary"></i>
                                <span class="fw-semibold">Filter</span>
                            </button>

                            <div class="dropdown-menu dropdown-menu-end p-3 shadow-lg border-0 pegawai-asset-filter-menu" aria-labelledby="pegawaiReturnFilterDropdownBtn">
                                <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
                                    <h6 class="mb-0 fw-bold text-dark"><i class="bi bi-sliders me-1 text-primary"></i> Filter Pengembalian</h6>
                                    <button type="button" id="pegawaiReturnResetFilter" class="btn btn-sm btn-link text-danger p-0 text-decoration-none d-none">
                                        <i class="bi bi-arrow-counterclockwise me-1"></i>Reset
                                    </button>
                                </div>

                                <div class="row g-2">
                                    <div class="col-12 mb-2">
                                        <label for="pegawaiReturnConditionFilter" class="form-label text-muted small font-semibold">Kondisi Aset</label>
                                        <select id="pegawaiReturnConditionFilter" class="form-select form-select-sm">
                                            <option value="">Semua Kondisi</option>
                                            @foreach ($conditions as $condition)
                                                <option value="{{ $condition }}">{{ $condition }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    @if ($returnableLoans->isEmpty())
                        <div class="alert alert-light-warning color-warning mb-4">
                            <i class="bi bi-exclamation-triangle me-1"></i>Belum ada peminjaman yang siap diajukan untuk pengembalian.
                        </div>
                    @endif

                    <div class="table-responsive">
                        <table class="table table-hover align-middle table-lg mb-0">
                            <thead>
                                <tr>
                                    <th>Aset</th>
                                    <th>Tanggal Peminjaman</th>
                                    <th>Tanggal Pengembalian</th>
                                    <th>Kondisi</th>
                                    <th>Status</th>
                                    <th>Surat Peminjaman</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($returns as $return)
                                    @php
                                        $conditionBadge = match ($return['condition_variant']) {
                                            'warning' => 'pegawai-badge-warning',
                                            'danger' => 'pegawai-badge-danger',
                                            default => 'pegawai-badge-success',
                                        };
                                        $searchSource = strtolower(trim(($return['asset_name'] ?? '').' '.($return['asset_code'] ?? '')));
                                    @endphp
                                    <tr
                                        data-pegawai-return-row
                                        data-pegawai-return-search="{{ $searchSource }}"
                                        data-pegawai-return-status="sudah dikembalikan"
                                        data-pegawai-return-condition="{{ strtolower($return['condition'] ?? '') }}"
                                    >
                                        <td>
                                            <div>{{ $return['asset_name'] }}</div>
                                            <small class="text-muted">{{ $return['asset_code'] }}</small>
                                        </td>
                                        <td>
                                            <div>{{ $return['loan_date'] ?: '-' }}</div>
                                            <small class="text-muted">Tanggal aset dipinjam.</small>
                                        </td>
                                        <td>
                                            <div>{{ $return['returned_at'] }}</div>
                                            <small class="text-muted">Telah dikembalikan oleh pegawai.</small>
                                        </td>
                                        <td>
                                            <span class="pegawai-badge {{ $conditionBadge }}">
                                                <span class="pegawai-badge-dot"></span>
                                                <span>{{ $return['condition'] }}</span>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="pegawai-badge pegawai-badge-success">
                                                <span class="pegawai-badge-dot"></span>
                                                <span>Sudah Dikembalikan</span>
                                            </span>
                                        </td>
                                        <td>
                                            @if ($return['letter_url'])
                                                <div class="fw-semibold text-secondary small mb-1.5">{{ $return['letter_number'] }}</div>
                                                <div class="d-flex flex-wrap gap-2">
                                                    <a href="{{ $return['letter_url'] }}" class="btn btn-sm btn-light-primary icon icon-left">
                                                        <i class="bi bi-file-earmark-text"></i><span>Lihat Surat</span>
                                                    </a>
                                                    <a href="{{ $return['letter_download_url'] }}" class="btn btn-sm btn-light-secondary icon icon-left">
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
                                        <td colspan="6" class="text-center text-muted">Belum ada data pengembalian.</td>
                                    </tr>
                                @endforelse
                                @if ($returns->count() > 0)
                                    <tr id="pegawaiReturnEmptyRow" class="d-none">
                                        <td colspan="6" class="text-center text-muted">Tidak ada data yang sesuai</td>
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
                <form action="{{ route('pegawai.returns.store') }}" method="POST" data-swal-confirm data-swal-icon="question" data-swal-title="Kirim pengajuan pengembalian?" data-swal-text="Pastikan data peminjaman dan kondisi aset sudah sesuai." data-swal-confirm-text="Ya, kirim pengajuan" data-swal-confirm-color="#198754">
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
                                                    @php
                                                        $itemList = $loan->getItemList();
                                                        $firstAsset = $itemList->first()['asset'] ?? $loan->asset;
                                                        $itemCount = $itemList->count();
                                                        $label = $itemCount > 1
                                                            ? $firstAsset?->name . ' (+' . ($itemCount - 1) . ' barang lainnya, Total: ' . $itemList->sum('quantity') . ' Unit)'
                                                            : $firstAsset?->name . ' (' . $firstAsset?->code . ')';
                                                    @endphp
                                                    <option value="{{ $loan->id }}" @selected(old('loan_id') == $loan->id)>
                                                        {{ $label }} - Pinjam {{ optional($loan->loan_date)->translatedFormat('d F Y') }}
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
            const conditionFilter = document.getElementById('pegawaiReturnConditionFilter');
            const resetFilterButton = document.getElementById('pegawaiReturnResetFilter');
            const filterCountBadge = document.getElementById('pegawaiReturnFilterCountBadge');
            const filterMenu = document.querySelector('.pegawai-asset-filter-menu');
            const rows = Array.from(document.querySelectorAll('[data-pegawai-return-row]'));
            const emptyRow = document.getElementById('pegawaiReturnEmptyRow');

            if (filterMenu) {
                ['click', 'mousedown', 'pointerdown'].forEach((evtType) => {
                    filterMenu.addEventListener(evtType, function (e) {
                        e.stopPropagation();
                    });
                });
            }

            if (searchInput && conditionFilter && rows.length > 0) {
                const normalize = (value) => (value || '').toString().trim().toLowerCase();

                const applyFilters = () => {
                    const keyword = normalize(searchInput.value);
                    const condition = normalize(conditionFilter.value);

                    let visibleCount = 0;
                    let activeFilterCount = 0;

                    if (conditionFilter.value) activeFilterCount++;

                    if (filterCountBadge) {
                        filterCountBadge.textContent = activeFilterCount;
                        filterCountBadge.classList.toggle('d-none', activeFilterCount === 0);
                    }

                    if (resetFilterButton) {
                        resetFilterButton.classList.toggle('d-none', activeFilterCount === 0 && !searchInput.value);
                    }

                    rows.forEach((row) => {
                        const matchesKeyword = row.dataset.pegawaiReturnSearch.includes(keyword);
                        const matchesCondition = !condition || row.dataset.pegawaiReturnCondition === condition;
                        const isVisible = matchesKeyword && matchesCondition;

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
                conditionFilter.addEventListener('change', applyFilters);

                if (resetFilterButton) {
                    resetFilterButton.addEventListener('click', function (e) {
                        e.stopPropagation();
                        searchInput.value = '';
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
