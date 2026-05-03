@extends('layouts.app')

@section('title', 'Dashboard')

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/vendors/apexcharts/apexcharts.css') }}">
@endpush

@section('content')
    <div class="page-heading">
        @include('admin.partials.page-header', [
            'title' => 'Dashboard Inventaris Aset',
            'subtitle' => 'Ringkasan data inventaris untuk admin.',
            'breadcrumb' => 'Dashboard',
        ])
    </div>

    <div class="page-content">
        <section class="row">
            @foreach ($statCards as $card)
                @php
                    $iconClass = match ($card['variant']) {
                        'success' => 'green',
                        'warning' => 'red',
                        'info' => 'blue',
                        default => 'purple',
                    };
                @endphp
                <div class="col-12 col-md-6 col-xl-3">
                    <div class="card">
                        <div class="card-body px-3 py-4-5">
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="stats-icon {{ $iconClass }}">
                                        <i class="bi bi-{{ $card['icon'] }}"></i>
                                    </div>
                                </div>
                                <div class="col-md-8">
                                    <h6 class="text-muted font-semibold">{{ $card['label'] }}</h6>
                                    <h6 class="font-extrabold mb-0">{{ $card['value'] }}</h6>
                                    <small class="text-muted">{{ $card['helper'] }}</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach

            <div class="col-12 col-xl-8">
                <div class="card">
                    <div class="card-header">
                        <h4>Aktivitas Bulanan</h4>
                    </div>
                    <div class="card-body">
                        <div id="chart-activity-overview"></div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-xl-4">
                <div class="card">
                    <div class="card-header">
                        <h4>Komposisi Kondisi Aset</h4>
                    </div>
                    <div class="card-body">
                        <div id="chart-asset-condition"></div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-xl-4">
                <div class="card">
                    <div class="card-header">
                        <h4>Tren Cepat</h4>
                    </div>
                    <div class="card-body">
                        @foreach ($trendCards as $trendCard)
                            <div class="row {{ $loop->last ? '' : 'mb-3' }}">
                                <div class="col-6">
                                    <div class="d-flex align-items-center">
                                        <svg class="bi" width="32" height="32" style="width: 10px; color: {{ $trendCard['color'] }}">
                                            <use xlink:href="{{ asset('assets/vendors/bootstrap-icons/bootstrap-icons.svg#circle-fill') }}" />
                                        </svg>
                                        <h6 class="mb-0 ms-3">{{ $trendCard['title'] }}</h6>
                                    </div>
                                </div>
                                <div class="col-6 text-end">
                                    <h6 class="mb-0">{{ $trendCard['value'] }}</h6>
                                </div>
                                <div class="col-12">
                                    <div id="{{ $trendCard['chart_id'] }}"></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="col-12 col-xl-8">
                <div class="card">
                    <div class="card-header">
                        <h4>Fitur Admin</h4>
                    </div>
                    <div class="card-body">
                        <div class="list-group">
                            @foreach ($quickLinks as $link)
                                <a href="{{ route($link['route']) }}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-start">
                                    <div class="me-3">
                                        <div class="fw-bold">
                                            <i class="bi bi-{{ $link['icon'] }} me-2 text-primary"></i>{{ $link['title'] }}
                                        </div>
                                        <small class="text-muted">{{ $link['description'] }}</small>
                                    </div>
                                    <i class="bi bi-arrow-right text-muted"></i>
                                </a>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-xl-4">
                <div class="card">
                    <div class="card-header">
                        <h4>Highlight Sistem</h4>
                    </div>
                    <div class="card-body">
                        @foreach ($highlights as $highlight)
                            <div class="d-flex align-items-start {{ $loop->last ? '' : 'mb-3' }}">
                                <div class="badge bg-light-primary me-3">{{ $highlight['value'] }}</div>
                                <div>
                                    <h6 class="mb-1">{{ $highlight['title'] }}</h6>
                                    <p class="mb-0 text-sm text-muted">{{ $highlight['note'] }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="col-12 col-xl-6">
                <div class="card">
                    <div class="card-header">
                        <h4>Aset Terbaru</h4>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover table-lg mb-0">
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
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="avatar avatar-lg me-3">
                                                        @if ($asset['avatar_type'] === 'image')
                                                            <img src="{{ asset($asset['avatar_value']) }}" alt="{{ $asset['name'] }}">
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
                                                <span class="badge {{ $asset['status_variant'] === 'success' ? 'bg-light-success' : 'bg-light-warning' }}">{{ $asset['status'] }}</span>
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

            <div class="col-12 col-xl-6">
                <div class="card">
                    <div class="card-header">
                        <h4>Peminjaman Terbaru</h4>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover table-lg mb-0">
                                <thead>
                                    <tr>
                                        <th>Aset</th>
                                        <th>Pegawai</th>
                                        <th>Tanggal</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($recentLoans as $loan)
                                        @php
                                            $loanBadge = match ($loan['status_variant']) {
                                                'danger' => 'bg-light-danger',
                                                'warning' => 'bg-light-warning',
                                                default => 'bg-light-success',
                                            };
                                        @endphp
                                        <tr>
                                            <td>
                                                <div>{{ $loan['asset_name'] }}</div>
                                                <small class="text-muted">{{ $loan['asset_code'] }}</small>
                                            </td>
                                            <td>
                                                <div>{{ $loan['employee_name'] }}</div>
                                                <small class="text-muted">{{ $loan['employee_email'] }}</small>
                                            </td>
                                            <td>
                                                <div>{{ $loan['loan_date'] }}</div>
                                                <small class="text-muted">{{ $loan['return_plan'] }}</small>
                                            </td>
                                            <td>
                                                <span class="badge {{ $loanBadge }}">{{ $loan['status'] }}</span>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4" class="text-center text-muted">Belum ada data peminjaman terbaru.</td>
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
        const activityChart = new ApexCharts(document.querySelector('#chart-activity-overview'), {
            chart: {
                type: 'bar',
                height: 320,
                toolbar: {
                    show: false
                }
            },
            dataLabels: {
                enabled: false
            },
            stroke: {
                show: false
            },
            colors: ['#435ebe', '#55c6e8', '#00b894'],
            series: [{
                name: 'Aset Masuk',
                data: @json($activityChart['asset_series'])
            }, {
                name: 'Peminjaman',
                data: @json($activityChart['loan_series'])
            }, {
                name: 'Pengembalian',
                data: @json($activityChart['return_series'])
            }],
            xaxis: {
                categories: @json($activityChart['labels'])
            },
            legend: {
                position: 'top'
            },
            plotOptions: {
                bar: {
                    borderRadius: 4,
                    columnWidth: '45%'
                }
            }
        });

        const assetConditionChart = new ApexCharts(document.querySelector('#chart-asset-condition'), {
            chart: {
                type: 'donut',
                height: 320
            },
            series: @json($assetConditionChart['series']),
            labels: @json($assetConditionChart['labels']),
            colors: ['#5ddab4', '#fdac41', '#ff7976'],
            legend: {
                position: 'bottom'
            },
            plotOptions: {
                pie: {
                    donut: {
                        size: '38%'
                    }
                }
            }
        });

        const trendCards = @json($trendCards);

        trendCards.forEach(function (trend) {
            new ApexCharts(document.querySelector('#' + trend.chart_id), {
                series: [{
                    name: trend.title,
                    data: trend.series
                }],
                chart: {
                    height: 80,
                    type: 'area',
                    toolbar: {
                        show: false
                    },
                    sparkline: {
                        enabled: true
                    }
                },
                colors: [trend.color],
                stroke: {
                    width: 2
                },
                fill: {
                    opacity: 0.25
                },
                dataLabels: {
                    enabled: false
                },
                tooltip: {
                    x: {
                        show: false
                    }
                }
            }).render();
        });

        activityChart.render();
        assetConditionChart.render();
    </script>
@endpush
