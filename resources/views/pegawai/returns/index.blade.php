@extends('layouts.app')

@section('title', 'Pengembalian Pegawai')

@section('content')
    <div class="page-heading">
        @include('admin.partials.page-header', [
            'title' => 'Pengembalian Aset',
            'subtitle' => 'Pantau data pengembalian dan status verifikasi aset.',
            'breadcrumb' => 'Pengembalian',
            'homeRoute' => 'pegawai.dashboard',
        ])
    </div>

    <div class="page-content">
        <section class="section">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title mb-1">Riwayat Pengembalian</h4>
                    <p class="mb-0 text-muted">Daftar pengembalian aset yang tercatat pada akun pegawai.</p>
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
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center text-muted">Belum ada data pengembalian.</td>
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
