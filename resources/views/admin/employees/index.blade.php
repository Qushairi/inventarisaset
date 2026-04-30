@extends('layouts.app')

@section('title', 'Data Pegawai')

@section('content')
    <div class="page-heading">
        @include('admin.partials.page-header', [
            'title' => 'Data Pegawai',
            'subtitle' => 'Kelola akun pegawai (dibuat oleh admin).',
            'breadcrumb' => 'Pegawai',
        ])

        <section class="section">
            <div class="row">
                <div class="col-12 col-md-4">
                    <div class="card">
                        <div class="card-body py-4-5">
                            <div class="row">
                                <div class="col-8 d-flex flex-column justify-content-center">
                                    <h6 class="text-muted font-semibold">Total Pegawai</h6>
                                    <h3 class="font-extrabold mb-0">{{ count($employees) }}</h3>
                                </div>
                                <div class="col-4">
                                    <div class="stats-icon blue">
                                        <i class="bi bi-people-fill"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <h4 class="card-title mb-1">Daftar Pegawai</h4>
                        <p class="mb-0 text-muted">Daftar akun pegawai yang dapat mengakses sistem inventaris aset.</p>
                    </div>
                    <a href="javascript:void(0)" class="btn btn-primary">
                        <i class="bi bi-plus-circle"></i> Tambah Pegawai
                    </a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-lg">
                            <thead>
                                <tr>
                                    <th>Pegawai</th>
                                    <th>Email</th>
                                    <th>Terdaftar</th>
                                    <th class="text-end">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($employees as $employee)
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="avatar avatar-lg me-3">
                                                    <span class="avatar-content">{{ $employee['initials'] }}</span>
                                                </div>
                                                <div>
                                                    <h6 class="mb-0">{{ $employee['name'] }}</h6>
                                                    <small class="text-muted d-block">ID Akun {{ $employee['account_id'] }}</small>
                                                    <span class="badge bg-light-secondary">{{ $employee['role'] }}</span>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div>{{ $employee['email'] }}</div>
                                            <small class="text-muted">{{ $employee['email_note'] }}</small>
                                        </td>
                                        <td>
                                            <div>{{ $employee['registered_at'] }}</div>
                                            <small class="text-muted">{{ $employee['registered_time'] }}</small>
                                        </td>
                                        <td class="text-end">
                                            <div class="d-flex flex-wrap gap-2 justify-content-end">
                                                <a href="javascript:void(0)" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil-square"></i> Edit</a>
                                                <a href="javascript:void(0)" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i> Hapus</a>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @include('admin.partials.table-footer', [
                        'to' => count($employees),
                        'total' => count($employees),
                        'label' => 'pegawai',
                    ])
                </div>
            </div>
        </section>
    </div>
@endsection
