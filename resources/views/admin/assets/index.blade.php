@extends('layouts.app')

@section('title', 'Data Aset')

@section('content')
    <div class="page-heading">
        @include('admin.partials.page-header', [
            'title' => 'Data Aset',
            'subtitle' => 'Kelola data inventaris aset.',
            'breadcrumb' => 'Aset',
        ])

        <section class="section">
            <div class="row">
                <div class="col-12 col-md-4">
                    <div class="card">
                        <div class="card-body py-4-5">
                            <div class="row">
                                <div class="col-8 d-flex flex-column justify-content-center">
                                    <h6 class="text-muted font-semibold">Total Aset</h6>
                                    <h3 class="font-extrabold mb-0">{{ count($assets) }}</h3>
                                </div>
                                <div class="col-4">
                                    <div class="stats-icon green">
                                        <i class="bi bi-box-seam"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <h4 class="card-title mb-1">Daftar Aset</h4>
                        <p class="mb-0 text-muted">Kelola inventaris aset, status penggunaan, dan data penempatan barang.</p>
                    </div>
                    <a href="javascript:void(0)" class="btn btn-primary">
                        <i class="bi bi-plus-circle"></i> Tambah Aset
                    </a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-lg">
                            <thead>
                                <tr>
                                    <th>Aset</th>
                                    <th>Kategori</th>
                                    <th>Lokasi</th>
                                    <th>Kondisi</th>
                                    <th>Status</th>
                                    <th>Perolehan</th>
                                    <th class="text-end">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($assets as $asset)
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
                                        <td class="text-end">
                                            <div class="d-flex flex-wrap gap-2 justify-content-end">
                                                <a href="javascript:void(0)" class="btn btn-sm btn-outline-info"><i class="bi bi-eye"></i> Detail</a>
                                                <a href="javascript:void(0)" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil-square"></i> Edit</a>
                                                <a href="javascript:void(0)" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i> Hapus</a>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @include('admin.partials.table-footer', [
                        'to' => count($assets),
                        'total' => count($assets),
                        'label' => 'aset',
                    ])
                </div>
            </div>
        </section>
    </div>
@endsection
