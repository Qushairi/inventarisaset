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
                                    <th>Jumlah</th>
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
                                            @if (!empty($loan['items_list']) && count($loan['items_list']) > 1)
                                                <div class="d-flex flex-column gap-1.5">
                                                    @foreach ($loan['items_list'] as $it)
                                                        <div>
                                                            <div class="fw-bold text-dark font-13">{{ $it['name'] }}</div>
                                                            <small class="text-muted font-11 d-block">{{ $it['code'] }}</small>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            @else
                                                <div class="fw-bold text-dark font-13">{{ $loan['asset_name'] }}</div>
                                                <small class="text-muted font-11 d-block">{{ $loan['asset_code'] }}</small>
                                            @endif
                                        </td>
                                        <td>
                                            @if (!empty($loan['items_list']) && count($loan['items_list']) > 1)
                                                <div class="d-flex flex-column gap-1.5">
                                                    @foreach ($loan['items_list'] as $it)
                                                        <div>
                                                            <span class="badge bg-light-primary text-primary font-11 font-semibold">{{ $it['quantity'] }} unit</span>
                                                            <small class="text-muted font-11 d-block">&nbsp;</small>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            @else
                                                <span class="badge bg-light-primary text-primary font-12 font-semibold">{{ $loan['quantity'] }} unit</span>
                                            @endif
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
                                        <td colspan="6" class="text-center text-muted">Belum ada data peminjaman.</td>
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
                            <div class="alert alert-light-danger border-0 shadow-sm rounded-3 p-3 mb-4 d-flex align-items-center gap-3">
                                <div class="alert-icon-box bg-danger text-white rounded-2 d-flex align-items-center justify-content-center flex-shrink-0" style="width: 36px; height: 36px;">
                                    <i class="bi bi-exclamation-triangle-fill font-16"></i>
                                </div>
                                <div>
                                    <h6 class="mb-0 text-danger fw-bold font-13">Pengajuan Belum Lengkap</h6>
                                    <small class="text-muted font-12">{{ $errors->createLoan->first() }}</small>
                                </div>
                            </div>
                        @endif

                        <div class="transaction-form-section">
                            <div class="transaction-section-heading">
                                <span><i class="bi bi-boxes"></i></span>
                                <div>
                                    <h5 class="mb-0">Daftar Barang yang Dipinjam</h5>
                                    <small class="text-muted">Cari aset di atas untuk menambahkan ke daftar peminjaman</small>
                                </div>
                            </div>

                            <!-- Master Asset Search Field at the Top -->
                            <div class="mt-3 mb-3">
                                <label class="form-label font-semibold small mb-1">Cari & Tambah Barang Aset</label>
                                <div class="transaction-input-shell position-relative" id="masterAssetSearchWrapper">
                                    <span class="transaction-input-icon"><i class="bi bi-search"></i></span>
                                    <input
                                        type="text"
                                        id="masterAssetSearchInput"
                                        class="form-control"
                                        placeholder="Ketik nama atau kode aset untuk mencari & menambah..."
                                        autocomplete="off"
                                        @disabled($availableAssets->isEmpty())
                                    >
                                    
                                    <!-- Floating Master Search Results Dropdown -->
                                    <div id="masterAssetDropdown" class="shadow-lg border rounded-3 p-2 bg-white position-absolute w-100 start-0 top-100 mt-1 d-none" style="z-index: 1055; max-height: 250px; overflow-y: auto;">
                                        @foreach ($availableAssets as $asset)
                                            <div class="master-asset-option p-2 rounded-2 border-bottom cursor-pointer text-dark"
                                                 data-id="{{ $asset->id }}"
                                                 data-name="{{ $asset->name }}"
                                                 data-code="{{ $asset->code }}"
                                                 data-location="{{ $asset->location?->name ?? '' }}"
                                                 data-stock="{{ $asset->quantity }}"
                                                 data-search="{{ strtolower($asset->name . ' ' . $asset->code . ' ' . ($asset->location?->name ?? '')) }}">
                                                <div class="fw-bold font-13">{{ $asset->name }}</div>
                                                <div class="d-flex align-items-center gap-2 mt-1">
                                                    <span class="badge bg-light-primary text-primary font-10">{{ $asset->code }}</span>
                                                    @if($asset->location)
                                                        <small class="text-muted font-11"><i class="bi bi-geo-alt me-0.5"></i>{{ $asset->location->name }}</small>
                                                    @endif
                                                    <span class="badge bg-light-success text-success font-10 ms-auto">Stok: {{ $asset->quantity }}</span>
                                                </div>
                                            </div>
                                        @endforeach
                                        <div id="masterAssetNoResult" class="text-muted p-3 text-center small d-none">
                                            <i class="bi bi-exclamation-circle me-1"></i>Barang aset tidak ditemukan
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Selected Items Container (Rendered below) -->
                            @php
                                $oldItems = old('items', []);
                                $validOldItems = is_array($oldItems) ? array_filter($oldItems, fn($i) => !empty($i['asset_id'])) : [];
                                if (empty($validOldItems) && request('asset_id')) {
                                    $validOldItems = [['asset_id' => request('asset_id'), 'quantity' => 1]];
                                }
                            @endphp

                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <label class="form-label font-semibold small text-muted mb-0">Barang Terpilih (<span id="selectedAssetCount">{{ count($validOldItems) }}</span>):</label>
                            </div>

                            <div id="selectedAssetsList" class="d-flex flex-column gap-2">
                                <div id="emptyAssetNotice" class="border rounded-3 p-4 bg-light text-center text-muted small @if(count($validOldItems) > 0) d-none @endif">
                                    <i class="bi bi-inbox fs-4 d-block mb-1 opacity-50"></i>
                                    Belum ada barang yang dipilih. Ketik nama aset pada kotak pencarian di atas untuk menambahkan.
                                </div>

                                @foreach ($validOldItems as $idx => $itemData)
                                    @php
                                        $oldAssetId = $itemData['asset_id'] ?? '';
                                        $oldQty = $itemData['quantity'] ?? 1;
                                        $selectedAsset = $oldAssetId ? $availableAssets->firstWhere('id', $oldAssetId) : null;
                                    @endphp
                                    @if ($selectedAsset)
                                        <div class="selected-asset-card border rounded-3 p-3 bg-white shadow-sm d-flex align-items-center justify-content-between gap-3 position-relative" data-id="{{ $selectedAsset->id }}">
                                            <input type="hidden" name="items[{{ $idx }}][asset_id]" value="{{ $selectedAsset->id }}">
                                            <div class="flex-grow-1">
                                                <div class="fw-bold text-dark font-13">{{ $selectedAsset->name }} <span class="text-muted font-11">({{ $selectedAsset->code }})</span></div>
                                                <small class="text-muted font-11"><i class="bi bi-geo-alt me-0.5"></i>{{ $selectedAsset->location?->name ?? 'Tanpa Lokasi' }}</small>
                                            </div>
                                            <div class="d-flex align-items-center gap-2 flex-shrink-0">
                                                <div class="text-end me-1">
                                                    <small class="form-label d-block font-10 text-muted mb-0">Jumlah</small>
                                                    <small class="badge bg-light-primary text-primary font-10 rounded-pill">Stok: {{ $selectedAsset->quantity }}</small>
                                                </div>
                                                <div class="transaction-input-shell" style="width: 80px;">
                                                    <input type="number" name="items[{{ $idx }}][quantity]" class="form-control form-control-sm text-center selected-asset-qty" min="1" max="{{ $selectedAsset->quantity }}" value="{{ $oldQty }}" required>
                                                </div>
                                                <button type="button" class="btn btn-light-danger btn-sm icon btn-remove-selected-asset" title="Hapus Barang">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </div>
                                        </div>
                                    @endif
                                @endforeach
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

            // Master Asset Search & Cart List Script
            const masterSearchInput = document.getElementById('masterAssetSearchInput');
            const masterDropdown = document.getElementById('masterAssetDropdown');
            const masterOptions = document.querySelectorAll('.master-asset-option');
            const masterNoResult = document.getElementById('masterAssetNoResult');
            const selectedAssetsList = document.getElementById('selectedAssetsList');
            const emptyAssetNotice = document.getElementById('emptyAssetNotice');
            const selectedAssetCount = document.getElementById('selectedAssetCount');
            let itemIndex = selectedAssetsList ? selectedAssetsList.querySelectorAll('.selected-asset-card').length : 0;

            function updateAssetCount() {
                if (!selectedAssetsList) return;
                const cards = selectedAssetsList.querySelectorAll('.selected-asset-card');
                if (selectedAssetCount) selectedAssetCount.textContent = cards.length;
                if (emptyAssetNotice) {
                    emptyAssetNotice.classList.toggle('d-none', cards.length > 0);
                }
            }

            if (masterSearchInput && masterDropdown && selectedAssetsList) {
                const filterMasterOptions = () => {
                    const query = (masterSearchInput.value || '').trim().toLowerCase();
                    let visibleCount = 0;

                    masterOptions.forEach(opt => {
                        const searchText = opt.dataset.search || '';
                        const matches = !query || searchText.includes(query);
                        opt.classList.toggle('d-none', !matches);
                        if (matches) visibleCount++;
                    });

                    if (masterNoResult) {
                        masterNoResult.classList.toggle('d-none', visibleCount > 0);
                    }
                    masterDropdown.classList.remove('d-none');
                };

                masterSearchInput.addEventListener('focus', filterMasterOptions);
                masterSearchInput.addEventListener('input', filterMasterOptions);

                document.addEventListener('click', function (e) {
                    const wrapper = document.getElementById('masterAssetSearchWrapper');
                    if (wrapper && !wrapper.contains(e.target)) {
                        masterDropdown.classList.add('d-none');
                    }
                });

                masterOptions.forEach(opt => {
                    opt.addEventListener('click', function (e) {
                        e.stopPropagation();
                        const id = opt.dataset.id;
                        const name = opt.dataset.name;
                        const code = opt.dataset.code;
                        const location = opt.dataset.location;
                        const stock = parseInt(opt.dataset.stock || '1', 10);

                        // Check if already added
                        const existingCard = selectedAssetsList.querySelector(`.selected-asset-card[data-id="${id}"]`);
                        if (existingCard) {
                            const qtyInput = existingCard.querySelector('.selected-asset-qty');
                            if (qtyInput) {
                                let val = parseInt(qtyInput.value || '1', 10) + 1;
                                if (val <= stock) qtyInput.value = val;
                            }
                            masterSearchInput.value = '';
                            masterDropdown.classList.add('d-none');
                            return;
                        }

                        // Create card element
                        const card = document.createElement('div');
                        card.className = 'selected-asset-card border rounded-3 p-3 bg-white shadow-sm d-flex align-items-center justify-content-between gap-3 position-relative';
                        card.dataset.id = id;

                        card.innerHTML = `
                            <input type="hidden" name="items[${itemIndex}][asset_id]" value="${id}">
                            <div class="flex-grow-1">
                                <div class="fw-bold text-dark font-13">${name} <span class="text-muted font-11">(${code})</span></div>
                                <small class="text-muted font-11"><i class="bi bi-geo-alt me-0.5"></i>${location || 'Tanpa Lokasi'}</small>
                            </div>
                            <div class="d-flex align-items-center gap-2 flex-shrink-0">
                                <div class="text-end me-1">
                                    <small class="form-label d-block font-10 text-muted mb-0">Jumlah</small>
                                    <small class="badge bg-light-primary text-primary font-10 rounded-pill">Stok: ${stock}</small>
                                </div>
                                <div class="transaction-input-shell" style="width: 80px;">
                                    <input type="number" name="items[${itemIndex}][quantity]" class="form-control form-control-sm text-center selected-asset-qty" min="1" max="${stock}" value="1" required>
                                </div>
                                <button type="button" class="btn btn-light-danger btn-sm icon btn-remove-selected-asset" title="Hapus Barang">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        `;

                        const btnRemove = card.querySelector('.btn-remove-selected-asset');
                        btnRemove.addEventListener('click', function () {
                            card.remove();
                            updateAssetCount();
                        });

                        const qtyInput = card.querySelector('.selected-asset-qty');
                        qtyInput.addEventListener('input', function () {
                            let val = parseInt(qtyInput.value || '1', 10);
                            if (val > stock) qtyInput.value = stock;
                            else if (val < 1 || isNaN(val)) qtyInput.value = 1;
                        });

                        selectedAssetsList.appendChild(card);
                        itemIndex++;
                        masterSearchInput.value = '';
                        masterDropdown.classList.add('d-none');
                        updateAssetCount();
                    });
                });

                // Attach remove handlers to existing SSR old cards
                selectedAssetsList.querySelectorAll('.selected-asset-card').forEach(card => {
                    const btnRemove = card.querySelector('.btn-remove-selected-asset');
                    const qtyInput = card.querySelector('.selected-asset-qty');
                    const stock = qtyInput ? parseInt(qtyInput.max || '1', 10) : 1;

                    if (btnRemove) {
                        btnRemove.addEventListener('click', function () {
                            card.remove();
                            updateAssetCount();
                        });
                    }
                    if (qtyInput) {
                        qtyInput.addEventListener('input', function () {
                            let val = parseInt(qtyInput.value || '1', 10);
                            if (val > stock) qtyInput.value = stock;
                            else if (val < 1 || isNaN(val)) qtyInput.value = 1;
                        });
                    }
                });

                updateAssetCount();
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

            @if ($errors->createLoan->any() || request('asset_id'))
                if (modalElement) {
                    new bootstrap.Modal(modalElement).show();
                }
            @endif
        });
    </script>
@endpush
