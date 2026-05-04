@extends('layouts.app')

@section('title', 'Dashboard')

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/vendors/apexcharts/apexcharts.css') }}">
    <style>
        .dashboard-grid {
            --bs-gutter-x: 1.5rem;
            --bs-gutter-y: 1.5rem;
        }

        .dashboard-panel {
            height: 100%;
            border-radius: 1rem;
            box-shadow: 0 18px 40px rgba(15, 23, 42, 0.06);
        }

        .dashboard-panel .card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            padding: 1.4rem 1.5rem 0.9rem;
        }

        .dashboard-panel .card-header h4 {
            margin-bottom: 0;
        }

        .dashboard-panel .card-header + .card-body {
            padding: 0 1.5rem 1.5rem;
        }

        .dashboard-stat-card .card-body {
            display: flex;
            align-items: center;
            padding: 1.15rem 1.2rem;
        }

        .dashboard-stat-layout {
            display: grid;
            grid-template-columns: 3.25rem minmax(0, 1fr);
            align-items: start;
            gap: 0.9rem;
            width: 100%;
        }

        .dashboard-stat-card .stats-icon {
            float: none;
            width: 3.25rem;
            height: 3.25rem;
            border-radius: 0.95rem;
            flex-shrink: 0;
        }

        .dashboard-stat-card .stats-icon i {
            font-size: 1.3rem;
        }

        .dashboard-stat-copy {
            display: flex;
            flex-direction: column;
            justify-content: center;
            min-width: 0;
        }

        .dashboard-stat-copy .dashboard-stat-label {
            margin-bottom: 0.25rem;
            color: #6c7aa5;
            font-size: 0.95rem;
            font-weight: 600;
            line-height: 1.35;
        }

        .dashboard-stat-copy .dashboard-stat-value {
            margin-bottom: 0.35rem;
            color: #25396f;
            font-size: 1.7rem;
            line-height: 1;
        }

        .dashboard-stat-copy .dashboard-stat-helper {
            display: block;
            color: #7c8db5;
            font-size: 0.88rem;
            line-height: 1.45;
            max-width: none;
        }

        .dashboard-chart-card .card-body {
            padding-bottom: 1.1rem;
        }

        .dashboard-chart-card #chart-activity-overview,
        .dashboard-chart-card #chart-asset-condition {
            min-height: 320px;
        }

        .dashboard-side-stack {
            height: 100%;
        }

        .dashboard-trend-item + .dashboard-trend-item {
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid #eef2ff;
        }

        .dashboard-trend-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            margin-bottom: 0.35rem;
        }

        .dashboard-trend-dot {
            width: 0.75rem;
            height: 0.75rem;
            border-radius: 999px;
            display: inline-flex;
            flex-shrink: 0;
            margin-right: 0.75rem;
        }

        .dashboard-highlight-item {
            display: flex;
            align-items: flex-start;
            gap: 0.9rem;
            padding: 0.95rem 1rem;
            border-radius: 0.95rem;
            background: #f8faff;
            border: 1px solid #edf2ff;
        }

        .dashboard-highlight-badge {
            min-width: 2.2rem;
            height: 2.2rem;
            border-radius: 0.8rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: #ebf3ff;
            color: #435ebe;
            font-weight: 700;
            flex-shrink: 0;
        }

        .dashboard-table-card .card-header {
            padding-bottom: 1rem;
        }

        .dashboard-table-card .card-body {
            padding: 0;
        }

        @media (max-width: 575.98px) {
            .dashboard-panel .card-header,
            .dashboard-stat-card .card-body {
                padding: 1rem;
            }

            .dashboard-panel .card-header + .card-body {
                padding: 0 1.4rem 1.4rem;
            }

            .dashboard-stat-layout {
                gap: 0.85rem;
                grid-template-columns: 3rem minmax(0, 1fr);
            }

            .dashboard-stat-card .stats-icon {
                width: 3rem;
                height: 3rem;
            }

            .dashboard-stat-card .stats-icon i {
                font-size: 1.2rem;
            }

            .dashboard-stat-copy .dashboard-stat-value {
                font-size: 1.45rem;
            }

            .dashboard-stat-copy .dashboard-stat-helper {
                max-width: none;
            }

            .dashboard-table-card .card-body {
                padding: 0;
            }
        }
    </style>
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
        <section class="row g-4 dashboard-grid">
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
                    <div class="card dashboard-panel dashboard-stat-card">
                        <div class="card-body">
                            <div class="dashboard-stat-layout">
                                <div class="stats-icon {{ $iconClass }}">
                                    <i class="bi bi-{{ $card['icon'] }}"></i>
                                </div>
                                <div class="dashboard-stat-copy">
                                    <div class="dashboard-stat-label">{{ $card['label'] }}</div>
                                    <h5 class="font-extrabold dashboard-stat-value">{{ $card['value'] }}</h5>
                                    <small class="dashboard-stat-helper">{{ $card['helper'] }}</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach

            <div class="col-12 col-xl-8">
                <div class="card dashboard-panel dashboard-chart-card">
                    <div class="card-header">
                        <h4>Aktivitas Bulanan</h4>
                    </div>
                    <div class="card-body">
                        <div id="chart-activity-overview"></div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-xl-4">
                <div class="card dashboard-panel dashboard-chart-card">
                    <div class="card-header">
                        <h4>Komposisi Kondisi Aset</h4>
                    </div>
                    <div class="card-body">
                        <div id="chart-asset-condition"></div>
                    </div>
                </div>
            </div>

            <div class="col-12">
                <div class="row g-4 dashboard-side-stack">
                    <div class="col-12 col-xl-6">
                        <div class="card dashboard-panel">
                            <div class="card-header">
                                <h4>Tren Cepat</h4>
                            </div>
                            <div class="card-body">
                                @foreach ($trendCards as $trendCard)
                                    <div class="dashboard-trend-item">
                                        <div class="dashboard-trend-head">
                                            <div class="d-flex align-items-center">
                                                <span class="dashboard-trend-dot" style="background-color: {{ $trendCard['color'] }}"></span>
                                                <h6 class="mb-0">{{ $trendCard['title'] }}</h6>
                                            </div>
                                            <h6 class="mb-0">{{ $trendCard['value'] }}</h6>
                                        </div>
                                        <div id="{{ $trendCard['chart_id'] }}"></div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-xl-6">
                        <div class="card dashboard-panel">
                            <div class="card-header">
                                <h4>Highlight Sistem</h4>
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    @foreach ($highlights as $highlight)
                                        <div class="col-12">
                                            <div class="dashboard-highlight-item">
                                                <div class="dashboard-highlight-badge">{{ $highlight['value'] }}</div>
                                                <div>
                                                    <h6 class="mb-1">{{ $highlight['title'] }}</h6>
                                                    <p class="mb-0 text-sm text-muted">{{ $highlight['note'] }}</p>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-xl-6">
                <div class="card dashboard-panel dashboard-table-card">
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
                <div class="card dashboard-panel dashboard-table-card">
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
