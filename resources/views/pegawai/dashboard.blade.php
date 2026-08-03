@extends('layouts.app')

@section('title', 'Dashboard')

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/vendors/apexcharts/apexcharts.css') }}">
@endpush

@section('content')
    <div class="page-heading">
        @include('pegawai.partials.page-heading', [
            'title' => 'Dashboard',
            'breadcrumb' => 'Dashboard',
        ])
    </div>

    <div class="page-content">
        <section class="row g-4 pegawai-dashboard-grid">
            <!-- Enhanced Centered Stat Cards -->
            @foreach ($statCards as $card)
                @php
                    $iconClass = match ($card['variant']) {
                        'success' => 'green',
                        'warning' => 'red',
                        'info' => 'blue',
                        default => 'purple',
                    };
                    $borderVariant = match ($card['variant']) {
                        'success' => 'stat-border-success',
                        'warning' => 'stat-border-warning',
                        'info' => 'stat-border-info',
                        default => 'stat-border-primary',
                    };
                @endphp
                <div class="col-12 col-md-6 col-xl-3">
                    <div class="card pegawai-panel pegawai-stat-card {{ $borderVariant }} h-100">
                        <div class="card-body">
                            <div class="pegawai-stat-layout">
                                <div class="stats-icon {{ $iconClass }}">
                                    <i class="bi bi-{{ $card['icon'] }}"></i>
                                </div>
                                <div class="pegawai-stat-copy">
                                    <div class="pegawai-stat-label">{{ $card['label'] }}</div>
                                    <h5 class="font-extrabold pegawai-stat-value mb-0">{{ $card['value'] }}</h5>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach

            <!-- Grafik Aktivitas Bulanan (Full Width) -->
            <div class="col-12">
                <div class="card pegawai-panel">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h4 class="mb-0"><i class="bi bi-graph-up-arrow text-primary me-2"></i>Aktivitas Bulanan Saya</h4>
                        <span class="badge bg-light-primary text-primary">6 Bulan Terakhir</span>
                    </div>
                    <div class="card-body">
                        <div id="chart-pegawai-activity"></div>
                    </div>
                </div>
            </div>

            <!-- Tabel Peminjaman Terbaru Saya & Pengembalian Terbaru Saya (Berdampingan) -->
            <div class="col-12 col-xl-6">
                <div class="card pegawai-panel pegawai-table-card h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h4 class="mb-0"><i class="bi bi-journal-check text-primary me-2"></i>Peminjaman Terbaru Saya</h4>
                        <a href="{{ route('pegawai.loans.index') }}" class="btn btn-sm btn-light-primary">Lihat Semua</a>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle table-lg mb-0">
                                <thead>
                                    <tr>
                                        <th>Aset</th>
                                        <th>Tanggal</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($recentLoans as $loan)
                                        @php
                                            $loanBadge = match ($loan['status_variant']) {
                                                'danger' => 'pegawai-badge-danger',
                                                'warning' => 'pegawai-badge-warning',
                                                default => 'pegawai-badge-success',
                                            };
                                        @endphp
                                        <tr>
                                            <td>
                                                <div class="fw-semibold text-dark">{{ $loan['asset_name'] }}</div>
                                                <small class="text-muted">{{ $loan['asset_code'] }}</small>
                                            </td>
                                            <td>
                                                <div class="small fw-semibold">{{ $loan['loan_date'] }}</div>
                                                <small class="text-muted">{{ $loan['return_plan'] }}</small>
                                            </td>
                                            <td>
                                                <span class="pegawai-badge {{ $loanBadge }}">
                                                    <span class="pegawai-badge-dot"></span>
                                                    <span>{{ $loan['status'] }}</span>
                                                </span>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="3" class="text-center text-muted py-4">Belum ada riwayat peminjaman.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-xl-6">
                <div class="card pegawai-panel pegawai-table-card h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h4 class="mb-0"><i class="bi bi-arrow-counterclockwise text-success me-2"></i>Pengembalian Terbaru Saya</h4>
                        <a href="{{ route('pegawai.returns.index') }}" class="btn btn-sm btn-light-success">Lihat Semua</a>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle table-lg mb-0">
                                <thead>
                                    <tr>
                                        <th>Aset</th>
                                        <th>Tanggal Kembali</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($recentReturns as $returnItem)
                                        <tr>
                                            <td>
                                                <div class="fw-semibold text-dark">{{ $returnItem['asset_name'] }}</div>
                                                <small class="text-muted">{{ $returnItem['asset_code'] }}</small>
                                            </td>
                                            <td>
                                                <div class="small fw-semibold">{{ $returnItem['returned_at'] }}</div>
                                                <small class="text-muted">Kondisi: {{ $returnItem['condition'] }}</small>
                                            </td>
                                            <td>
                                                <span class="pegawai-badge pegawai-badge-success">
                                                    <span class="pegawai-badge-dot"></span>
                                                    <span>{{ $returnItem['status'] }}</span>
                                                </span>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="3" class="text-center text-muted py-4">Belum ada riwayat pengembalian.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tabel Data Aset Terbaru -->
            <div class="col-12">
                <div class="card pegawai-panel pegawai-table-card">
                    <div class="card-header">
                        <h4>Data Aset Terbaru</h4>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle table-lg mb-0">
                                <thead>
                                    <tr>
                                        <th>Aset</th>
                                        <th>Kategori</th>
                                        <th>Lokasi</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($recentAssets as $asset)
                                        @php
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
                                                    <div class="avatar avatar-lg me-3 {{ $asset['avatar_type'] === 'image' ? '' : 'bg-light-primary' }}">
                                                        @if ($asset['avatar_type'] === 'image')
                                                            <img src="{{ $asset['avatar_value'] }}" alt="{{ $asset['name'] }}">
                                                        @else
                                                            <span class="avatar-content">{{ $asset['avatar_value'] }}</span>
                                                        @endif
                                                    </div>
                                                    <div>
                                                        <h6 class="mb-0">{{ $asset['name'] }}</h6>
                                                        <small class="text-muted">{{ $asset['code'] }}</small>
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
                                                <span class="pegawai-badge {{ $statusBadge }}">
                                                    <span class="pegawai-badge-dot"></span>
                                                    <span>{{ $asset['status'] }}</span>
                                                </span>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4" class="text-center text-muted">Belum ada data aset terbaru.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('assets/vendors/apexcharts/apexcharts.js') }}"></script>
    <script>
        const pegawaiActivityChart = new ApexCharts(document.querySelector('#chart-pegawai-activity'), {
            chart: {
                type: 'area',
                height: 320,
                toolbar: {
                    show: false
                },
                fontFamily: 'inherit'
            },
            dataLabels: {
                enabled: false
            },
            stroke: {
                width: 3,
                curve: 'smooth'
            },
            colors: ['#435ebe', '#57caeb'],
            fill: {
                type: 'gradient',
                gradient: {
                    shadeIntensity: 1,
                    opacityFrom: 0.45,
                    opacityTo: 0.05,
                    stops: [0, 90, 100]
                }
            },
            series: [{
                name: 'Peminjaman',
                data: @json($activityChart['loan_series'])
            }, {
                name: 'Pengembalian',
                data: @json($activityChart['return_series'])
            }],
            xaxis: {
                categories: @json($activityChart['labels']),
                axisBorder: {
                    show: false
                },
                axisTicks: {
                    show: false
                }
            },
            yaxis: {
                min: 0,
                forceNiceScale: true
            },
            grid: {
                borderColor: '#f1f1f1',
                strokeDashArray: 4
            },
            legend: {
                position: 'top',
                horizontalAlign: 'right'
            },
            markers: {
                size: 4,
                colors: ['#435ebe', '#57caeb'],
                strokeColors: '#fff',
                strokeWidth: 2,
                hover: {
                    size: 6
                }
            },
            tooltip: {
                shared: true,
                intersect: false
            }
        });

        pegawaiActivityChart.render();
    </script>
@endpush
