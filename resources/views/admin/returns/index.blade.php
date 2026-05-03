@extends('layouts.app')

@section('title', 'Pengembalian Aset')

@section('content')
    <div class="page-heading">
        @include('admin.partials.page-header', [
            'title' => 'Pengembalian Aset',
            'subtitle' => 'Kelola proses pengembalian dan verifikasi.',
            'breadcrumb' => 'Pengembalian',
        ])
    </div>

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
                        <h4 class="card-title mb-1">Daftar Pengembalian</h4>
                        <p class="mb-0 text-muted">Kelola data pengembalian dan verifikasi aset.</p>
                    </div>
                    <a href="{{ route('admin.returns.create') }}" class="btn btn-primary btn-sm icon icon-left">
                        <i class="bi bi-plus-circle"></i><span>Tambah Pengembalian</span>
                    </a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover table-lg">
                            <thead>
                                <tr>
                                    <th>Aset</th>
                                    <th>Tanggal Kembali</th>
                                    <th>Kondisi</th>
                                    <th>Status</th>
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
                                            <div>{{ $return['returned_at'] }}</div>
                                            <small class="text-muted">{{ $return['verified_note'] }}</small>
                                        </td>
                                        <td>
                                            <span class="badge {{ $conditionBadge }}">{{ $return['condition'] }}</span>
                                        </td>
                                        <td>
                                            <span class="badge {{ $statusBadge }}">{{ $return['status'] }}</span>
                                            <div><small class="text-muted">{{ $return['status_note'] }}</small></div>
                                        </td>
                                        <td>
                                            <div>{{ $return['report_number'] }}</div>
                                            <small class="text-muted">{{ $return['report_note'] }}</small>
                                        </td>
                                        <td class="text-end">
                                            <div class="d-inline-flex flex-nowrap gap-2">
                                                <a href="{{ route('admin.returns.edit', $return['id']) }}" class="btn btn-sm btn-light-primary icon icon-left">
                                                    <i class="bi bi-pencil-square"></i><span>Edit</span>
                                                </a>
                                                <form action="{{ route('admin.returns.destroy', $return['id']) }}" method="POST" class="d-inline-block">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-light-danger icon icon-left" onclick="return confirm('Hapus data pengembalian ini?')">
                                                        <i class="bi bi-trash"></i><span>Hapus</span>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center text-muted">Belum ada data pengembalian.</td>
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
                    ])
                </div>
            </div>
        </section>
    </div>
@endsection
