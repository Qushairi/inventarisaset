@extends('layouts.app')

@section('title', 'Dashboard Pegawai')

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/vendors/apexcharts/apexcharts.css') }}">
@endpush

@section('content')
    <div class="page-heading">
        @include('admin.partials.page-header', [
            'title' => 'Dashboard Pegawai',
            'subtitle' => 'Ringkasan inventaris aset untuk aktivitas pegawai.',
            'breadcrumb' => 'Dashboard',
            'homeRoute' => 'pegawai.dashboard',
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
                        <h4>Aktivitas Bulanan Saya</h4>
                    </div>
                    <div class="card-body">
                        <div id="chart-pegawai-activity"></div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-xl-4">
                <div class="card">
                    <div class="card-header">
                        <h4>Highlight Pegawai</h4>
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

            <div class="col-12 col-xl-4">
                <div class="card">
                    <div class="card-header">
                        <h4>Fitur Pegawai</h4>
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

            <div class="col-12 col-xl-8">
                <div class="card">
                    <div class="card-header">
                        <h4>Peminjaman Terbaru Saya</h4>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover table-lg mb-0">
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
                                                <div>{{ $loan['loan_date'] }}</div>
                                                <small class="text-muted">{{ $loan['return_plan'] }}</small>
                                            </td>
                                            <td>
                                                <span class="badge {{ $loanBadge }}">{{ $loan['status'] }}</span>
                                                <div><small class="text-muted">{{ $loan['status_note'] }}</small></div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="3" class="text-center text-muted">Belum ada riwayat peminjaman.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h4>Data Aset Terbaru</h4>
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
                                        @php
                                            $statusBadge = match ($asset['status_variant']) {
                                                'warning' => 'bg-light-warning',
                                                'danger' => 'bg-light-danger',
                                                'info' => 'bg-light-info',
                                                default => 'bg-light-success',
                                            };
                                        @endphp
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
                                                <span class="badge {{ $statusBadge }}">{{ $asset['status'] }}</span>
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
                type: 'line',
                height: 320,
                toolbar: {
                    show: false
                }
            },
            dataLabels: {
                enabled: false
            },
            stroke: {
                width: 3,
                curve: 'smooth'
            },
            colors: ['#435ebe', '#00b894'],
            series: [{
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
            fill: {
                opacity: 0.15
            }
        });

        pegawaiActivityChart.render();
    </script>
@endpush
