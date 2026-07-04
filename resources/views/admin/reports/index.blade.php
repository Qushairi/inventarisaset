@extends('layouts.app')

@section('title', 'Laporan Inventaris Aset')

@section('content')
    <div class="page-content">
        <section class="section">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title mb-1">Unduh Laporan</h4>
                    <p class="mb-0 text-muted">Pilih jenis laporan dan gunakan filter sebelum mengunduh PDF.</p>
                </div>
                <div class="card-body">
                    @if ($errors->any())
                        <div class="alert alert-light-danger color-danger">
                            <i class="bi bi-exclamation-circle me-1"></i>{{ $errors->first() }}
                        </div>
                    @endif

                    <ul class="nav nav-tabs mb-4" id="reportFilterTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="inventory-filter-tab" data-bs-toggle="tab" data-bs-target="#inventory-filter" type="button" role="tab">Inventaris</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="loan-filter-tab" data-bs-toggle="tab" data-bs-target="#loan-filter" type="button" role="tab">Peminjaman</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="return-filter-tab" data-bs-toggle="tab" data-bs-target="#return-filter" type="button" role="tab">Pengembalian</button>
                        </li>
                    </ul>

                    <div class="tab-content">
                        <div class="tab-pane fade show active" id="inventory-filter" role="tabpanel">
                            <form action="{{ route('admin.reports.download', 'inventaris') }}" method="GET">
                                <div class="row g-3 align-items-end">
                                    <div class="col-lg-3 col-md-6">
                                        <label for="report_category_id" class="form-label">Kategori</label>
                                        <select id="report_category_id" name="category_id" class="form-select">
                                            <option value="">Semua kategori</option>
                                            @foreach ($categories as $category)
                                                <option value="{{ $category->id }}">{{ $category->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-lg-3 col-md-6">
                                        <label for="report_location_id" class="form-label">Lokasi</label>
                                        <select id="report_location_id" name="location_id" class="form-select">
                                            <option value="">Semua lokasi</option>
                                            @foreach ($locations as $location)
                                                <option value="{{ $location->id }}">{{ $location->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-lg-2 col-md-4">
                                        <label for="report_asset_condition" class="form-label">Kondisi</label>
                                        <select id="report_asset_condition" name="condition" class="form-select">
                                            <option value="">Semua kondisi</option>
                                            @foreach ($assetConditions as $condition)
                                                <option value="{{ $condition }}">{{ $condition }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-lg-2 col-md-4">
                                        <label for="report_asset_status" class="form-label">Status</label>
                                        <select id="report_asset_status" name="status" class="form-select">
                                            <option value="">Semua status</option>
                                            @foreach ($assetStatuses as $status)
                                                <option value="{{ $status }}">{{ $status }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-lg-2 col-md-4">
                                        <label for="report_asset_year" class="form-label">Tahun Pembuatan</label>
                                        <input type="number" id="report_asset_year" name="year" min="1900" max="{{ now()->addYear()->year }}" class="form-control" placeholder="{{ now()->year }}">
                                    </div>
                                    <div class="col-12 d-flex justify-content-end">
                                        <button type="submit" class="btn btn-primary icon icon-left">
                                            <i class="bi bi-download"></i><span>Unduh PDF Inventaris</span>
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>

                        <div class="tab-pane fade" id="loan-filter" role="tabpanel">
                            <form action="{{ route('admin.reports.download', 'peminjaman') }}" method="GET">
                                <div class="row g-3 align-items-end">
                                    <div class="col-lg-4 col-md-6">
                                        <label for="report_loan_employee" class="form-label">Pegawai</label>
                                        <select id="report_loan_employee" name="user_id" class="form-select">
                                            <option value="">Semua pegawai</option>
                                            @foreach ($employees as $employee)
                                                <option value="{{ $employee->id }}">{{ $employee->name }} ({{ $employee->email }})</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-lg-3 col-md-6">
                                        <label for="report_loan_status" class="form-label">Status</label>
                                        <select id="report_loan_status" name="status" class="form-select">
                                            <option value="">Semua status</option>
                                            @foreach ($loanStatuses as $status)
                                                <option value="{{ $status }}">{{ $status }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-lg-2 col-md-6">
                                        <label for="report_loan_from" class="form-label">Dari Tanggal</label>
                                        <input type="date" id="report_loan_from" name="date_from" class="form-control">
                                    </div>
                                    <div class="col-lg-2 col-md-6">
                                        <label for="report_loan_to" class="form-label">Sampai Tanggal</label>
                                        <input type="date" id="report_loan_to" name="date_to" class="form-control">
                                    </div>
                                    <div class="col-12 d-flex justify-content-end">
                                        <button type="submit" class="btn btn-success icon icon-left">
                                            <i class="bi bi-download"></i><span>Unduh PDF Peminjaman</span>
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>

                        <div class="tab-pane fade" id="return-filter" role="tabpanel">
                            <form action="{{ route('admin.reports.download', 'pengembalian') }}" method="GET">
                                <div class="row g-3 align-items-end">
                                    <div class="col-lg-4 col-md-6">
                                        <label for="report_return_employee" class="form-label">Pegawai</label>
                                        <select id="report_return_employee" name="user_id" class="form-select">
                                            <option value="">Semua pegawai</option>
                                            @foreach ($employees as $employee)
                                                <option value="{{ $employee->id }}">{{ $employee->name }} ({{ $employee->email }})</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-lg-3 col-md-6">
                                        <label for="report_return_condition" class="form-label">Kondisi</label>
                                        <select id="report_return_condition" name="condition" class="form-select">
                                            <option value="">Semua kondisi</option>
                                            @foreach ($assetConditions as $condition)
                                                <option value="{{ $condition }}">{{ $condition }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-lg-2 col-md-6">
                                        <label for="report_return_from" class="form-label">Dari Tanggal</label>
                                        <input type="date" id="report_return_from" name="date_from" class="form-control">
                                    </div>
                                    <div class="col-lg-2 col-md-6">
                                        <label for="report_return_to" class="form-label">Sampai Tanggal</label>
                                        <input type="date" id="report_return_to" name="date_to" class="form-control">
                                    </div>
                                    <div class="col-12 d-flex justify-content-end">
                                        <button type="submit" class="btn btn-warning icon icon-left">
                                            <i class="bi bi-download"></i><span>Unduh PDF Pengembalian</span>
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
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
