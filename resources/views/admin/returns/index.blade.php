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
                                            'warning' => 'bg-light-warning text-warning',
                                            'danger' => 'bg-light-danger text-danger',
                                            default => 'bg-light-success text-success',
                                        };
                                        $statusBadge = match ($return['status_variant']) {
                                            'danger' => 'bg-light-danger text-danger',
                                            'success' => 'bg-light-success text-success',
                                            default => 'bg-light-warning text-warning',
                                        };
                                        $isPending = in_array($return['status'], ['Menunggu', 'Menunggu Verifikasi']);
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
                                        <td class="text-end text-nowrap">
                                            <div class="d-inline-flex align-items-center gap-1 justify-content-end">
                                                @if ($isPending)
                                                    <form action="{{ route('admin.returns.status', $return['id']) }}" method="POST" class="d-inline-block" data-swal-confirm data-swal-title="Setujui pengembalian?" data-swal-text="Apakah Anda yakin barang ini sudah dikembalikan dan ingin menyetujui pengembalian ini?" data-swal-confirm-text="Ya, setujui">
                                                        @csrf
                                                        @method('PUT')
                                                        <input type="hidden" name="status" value="Terverifikasi">
                                                        <button type="submit" class="btn btn-sm btn-light-success icon icon-left">
                                                            <i class="bi bi-check-circle"></i><span>Terima</span>
                                                        </button>
                                                    </form>

                                                    <form action="{{ route('admin.returns.status', $return['id']) }}" method="POST" class="d-inline-block" data-swal-confirm data-swal-title="Tolak pengembalian?" data-swal-text="Apakah Anda yakin ingin menolak pengajuan pengembalian ini?" data-swal-confirm-text="Ya, tolak">
                                                        @csrf
                                                        @method('PUT')
                                                        <input type="hidden" name="status" value="Ditolak">
                                                        <button type="submit" class="btn btn-sm btn-light-danger icon icon-left">
                                                            <i class="bi bi-x-circle"></i><span>Tolak</span>
                                                        </button>
                                                    </form>
                                                @else
                                                    @if ($return['status'] === 'Terverifikasi')
                                                        <a href="{{ route('admin.returns.letter.show', $return['id']) }}" class="btn btn-sm btn-light-primary icon icon-left">
                                                            <i class="bi bi-file-earmark-pdf"></i><span>Lihat Surat</span>
                                                        </a>
                                                    @else
                                                        <span class="text-danger small font-semibold">Pengembalian Ditolak</span>
                                                    @endif
                                                @endif
                                            </div>
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
