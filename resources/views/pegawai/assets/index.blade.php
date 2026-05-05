@extends('layouts.app')

@section('title', 'Data Aset Pegawai')

@section('content')
    <div class="page-heading">
        @include('admin.partials.page-header', [
            'title' => 'Data Aset',
            'subtitle' => 'Lihat daftar aset yang tersedia di sistem inventaris.',
            'breadcrumb' => 'Data Aset',
            'homeRoute' => 'pegawai.dashboard',
        ])
    </div>

    <div class="page-content">
        <section class="section">
            <div class="card pegawai-panel pegawai-table-card">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-3">
                    <div>
                        <h4 class="card-title mb-1">Daftar Aset</h4>
                        <p class="mb-0 text-muted">Informasi aset, lokasi, kondisi, dan status ketersediaan.</p>
                    </div>
                    <span class="badge bg-light-primary">{{ $assets->total() }} aset</span>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover table-lg mb-0">
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
