@extends('layouts.app')

@section('title', 'Peminjaman Aset')

@section('content')
    <div class="page-heading">
        @include('admin.partials.page-header', [
            'title' => 'Peminjaman Aset',
            'subtitle' => 'Kelola pengajuan dan persetujuan peminjaman.',
            'breadcrumb' => 'Peminjaman',
        ])

        <section class="section">
            <div class="row">
                <div class="col-12 col-md-4">
                    <div class="card">
                        <div class="card-body py-4-5">
                            <div class="row">
                                <div class="col-8 d-flex flex-column justify-content-center">
                                    <h6 class="text-muted font-semibold">Total Peminjaman</h6>
                                    <h3 class="font-extrabold mb-0">{{ $loanTotal }}</h3>
                                </div>
                                <div class="col-4">
                                    <div class="stats-icon green">
                                        <i class="bi bi-journal-text"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h4 class="card-title mb-1">Daftar Peminjaman</h4>
                    <p class="mb-0 text-muted">Pantau, setujui, atau tolak permintaan peminjaman aset dari pegawai.</p>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-lg">
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
                                @foreach ($loans as $loan)
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
                                            <div class="d-flex flex-wrap gap-2 justify-content-end">
                                                <a href="javascript:void(0)" class="btn btn-sm btn-outline-success"><i class="bi bi-check-circle"></i> Approve</a>
                                                <a href="javascript:void(0)" class="btn btn-sm btn-outline-danger"><i class="bi bi-x-circle"></i> Reject</a>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @include('admin.partials.table-footer', [
                        'to' => count($loans),
                        'total' => $loanTotal,
                        'label' => 'peminjaman',
                    ])
                </div>
            </div>
        </section>
    </div>
@endsection
