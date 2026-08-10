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


            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-3">
                    <form method="GET" action="{{ route('pegawai.assets.index') }}" id="pegawaiAssetFilterForm" class="pegawai-toolbar-wrapper ms-auto d-flex align-items-center gap-2">
                        <!-- Sleek Search Input Box -->
                        <div class="pegawai-search-box">
                            <i class="bi bi-search pegawai-search-icon"></i>
                            <input
                                type="search"
                                name="search"
                                id="pegawaiAssetSearchInput"
                                class="form-control pegawai-search-input"
                                placeholder="Cari nama, kode, seri aset..."
                                value="{{ $selectedSearch }}"
                            >
                        </div>

                        <!-- Floating Filter Dropdown Popover -->
                        <div class="dropdown">
                            <button
                                class="btn pegawai-filter-btn d-inline-flex align-items-center gap-2"
                                type="button"
                                id="pegawaiAssetFilterDropdownBtn"
                                data-bs-toggle="dropdown"
                                data-bs-auto-close="outside"
                                aria-expanded="false"
                                title="Filter Data Aset"
                            >
                                <i class="bi bi-funnel-fill text-primary"></i>
                                <span class="fw-semibold">Filter</span>
                            </button>

                            <div class="dropdown-menu dropdown-menu-end p-4 shadow-lg border-0 pegawai-asset-filter-menu" style="min-width: 320px;" aria-labelledby="pegawaiAssetFilterDropdownBtn">
                                <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
                                    <h6 class="mb-0 fw-bold text-dark"><i class="bi bi-sliders me-1.5 text-primary"></i> Filter Data Aset</h6>
                                    @if ($selectedCategory || $selectedLocation || $selectedCondition || $selectedStatus || $selectedSearch)
                                        <a href="{{ route('pegawai.assets.index') }}" class="btn btn-sm btn-light-danger px-2 py-1 font-11 rounded-2 text-decoration-none">
                                            <i class="bi bi-arrow-counterclockwise me-1"></i>Reset
                                        </a>
                                    @endif
                                </div>

                                <div class="row g-3">
                                    <div class="col-12 col-sm-6">
                                        <label for="pegawaiAssetCategoryFilter" class="form-label text-muted small font-semibold mb-1"><i class="bi bi-tags me-1 text-primary"></i>Kategori</label>
                                        <select id="pegawaiAssetCategoryFilter" name="category" class="form-select form-select-sm" onchange="this.form.submit()">
                                            <option value="">Semua Kategori</option>
                                            @foreach ($categoryOptions as $catOpt)
                                                <option value="{{ $catOpt }}" @selected($selectedCategory == $catOpt)>{{ $catOpt }}</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="col-12 col-sm-6">
                                        <label for="pegawaiAssetLocationFilter" class="form-label text-muted small font-semibold mb-1"><i class="bi bi-geo-alt me-1 text-primary"></i>Ruangan / Lokasi</label>
                                        <select id="pegawaiAssetLocationFilter" name="location" class="form-select form-select-sm" onchange="this.form.submit()">
                                            <option value="">Semua Ruangan</option>
                                            @foreach ($locationOptions as $locOpt)
                                                <option value="{{ $locOpt }}" @selected($selectedLocation == $locOpt)>{{ $locOpt }}</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="col-12 col-sm-6">
                                        <label for="pegawaiAssetConditionFilter" class="form-label text-muted small font-semibold mb-1"><i class="bi bi-heart-pulse me-1 text-primary"></i>Kondisi Aset</label>
                                        <select id="pegawaiAssetConditionFilter" name="condition" class="form-select form-select-sm" onchange="this.form.submit()">
                                            <option value="">Semua Kondisi</option>
                                            @foreach ($conditionOptions as $condOpt)
                                                <option value="{{ $condOpt }}" @selected($selectedCondition == $condOpt)>{{ $condOpt }}</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="col-12 col-sm-6">
                                        <label for="pegawaiAssetStatusFilter" class="form-label text-muted small font-semibold mb-1"><i class="bi bi-bookmark-check me-1 text-primary"></i>Status Aset</label>
                                        <select id="pegawaiAssetStatusFilter" name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                                            <option value="">Semua Status</option>
                                            @foreach ($statusOptions as $stOpt)
                                                <option value="{{ $stOpt }}" @selected($selectedStatus == $stOpt)>{{ $stOpt }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>

                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle table-lg mb-0">
                            <thead>
                                <tr>
                                    <th>Aset</th>
                                    <th>Spesifikasi</th>
                                    <th>Kategori</th>
                                    <th>Lokasi</th>
                                    <th>Stok Tersedia</th>
                                    <th>Kondisi</th>
                                    <th>Status</th>
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
                                    @endphp
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="avatar avatar-lg me-3 flex-shrink-0 {{ $asset['avatar_type'] === 'image' ? '' : 'bg-light-primary' }}">
                                                    @if ($asset['avatar_type'] === 'image')
                                                        <img src="{{ $asset['avatar_value'] }}" alt="{{ $asset['name'] }}">
                                                    @else
                                                        <span class="avatar-content">{{ $asset['avatar_value'] }}</span>
                                                    @endif
                                                </div>
                                                <div>
                                                    <h6 class="mb-0 text-dark fw-bold font-13">{{ $asset['name'] }}</h6>
                                                    <small class="text-muted d-block font-11">Kode: {{ $asset['code'] }}</small>
                                                    @if ($asset['serial_number'])
                                                        <small class="text-secondary d-block font-10">Seri: {{ $asset['serial_number'] }}</small>
                                                    @endif
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            @if ($asset['material'] || $asset['size'] || $asset['note'])
                                                <div>
                                                    @if ($asset['material'])
                                                        <small class="text-dark font-11 d-block">Bahan: {{ $asset['material'] }}</small>
                                                    @endif
                                                    @if ($asset['size'])
                                                        <small class="text-dark font-11 d-block">Ukuran: {{ $asset['size'] }}</small>
                                                    @endif
                                                    @if ($asset['note'] && !$asset['material'] && !$asset['size'])
                                                        <small class="text-muted font-11 d-block">{{ $asset['note'] }}</small>
                                                    @endif
                                                </div>
                                            @else
                                                <small class="text-muted font-11">-</small>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="fw-semibold text-dark font-12">{{ $asset['category'] ?: '-' }}</span>
                                            <small class="text-muted d-block font-11">{{ $asset['category_note'] }}</small>
                                        </td>
                                        <td>
                                            <span class="font-12">{{ $asset['location'] ?: '-' }}</span>
                                            <small class="text-muted d-block font-11">{{ $asset['location_note'] }}</small>
                                        </td>
                                        <td>
                                            <span class="badge bg-light-primary text-primary font-12 font-semibold">
                                                <i class="bi bi-box-seam me-1"></i>{{ $asset['quantity'] }} Unit
                                            </span>
                                        </td>
                                        <td>
                                            <span class="pegawai-badge {{ $conditionBadge }}">{{ $asset['condition'] ?: '-' }}</span>
                                        </td>
                                        <td>
                                            <span class="pegawai-badge {{ $statusBadge }}">{{ $asset['status'] ?: '-' }}</span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center text-muted py-4">Tidak ada data yang sesuai</td>
                                    </tr>
                                @endforelse
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
            const filterMenu = document.querySelector('.pegawai-asset-filter-menu');
            if (filterMenu) {
                ['click', 'mousedown', 'pointerdown'].forEach((evtType) => {
                    filterMenu.addEventListener(evtType, function (e) {
                        e.stopPropagation();
                    });
                });
            }
        });
    </script>
@endpush
