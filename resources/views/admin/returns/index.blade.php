@extends('layouts.app')

@section('title', 'Pengembalian Aset')

@section('content')
    <div class="page-heading">
        @include('admin.partials.page-header', [
            'title' => 'Pengembalian Aset',
            'subtitle' => 'Kelola proses pengembalian dan verifikasi.',
            'breadcrumb' => 'Pengembalian',
        ])

        <section class="section">
            <div class="row">
                <div class="col-12 col-md-4">
                    <div class="card">
                        <div class="card-body py-4-5">
                            <div class="row">
                                <div class="col-8 d-flex flex-column justify-content-center">
                                    <h6 class="text-muted font-semibold">Total Pengembalian</h6>
                                    <h3 class="font-extrabold mb-0">{{ $returnTotal }}</h3>
                                </div>
                                <div class="col-4">
                                    <div class="stats-icon blue">
                                        <i class="bi bi-clipboard-check-fill"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h4 class="card-title mb-1">Daftar Pengembalian</h4>
                    <p class="mb-0 text-muted">Pantau dan verifikasi proses pengembalian aset dari pegawai.</p>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-lg">
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
                                @foreach ($returns as $return)
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
                                            <a href="javascript:void(0)" class="btn btn-sm btn-outline-primary">
                                                <i class="bi bi-download"></i> BA PDF
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @include('admin.partials.table-footer', [
                        'to' => count($returns),
                        'total' => $returnTotal,
                        'label' => 'pengembalian',
                    ])
                </div>
            </div>
        </section>
    </div>
@endsection
