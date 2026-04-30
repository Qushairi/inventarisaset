@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
    <div class="page-heading">
        @include('admin.partials.page-header', [
            'title' => 'Dashboard Inventaris Aset',
            'subtitle' => 'Ringkasan data inventaris untuk admin.',
            'breadcrumb' => 'Dashboard',
        ])

        <section class="section">
            <div class="row">
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
                            <div class="card-body py-4-5">
                                <div class="row">
                                    <div class="col-8 d-flex flex-column justify-content-center">
                                        <h6 class="text-muted font-semibold">{{ $card['label'] }}</h6>
                                        <h3 class="font-extrabold mb-0">{{ $card['value'] }}</h3>
                                        <small class="text-muted">{{ $card['helper'] }}</small>
                                    </div>
                                    <div class="col-4">
                                        <div class="stats-icon {{ $iconClass }}">
                                            <i class="bi bi-{{ $card['icon'] }}"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach

                <div class="col-12 col-xl-7">
                    <div class="card">
                        <div class="card-header">
                            <h4 class="card-title">Fitur Admin</h4>
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

                <div class="col-12 col-xl-5">
                    <div class="card">
                        <div class="card-header">
                            <h4 class="card-title">Highlight Sistem</h4>
                        </div>
                        <div class="card-body">
                            @foreach ($highlights as $highlight)
                                <div class="d-flex align-items-start mb-3">
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
                            <h4 class="card-title">Aset Terbaru</h4>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-lg mb-0">
                                    <thead>
                                        <tr>
                                            <th>Aset</th>
                                            <th>Kategori</th>
                                            <th>Lokasi</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($recentAssets as $asset)
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
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-xl-6">
                    <div class="card">
                        <div class="card-header">
                            <h4 class="card-title">Peminjaman Terbaru</h4>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-lg mb-0">
                                    <thead>
                                        <tr>
                                            <th>Aset</th>
                                            <th>Pegawai</th>
                                            <th>Tanggal</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($recentLoans as $loan)
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
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
@endsection
