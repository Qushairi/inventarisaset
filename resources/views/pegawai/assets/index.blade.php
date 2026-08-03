@extends('layouts.app')

@section('title', 'Data Aset Pegawai')

@section('content')
    <div class="page-heading">
        @include('pegawai.partials.page-heading', [
            'title' => 'Data Aset',
            'breadcrumb' => 'Data Aset',
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
                    <div class="pegawai-toolbar-wrapper ms-auto">
                        <!-- Sleek Search Input Box -->
                        <div class="pegawai-search-box">
                            <i class="bi bi-search pegawai-search-icon"></i>
                            <input
                                type="search"
                                id="pegawaiAssetSearchInput"
                                class="form-control pegawai-search-input"
                                placeholder="Cari nama atau kode aset..."
                            >
                        </div>

                        @php
                            $categoryFilters = ['Elektronik', 'Furnitur', 'Kendaraan'];
                            $conditionFilters = ['Baik', 'Rusak Ringan', 'Rusak Berat'];
                            $statusFilters = ['Tersedia', 'Dipinjam', 'Perbaikan', 'Diverifikasi'];
                        @endphp

                        <!-- Floating Filter Dropdown Popover -->
                        <div class="dropdown">
                            <button
                                class="btn pegawai-filter-btn d-inline-flex align-items-center gap-2"
                                type="button"
                                id="pegawaiAssetFilterDropdownBtn"
                                data-bs-toggle="dropdown"
                                data-bs-auto-close="outside"
                                aria-expanded="false"
                                title="Filter Aset"
                            >
                                <i class="bi bi-funnel-fill text-primary"></i>
                                <span class="fw-semibold">Filter</span>
                            </button>

                            <div class="dropdown-menu dropdown-menu-end p-3 shadow-lg border-0 pegawai-asset-filter-menu" aria-labelledby="pegawaiAssetFilterDropdownBtn">
                                <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
                                    <h6 class="mb-0 fw-bold text-dark"><i class="bi bi-sliders me-1 text-primary"></i> Filter Aset</h6>
                                    <button type="button" id="pegawaiAssetResetFilter" class="btn btn-sm btn-link text-danger p-0 text-decoration-none d-none">
                                        <i class="bi bi-arrow-counterclockwise me-1"></i>Reset
                                    </button>
                                </div>

                                <div class="row g-2">
                                    <div class="col-6 mb-2">
                                        <label for="pegawaiAssetCategoryFilter" class="form-label text-muted small font-semibold">Kategori</label>
                                        <select id="pegawaiAssetCategoryFilter" class="form-select form-select-sm">
                                            <option value="">Semua Kategori</option>
                                            @foreach ($categoryFilters as $categoryFilter)
                                                <option value="{{ $categoryFilter }}">{{ $categoryFilter }}</option>
                                            @endforeach
                                            <option value="Lainnya">Lainnya</option>
                                        </select>
                                    </div>

                                    <div class="col-6 mb-2">
                                        <label for="pegawaiAssetConditionFilter" class="form-label text-muted small font-semibold">Kondisi</label>
                                        <select id="pegawaiAssetConditionFilter" class="form-select form-select-sm">
                                            <option value="">Semua Kondisi</option>
                                            @foreach ($conditionFilters as $conditionFilter)
                                                <option value="{{ $conditionFilter }}">{{ $conditionFilter }}</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="col-6 mb-2">
                                        <label for="pegawaiAssetStatusFilter" class="form-label text-muted small font-semibold">Status</label>
                                        <select id="pegawaiAssetStatusFilter" class="form-select form-select-sm">
                                            <option value="">Semua Status</option>
                                            @foreach ($statusFilters as $statusFilter)
                                                <option value="{{ $statusFilter }}">{{ $statusFilter }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle table-lg">
                            <thead>
                                <tr>
                                    <th>Aset</th>
                                    <th>Kategori</th>
                                    <th>Lokasi</th>
                                    <th>Kondisi</th>
                                    <th>Status</th>
                                    <th>Perolehan</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($assets as $asset)
                                    @php
                                        $conditionBadge = match ($asset['condition_variant']) {
                                            'warning' => 'pegawai-badge-warning',
                                            'danger' => 'pegawai-badge-danger',
                                            default => 'pegawai-badge-success',
                                        };
                                        $statusBadge = match ($asset['status_variant']) {
                                            'warning' => 'pegawai-badge-warning',
                                            'danger' => 'pegawai-badge-danger',
                                            'info' => 'pegawai-badge-info',
                                            default => 'pegawai-badge-success',
                                        };
                                        $searchSource = strtolower(trim(($asset['name'] ?? '').' '.($asset['code'] ?? '')));
                                        $categoryValue = $asset['category'] ?? '';
                                        $conditionValue = $asset['condition'] ?? '';
                                        $statusValue = $asset['status'] ?? '';
                                        $categoryFilterValue = in_array($categoryValue, $categoryFilters, true)
                                            ? $categoryValue
                                            : 'Lainnya';
                                    @endphp
                                    <tr
                                        data-pegawai-asset-row
                                        data-pegawai-asset-search="{{ $searchSource }}"
                                        data-pegawai-asset-category="{{ strtolower($categoryFilterValue) }}"
                                        data-pegawai-asset-condition="{{ strtolower($conditionValue) }}"
                                        data-pegawai-asset-status="{{ strtolower($statusValue) }}"
                                    >
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="avatar avatar-lg me-3 {{ $asset['avatar_type'] === 'image' ? '' : 'bg-light-primary' }}">
                                                    @if ($asset['avatar_type'] === 'image')
                                                        <img src="{{ $asset['avatar_value'] }}" alt="{{ $asset['name'] }}">
                                                    @else
                                                        <span class="avatar-content">{{ $asset['avatar_value'] }}</span>
                                                    @endif
                                                </div>
                                                <div>
                                                    <h6 class="mb-0">{{ $asset['name'] }}</h6>
                                                    <small class="text-muted d-block">{{ $asset['code'] }}</small>
                                                    <small class="text-muted d-block">Seri: {{ $asset['serial_number'] ?: '-' }}</small>
                                                    <small class="text-muted d-block">Ukuran: {{ $asset['size'] ?: '-' }} | Bahan: {{ $asset['material'] ?: '-' }}</small>
                                                    <small class="text-muted">{{ $asset['note'] }}</small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div>{{ $asset['category'] }}</div>
                                            <small class="text-muted">{{ $asset['category_note'] }}</small>
                                        </td>
                                        <td>
                                            <div>{{ $asset['location'] }}</div>
                                            <small class="text-muted">{{ $asset['location_note'] }}</small>
                                        </td>
                                        <td>
                                            <span class="pegawai-badge {{ $conditionBadge }}">
                                                <span class="pegawai-badge-dot"></span>
                                                <span>{{ $asset['condition'] }}</span>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="pegawai-badge {{ $statusBadge }}">
                                                <span class="pegawai-badge-dot"></span>
                                                <span>{{ $asset['status'] }}</span>
                                            </span>
                                        </td>
                                        <td>
                                            <div>{{ $asset['price'] }}</div>
                                            <small class="text-muted">Tahun {{ $asset['acquisition_year'] ?: '-' }}</small>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center text-muted">Belum ada data aset.</td>
                                    </tr>
                                @endforelse
                                @if ($assets->count() > 0)
                                    <tr id="pegawaiAssetEmptyRow" class="d-none">
                                        <td colspan="6" class="text-center text-muted">Tidak ada data yang sesuai</td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                    @include('pegawai.partials.table-footer', ['paginator' => $assets])
                </div>
            </div>
        </section>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const searchInput = document.getElementById('pegawaiAssetSearchInput');
            const categoryFilter = document.getElementById('pegawaiAssetCategoryFilter');
            const conditionFilter = document.getElementById('pegawaiAssetConditionFilter');
            const statusFilter = document.getElementById('pegawaiAssetStatusFilter');
            const resetFilterButton = document.getElementById('pegawaiAssetResetFilter');
            const filterCountBadge = document.getElementById('pegawaiAssetFilterCountBadge');
            const filterMenu = document.querySelector('.pegawai-asset-filter-menu');
            const rows = Array.from(document.querySelectorAll('[data-pegawai-asset-row]'));
            const emptyRow = document.getElementById('pegawaiAssetEmptyRow');

            if (filterMenu) {
                ['click', 'mousedown', 'pointerdown'].forEach((evtType) => {
                    filterMenu.addEventListener(evtType, function (e) {
                        e.stopPropagation();
                    });
                });
            }

            if (!searchInput || !categoryFilter || !conditionFilter || !statusFilter || rows.length === 0) {
                return;
            }

            const normalize = (value) => (value || '').toString().trim().toLowerCase();

            const applyFilters = () => {
                const keyword = normalize(searchInput.value);
                const category = normalize(categoryFilter.value);
                const condition = normalize(conditionFilter.value);
                const status = normalize(statusFilter.value);

                let visibleCount = 0;
                let activeFilterCount = 0;

                if (categoryFilter.value) activeFilterCount++;
                if (conditionFilter.value) activeFilterCount++;
                if (statusFilter.value) activeFilterCount++;

                if (filterCountBadge) {
                    filterCountBadge.textContent = activeFilterCount;
                    filterCountBadge.classList.toggle('d-none', activeFilterCount === 0);
                }

                if (resetFilterButton) {
                    resetFilterButton.classList.toggle('d-none', activeFilterCount === 0 && !searchInput.value);
                }

                rows.forEach((row) => {
                    const matchesKeyword = row.dataset.pegawaiAssetSearch.includes(keyword);
                    const matchesCategory = !category || row.dataset.pegawaiAssetCategory === category;
                    const matchesCondition = !condition || row.dataset.pegawaiAssetCondition === condition;
                    const matchesStatus = !status || row.dataset.pegawaiAssetStatus === status;

                    const isVisible = matchesKeyword && matchesCategory && matchesCondition && matchesStatus;

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
            categoryFilter.addEventListener('change', applyFilters);
            conditionFilter.addEventListener('change', applyFilters);
            statusFilter.addEventListener('change', applyFilters);

            if (resetFilterButton) {
                resetFilterButton.addEventListener('click', function (e) {
                    e.stopPropagation();
                    searchInput.value = '';
                    categoryFilter.value = '';
                    conditionFilter.value = '';
                    statusFilter.value = '';
                    applyFilters();
                });
            }
        });
    </script>
@endpush
