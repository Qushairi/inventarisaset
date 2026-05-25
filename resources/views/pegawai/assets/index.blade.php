@extends('layouts.app')

@section('title', 'Data Aset Pegawai')

@section('content')
    <div class="page-heading">
        <div class="page-title">
            <div class="row">
                <div class="col-12 col-md-6 order-md-1 order-last">
                    <h3>Data Aset</h3>
                    <p class="text-subtitle text-muted">Lihat daftar aset yang tersedia di sistem inventaris.</p>
                </div>
                <div class="col-12 col-md-6 order-md-2 order-first">
                    <nav aria-label="breadcrumb" class="breadcrumb-header float-start float-lg-end">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="{{ route('pegawai.dashboard') }}">Dashboard</a></li>
                            <li class="breadcrumb-item active" aria-current="page">Data Aset</li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </div>

    <div class="page-content">
        <section class="section">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-3">
                    <h4 class="card-title mb-0">Daftar Aset</h4>
                    <span class="badge bg-light-primary">{{ $assets->total() }} aset</span>
                </div>
                <div class="card-body">
                    @php
                        $categoryFilters = ['Elektronik', 'Furnitur', 'Kendaraan'];
                        $conditionFilters = ['Baik', 'Rusak Ringan', 'Rusak Berat'];
                    @endphp

                    <div class="row g-2 mb-3">
                        <div class="col-12 col-lg-6">
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="bi bi-search"></i>
                                </span>
                                <input
                                    type="text"
                                    id="pegawaiAssetSearchInput"
                                    class="form-control"
                                    placeholder="Cari aset..."
                                >
                            </div>
                        </div>
                        <div class="col-12 col-md-6 col-lg-3">
                            <select id="pegawaiAssetCategoryFilter" class="form-select">
                                <option value="">Semua Kategori</option>
                                @foreach ($categoryFilters as $categoryFilter)
                                    <option value="{{ $categoryFilter }}">{{ $categoryFilter }}</option>
                                @endforeach
                                <option value="Lainnya">Lainnya</option>
                            </select>
                        </div>
                        <div class="col-12 col-md-6 col-lg-3">
                            <select id="pegawaiAssetConditionFilter" class="form-select">
                                <option value="">Semua Kondisi</option>
                                @foreach ($conditionFilters as $conditionFilter)
                                    <option value="{{ $conditionFilter }}">{{ $conditionFilter }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover table-striped mb-0">
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
                                        $categoryFilterValue = in_array($categoryValue, $categoryFilters, true)
                                            ? $categoryValue
                                            : 'Lainnya';
                                    @endphp
                                    <tr
                                        data-pegawai-asset-row
                                        data-pegawai-asset-search="{{ $searchSource }}"
                                        data-pegawai-asset-category="{{ strtolower($categoryFilterValue) }}"
                                        data-pegawai-asset-condition="{{ strtolower($conditionValue) }}"
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
                    @include('admin.partials.table-footer', [
                        'from' => $assets->firstItem() ?? 0,
                        'to' => $assets->lastItem() ?? 0,
                        'total' => $assets->total(),
                        'label' => 'aset',
                    ])
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
            const rows = Array.from(document.querySelectorAll('[data-pegawai-asset-row]'));
            const emptyRow = document.getElementById('pegawaiAssetEmptyRow');

            if (!searchInput || !categoryFilter || !conditionFilter || rows.length === 0) {
                return;
            }

            const normalize = (value) => (value || '').toString().trim().toLowerCase();

            const applyFilters = () => {
                const keyword = normalize(searchInput.value);
                const category = normalize(categoryFilter.value);
                const condition = normalize(conditionFilter.value);
                let visibleCount = 0;

                rows.forEach((row) => {
                    const matchesKeyword = row.dataset.pegawaiAssetSearch.includes(keyword);
                    const matchesCategory = !category || row.dataset.pegawaiAssetCategory === category;
                    const matchesCondition = !condition || row.dataset.pegawaiAssetCondition === condition;
                    const isVisible = matchesKeyword && matchesCategory && matchesCondition;

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
        });
    </script>
@endpush
