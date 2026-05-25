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
                    <div>
                        <h3 class="mb-0">Daftar Aset</h3>
                    </div>
                    <div class="pegawai-card-toolbar">
                        <div class="input-group pegawai-card-search">
                            <span class="input-group-text">
                                <i class="bi bi-search"></i>
                            </span>
                            <input
                                type="search"
                                id="pegawaiAssetSearchInput"
                                class="form-control"
                                placeholder="Cari nama atau kode aset"
                            >
                        </div>
                        <button
                            class="btn btn-light-secondary pegawai-filter-toggle"
                            type="button"
                            id="pegawaiAssetFilterButton"
                            aria-expanded="false"
                            aria-controls="pegawaiAssetFilterPanel"
                            aria-label="Filter aset"
                            title="Filter aset"
                        >
                            <i class="bi bi-funnel"></i>
                            <span class="visually-hidden">Filter</span>
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    @php
                        $categoryFilters = ['Elektronik', 'Furnitur', 'Kendaraan'];
                        $conditionFilters = ['Baik', 'Rusak Ringan', 'Rusak Berat'];
                        $statusFilters = ['Tersedia', 'Dipinjam', 'Perbaikan', 'Diverifikasi'];
                    @endphp

                    <div id="pegawaiAssetFilterPanel" class="pegawai-filter-panel border rounded p-3 mb-4 d-none">
                        <div class="row g-3 align-items-end">
                            <div class="col-lg-4 col-md-6 col-12">
                                <label for="pegawaiAssetCategoryFilter" class="form-label">Kategori</label>
                                <select id="pegawaiAssetCategoryFilter" class="form-select">
                                    <option value="">Semua Kategori</option>
                                    @foreach ($categoryFilters as $categoryFilter)
                                        <option value="{{ $categoryFilter }}">{{ $categoryFilter }}</option>
                                    @endforeach
                                    <option value="Lainnya">Lainnya</option>
                                </select>
                            </div>
                            <div class="col-lg-4 col-md-6 col-12">
                                <label for="pegawaiAssetConditionFilter" class="form-label">Kondisi</label>
                                <select id="pegawaiAssetConditionFilter" class="form-select">
                                    <option value="">Semua Kondisi</option>
                                    @foreach ($conditionFilters as $conditionFilter)
                                        <option value="{{ $conditionFilter }}">{{ $conditionFilter }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-lg-3 col-md-6 col-12">
                                <label for="pegawaiAssetStatusFilter" class="form-label">Status</label>
                                <select id="pegawaiAssetStatusFilter" class="form-select">
                                    <option value="">Semua Status</option>
                                    @foreach ($statusFilters as $statusFilter)
                                        <option value="{{ $statusFilter }}">{{ $statusFilter }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-lg-1 col-md-6 col-12">
                                <button type="button" id="pegawaiAssetResetFilter" class="btn btn-light-secondary icon w-100" aria-label="Reset filter aset">
                                    <i class="bi bi-arrow-counterclockwise"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover table-lg">
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
                                            'warning' => 'bg-light-warning',
                                            'danger' => 'bg-light-danger',
                                            default => 'bg-light-success',
                                        };
                                        $statusBadge = match ($asset['status_variant']) {
                                            'warning' => 'bg-light-warning',
                                            'danger' => 'bg-light-danger',
                                            'info' => 'bg-light-info',
                                            default => 'bg-light-success',
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
                                        <td><span class="badge {{ $conditionBadge }}">{{ $asset['condition'] }}</span></td>
                                        <td><span class="badge {{ $statusBadge }}">{{ $asset['status'] }}</span></td>
                                        <td>
                                            <div>{{ $asset['price'] }}</div>
                                            <small class="text-muted">Perolehan {{ $asset['acquired_at'] }}</small>
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
            const filterButton = document.getElementById('pegawaiAssetFilterButton');
            const filterPanel = document.getElementById('pegawaiAssetFilterPanel');
            const resetFilterButton = document.getElementById('pegawaiAssetResetFilter');
            const rows = Array.from(document.querySelectorAll('[data-pegawai-asset-row]'));
            const emptyRow = document.getElementById('pegawaiAssetEmptyRow');

            if (filterButton && filterPanel) {
                filterButton.addEventListener('click', function () {
                    const isOpening = filterPanel.classList.contains('d-none');

                    filterPanel.classList.toggle('d-none', !isOpening);
                    filterButton.classList.toggle('btn-primary', isOpening);
                    filterButton.classList.toggle('btn-light-secondary', !isOpening);
                    filterButton.setAttribute('aria-expanded', isOpening ? 'true' : 'false');
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
                resetFilterButton.addEventListener('click', function () {
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
