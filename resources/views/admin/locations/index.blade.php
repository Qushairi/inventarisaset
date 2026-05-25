@extends('layouts.app')

@section('title', 'Data Lokasi Aset')

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
                        <h4 class="card-title mb-1">Daftar Lokasi</h4>
                        <p class="mb-0 text-muted">Atur lokasi penempatan inventaris agar pelacakan aset lebih mudah dilakukan.</p>
                    </div>
                    <a href="{{ route('admin.locations.create') }}" class="btn btn-primary btn-sm icon icon-left">
                        <i class="bi bi-plus-circle"></i><span>Tambah Lokasi</span>
                    </a>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.locations.index') }}" method="GET" class="mb-4">
                        <div class="row g-3 align-items-end">
                            <div class="col-lg-4 col-md-6 col-12">
                                <label for="search" class="form-label">Cari Lokasi</label>
                                <input type="search" id="search" name="search" class="form-control" placeholder="Nama, kode, alamat, atau keterangan" value="{{ $filters['search'] ?? '' }}">
                            </div>
                            <div class="col-lg-2 col-md-6 col-12 d-flex gap-2">
                                <button type="submit" class="btn btn-primary icon">
                                    <i class="bi bi-funnel"></i>
                                </button>
                                @if ($hasActiveFilters)
                                    <a href="{{ route('admin.locations.index') }}" class="btn btn-light-secondary icon">
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
                                    <th>Lokasi</th>
                                    <th>Alamat</th>
                                    <th>Keterangan</th>
                                    <th class="text-end">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($locations as $location)
                                    <tr>
                                        <td>
                                            <h6 class="mb-0">{{ $location['name'] }}</h6>
                                            <small class="text-muted">{{ $location['code'] }}</small>
                                        </td>
                                        <td>
                                            <div>{{ $location['address'] }}</div>
                                            <small class="text-muted">{{ $location['address_note'] }}</small>
                                        </td>
                                        <td>
                                            <div>{{ $location['description'] }}</div>
                                            <small class="text-muted">{{ $location['note'] }}</small>
                                        </td>
                                        <td class="text-end">
                                            <div class="d-inline-flex flex-nowrap gap-2">
                                                <a href="{{ route('admin.locations.edit', $location['code']) }}" class="btn btn-sm btn-light-primary icon icon-left">
                                                    <i class="bi bi-pencil-square"></i><span>Edit</span>
                                                </a>
                                                <form action="{{ route('admin.locations.destroy', $location['code']) }}" method="POST" class="d-inline-block">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-light-danger icon icon-left" onclick="return confirm('Hapus lokasi ini?')">
                                                        <i class="bi bi-trash"></i><span>Hapus</span>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center text-muted">Belum ada data lokasi.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    @include('admin.partials.table-footer', [
                        'from' => $locations->firstItem() ?? 0,
                        'to' => $locations->lastItem() ?? 0,
                        'total' => $locations->total(),
                        'label' => 'lokasi',
                    ])
                </div>
            </div>
        </section>
    </div>
@endsection
