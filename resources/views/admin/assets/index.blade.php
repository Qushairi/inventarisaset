@extends('layouts.app')

@section('title', 'Data Aset')

@section('content')
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
                        <h4 class="card-title mb-1">Daftar Aset</h4>
                        <p class="mb-0 text-muted">Kelola inventaris aset, status penggunaan, dan data penempatan barang.</p>
                    </div>
                    <a href="{{ route('admin.assets.create') }}" class="btn btn-primary btn-sm icon icon-left">
                        <i class="bi bi-plus-circle"></i><span>Tambah Aset</span>
                    </a>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.assets.index') }}" method="GET" class="mb-4">
                        <div class="row g-3 align-items-end">
                            <div class="col-lg-3 col-md-6 col-12">
                                <label for="search" class="form-label">Cari Aset</label>
                                <input type="search" id="search" name="search" class="form-control" placeholder="Nama, kode, atau catatan" value="{{ $filters['search'] ?? '' }}">
                            </div>
                            <div class="col-lg-2 col-md-6 col-12">
                                <label for="category_id" class="form-label">Kategori</label>
                                <select id="category_id" name="category_id" class="form-select">
                                    <option value="">Semua kategori</option>
                                    @foreach ($categories as $category)
                                        <option value="{{ $category->id }}" @selected(($filters['category_id'] ?? '') == $category->id)>{{ $category->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-lg-2 col-md-6 col-12">
                                <label for="location_id" class="form-label">Lokasi</label>
                                <select id="location_id" name="location_id" class="form-select">
                                    <option value="">Semua lokasi</option>
                                    @foreach ($locations as $location)
                                        <option value="{{ $location->id }}" @selected(($filters['location_id'] ?? '') == $location->id)>{{ $location->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-lg-2 col-md-6 col-12">
                                <label for="condition" class="form-label">Kondisi</label>
                                <select id="condition" name="condition" class="form-select">
                                    <option value="">Semua kondisi</option>
                                    @foreach ($conditions as $condition)
                                        <option value="{{ $condition }}" @selected(($filters['condition'] ?? '') === $condition)>{{ $condition }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-lg-2 col-md-6 col-12">
                                <label for="status" class="form-label">Status</label>
                                <select id="status" name="status" class="form-select">
                                    <option value="">Semua status</option>
                                    @foreach ($statuses as $status)
                                        <option value="{{ $status }}" @selected(($filters['status'] ?? '') === $status)>{{ $status }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-lg-1 col-md-6 col-12 d-flex gap-2">
                                <button type="submit" class="btn btn-primary icon">
                                    <i class="bi bi-funnel"></i>
                                </button>
                                @if ($hasActiveFilters)
                                    <a href="{{ route('admin.assets.index') }}" class="btn btn-light-secondary icon">
                                        <i class="bi bi-arrow-counterclockwise"></i>
                                    </a>
                                @endif
                            </div>
                        </div>
                    </form>
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
                                    <th class="text-end">Aksi</th>
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
                                        <td class="text-end">
                                            <div class="d-inline-flex flex-nowrap gap-2">
                                                <a href="{{ route('admin.assets.edit', $asset['code']) }}" class="btn btn-sm btn-light-primary icon icon-left"><i class="bi bi-pencil-square"></i><span>Edit</span></a>
                                                <form action="{{ route('admin.assets.destroy', $asset['code']) }}" method="POST" class="d-inline-block">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-light-danger icon icon-left" onclick="return confirm('Hapus aset ini?')">
                                                        <i class="bi bi-trash"></i><span>Hapus</span>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center text-muted">Belum ada data aset.</td>
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
