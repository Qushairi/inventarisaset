@extends('layouts.app')

@section('title', 'Peminjaman Aset')

@section('content')
    <div class="page-heading">
        @include('admin.partials.page-header', [
            'title' => 'Peminjaman Aset',
            'subtitle' => 'Kelola pengajuan dan persetujuan peminjaman.',
            'breadcrumb' => 'Peminjaman',
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
                        <h4 class="card-title mb-1">Daftar Peminjaman</h4>
                        <p class="mb-0 text-muted">Kelola data peminjaman aset dari pegawai.</p>
                    </div>
                    <a href="{{ route('admin.loans.create') }}" class="btn btn-primary btn-sm icon icon-left">
                        <i class="bi bi-plus-circle"></i><span>Tambah Peminjaman</span>
                    </a>
                </div>
                <div class="card-body">
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
                                            <div class="d-inline-flex flex-nowrap gap-2">
                                                <a href="{{ route('admin.loans.edit', $loan['id']) }}" class="btn btn-sm btn-light-primary icon icon-left"><i class="bi bi-pencil-square"></i><span>Edit</span></a>
                                                <form action="{{ route('admin.loans.destroy', $loan['id']) }}" method="POST" class="d-inline-block">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-light-danger icon icon-left" onclick="return confirm('Hapus data peminjaman ini?')">
                                                        <i class="bi bi-trash"></i><span>Hapus</span>
                                                    </button>
                                                </form>
                                            </div>
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
                    ])
                </div>
            </div>
        </section>
    </div>
@endsection
