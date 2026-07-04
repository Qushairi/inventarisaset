@extends('layouts.app')

@section('title', 'Pengembalian Aset')

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
                        <h4 class="card-title mb-1">Riwayat Pengembalian</h4>
                        <p class="mb-0 text-muted">Riwayat peminjaman yang sudah dikembalikan beserta detail pengembaliannya.</p>
                    </div>
                    <button type="button" class="btn btn-primary btn-sm icon icon-left" data-bs-toggle="modal" data-bs-target="#adminReturnCreateModal">
                        <i class="bi bi-plus-circle"></i><span>Tambah Pengembalian</span>
                    </button>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.returns.index') }}" method="GET" class="mb-4">
                        <div class="row g-3 align-items-end">
                            <div class="col-lg-3 col-md-6 col-12">
                                <label for="search" class="form-label">Cari Pengembalian</label>
                                <input type="search" id="search" name="search" class="form-control" placeholder="Aset, kode, pegawai, atau BA" value="{{ $filters['search'] ?? '' }}">
                            </div>
                            <div class="col-lg-3 col-md-6 col-12">
                                <label for="condition" class="form-label">Kondisi</label>
                                <select id="condition" name="condition" class="form-select">
                                    <option value="">Semua kondisi</option>
                                    @foreach ($conditions as $condition)
                                        <option value="{{ $condition }}" @selected(($filters['condition'] ?? '') === $condition)>{{ $condition }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-lg-2 col-md-6 col-12 d-flex gap-2">
                                <button type="submit" class="btn btn-primary icon">
                                    <i class="bi bi-funnel"></i>
                                </button>
                                @if ($hasActiveFilters)
                                    <a href="{{ route('admin.returns.index') }}" class="btn btn-light-secondary icon">
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
                                    <th>Riwayat Peminjaman</th>
                                    <th>Pengembalian</th>
                                    <th>Kondisi</th>
                                    <th>Berita Acara</th>
                                    <th class="text-end">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($returns as $return)
                                    @php
                                        $conditionBadge = match ($return['condition_variant']) {
                                            'warning' => 'bg-light-warning',
                                            'danger' => 'bg-light-danger',
                                            default => 'bg-light-success',
                                        };
                                        $statusBadge = $return['status_variant'] === 'success' ? 'bg-light-success' : 'bg-light-info';
                                    @endphp
                                    <tr>
                                        <td>
                                            <div>{{ $return['asset_name'] }}</div>
                                            <small class="text-muted">{{ $return['asset_code'] }}</small>
                                        </td>
                                        <td>
                                            <div>{{ $return['employee_name'] }}</div>
                                            <small class="text-muted">{{ $return['employee_email'] }}</small>
                                        </td>
                                        <td>
                                            <div>Pinjam: {{ $return['loan_date'] ?: '-' }}</div>
                                            <small class="text-muted d-block">Rencana kembali: {{ $return['planned_return_date'] ?: '-' }}</small>
                                            <small class="text-muted d-block">Jumlah: {{ $return['loan_quantity'] }} | Durasi: {{ $return['loan_duration'] }}</small>
                                        </td>
                                        <td>
                                            <div>Kembali: {{ $return['returned_at'] }}</div>
                                            <span class="badge {{ $statusBadge }}">{{ $return['status'] }}</span>
                                            <small class="text-muted d-block">{{ $return['verified_note'] ?: $return['status_note'] }}</small>
                                        </td>
                                        <td>
                                            <span class="badge {{ $conditionBadge }}">{{ $return['condition'] }}</span>
                                        </td>
                                        <td>
                                            <div>{{ $return['report_number'] }}</div>
                                            <small class="text-muted">{{ $return['report_note'] }}</small>
                                        </td>
                                        <td class="text-end">
                                            <a href="{{ route('admin.returns.letter.show', $return['id']) }}" class="btn btn-sm btn-light-primary icon icon-left">
                                                <i class="bi bi-file-earmark-pdf"></i><span>Lihat Surat</span>
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center text-muted">Belum ada riwayat pengembalian.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    @include('admin.partials.table-footer', [
                        'from' => $returns->firstItem() ?? 0,
                        'to' => $returns->lastItem() ?? 0,
                        'total' => $returnTotal,
                        'label' => 'pengembalian',
                        'paginator' => $returns,
                    ])
                </div>
            </div>
        </section>
    </div>
    @include('admin.partials.create-modal', ['resource' => 'return'])
@endsection
