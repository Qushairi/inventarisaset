@extends('layouts.app')

@section('title', 'Peminjaman Aset')

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
                        <h4 class="card-title mb-1">Daftar Peminjaman</h4>
                        <p class="mb-0 text-muted">Kelola data peminjaman aset dari pegawai.</p>
                    </div>
                    <a href="{{ route('admin.loans.create') }}" class="btn btn-primary btn-sm icon icon-left">
                        <i class="bi bi-plus-circle"></i><span>Tambah Peminjaman</span>
                    </a>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.loans.index') }}" method="GET" class="mb-4">
                        <div class="row g-3 align-items-end">
                            <div class="col-lg-4 col-md-6 col-12">
                                <label for="search" class="form-label">Cari Peminjaman</label>
                                <input type="search" id="search" name="search" class="form-control" placeholder="Aset, kode, pegawai, atau catatan" value="{{ $filters['search'] ?? '' }}">
                            </div>
                            <div class="col-lg-3 col-md-6 col-12">
                                <label for="status" class="form-label">Status</label>
                                <select id="status" name="status" class="form-select">
                                    <option value="">Semua status</option>
                                    @foreach ($statuses as $status)
                                        <option value="{{ $status }}" @selected(($filters['status'] ?? '') === $status)>{{ $status }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-lg-2 col-md-6 col-12 d-flex gap-2">
                                <button type="submit" class="btn btn-primary icon">
                                    <i class="bi bi-funnel"></i>
                                </button>
                                @if ($hasActiveFilters)
                                    <a href="{{ route('admin.loans.index') }}" class="btn btn-light-secondary icon">
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
                                    <th>Pegawai</th>
                                    <th>Tgl Pinjam</th>
                                    <th>Status</th>
                                    <th class="text-end">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($loans as $loan)
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
                                            <div><small class="text-muted">Jumlah: {{ $loan['quantity'] }}</small></div>
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
                                            <div><small class="text-muted">{{ $loan['status_note'] }}</small></div>
                                        </td>
                                        <td class="text-end">
                                            @if ($loan['status'] === 'Menunggu')
                                                <div class="d-inline-flex flex-nowrap gap-2">
                                                    <form action="{{ route('admin.loans.status', $loan['id']) }}" method="POST" class="d-inline-block">
                                                        @csrf
                                                        @method('PUT')
                                                        <input type="hidden" name="status" value="Disetujui">
                                                        <button type="submit" class="btn btn-sm btn-light-success icon icon-left" onclick="return confirm('Terima pengajuan peminjaman ini?')">
                                                            <i class="bi bi-check-circle"></i><span>Terima</span>
                                                        </button>
                                                    </form>
                                                    <form action="{{ route('admin.loans.status', $loan['id']) }}" method="POST" class="d-inline-block">
                                                        @csrf
                                                        @method('PUT')
                                                        <input type="hidden" name="status" value="Ditolak">
                                                        <button type="submit" class="btn btn-sm btn-light-danger icon icon-left" onclick="return confirm('Tolak pengajuan peminjaman ini?')">
                                                            <i class="bi bi-x-circle"></i><span>Tolak</span>
                                                        </button>
                                                    </form>
                                                </div>
                                            @else
                                                <span class="text-muted small">Sudah diproses</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center text-muted">Belum ada data peminjaman.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    @include('admin.partials.table-footer', [
                        'from' => $loans->firstItem() ?? 0,
                        'to' => $loans->lastItem() ?? 0,
                        'total' => $loanTotal,
                        'label' => 'peminjaman',
                        'paginator' => $loans,
                    ])
                </div>
            </div>
        </section>
    </div>
@endsection
