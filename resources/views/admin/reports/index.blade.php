@extends('layouts.app')

@section('title', 'Laporan Inventaris Aset')

@section('content')
    <div class="page-heading">
        @include('admin.partials.page-header', [
            'title' => 'Laporan Inventaris Aset',
            'subtitle' => 'Ringkasan data dan unduh laporan PDF.',
            'breadcrumb' => 'Laporan',
        ])
    </div>

    <div class="page-content">
        <section class="section">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title mb-1">Unduh Laporan</h4>
                    <p class="mb-0 text-muted">Pantau ringkasan data dan unduh laporan inventaris, peminjaman, serta pengembalian.</p>
                </div>
                <div class="card-body">
                    <div class="d-flex flex-wrap gap-2 mb-4">
                        <a href="javascript:void(0)" class="btn btn-outline-primary btn-sm icon icon-left"><i class="bi bi-download"></i><span>Download PDF Inventaris</span></a>
                        <a href="javascript:void(0)" class="btn btn-outline-success btn-sm icon icon-left"><i class="bi bi-download"></i><span>Download PDF Peminjaman</span></a>
                        <a href="javascript:void(0)" class="btn btn-outline-warning btn-sm icon icon-left"><i class="bi bi-download"></i><span>Download PDF Pengembalian</span></a>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-12 col-xl-6">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-3">
                            <div>
                                <h4 class="card-title mb-1">Riwayat Peminjaman</h4>
                                <p class="mb-0 text-muted">Preview data peminjaman terbaru.</p>
                            </div>
                            <span class="badge bg-light-primary">{{ $loanTotal }} Data</span>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover table-lg mb-0">
                                    <thead>
                                        <tr>
                                            <th>Aset</th>
                                            <th>Pegawai</th>
                                            <th>Tanggal</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($loanPreview as $loan)
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
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-xl-6">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-3">
                            <div>
                                <h4 class="card-title mb-1">Riwayat Pengembalian</h4>
                                <p class="mb-0 text-muted">Preview data pengembalian terbaru.</p>
                            </div>
                            <span class="badge bg-light-success">{{ $returnTotal }} Data</span>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover table-lg mb-0">
                                    <thead>
                                        <tr>
                                            <th>Aset</th>
                                            <th>Tanggal</th>
                                            <th>Status</th>
                                            <th>No. BA</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($returnPreview as $return)
                                            <tr>
                                                <td>
                                                    <div>{{ $return['asset_name'] }}</div>
                                                    <small class="text-muted">{{ $return['asset_code'] }}</small>
                                                </td>
                                                <td>
                                                    <div>{{ $return['returned_at'] }}</div>
                                                    <small class="text-muted">{{ $return['status_note'] }}</small>
                                                </td>
                                                <td>
                                                    <span class="badge bg-light-success">{{ $return['status'] }}</span>
                                                </td>
                                                <td>
                                                    <div>{{ $return['report_number'] }}</div>
                                                    <small class="text-muted">{{ $return['report_note'] }}</small>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
@endsection
