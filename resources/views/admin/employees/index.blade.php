@extends('layouts.app')

@section('title', 'Data Pegawai')

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
                        <h4 class="card-title mb-1">Daftar Pegawai</h4>
                        <p class="mb-0 text-muted">Daftar akun pegawai yang dapat mengakses sistem inventaris aset.</p>
                    </div>
                    <button type="button" class="btn btn-primary btn-sm icon icon-left" data-bs-toggle="modal" data-bs-target="#adminEmployeeCreateModal">
                        <i class="bi bi-plus-circle"></i><span>Tambah Pegawai</span>
                    </button>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.employees.index') }}" method="GET" class="mb-4">
                        <div class="row g-3 align-items-end">
                            <div class="col-lg-4 col-md-6 col-12">
                                <label for="search" class="form-label">Cari Pegawai</label>
                                <input type="search" id="search" name="search" class="form-control" placeholder="Nama, NIP, atau email" value="{{ $filters['search'] ?? '' }}">
                            </div>
                            <div class="col-lg-2 col-md-6 col-12 d-flex gap-2">
                                <button type="submit" class="btn btn-primary icon">
                                    <i class="bi bi-funnel"></i>
                                </button>
                                @if ($hasActiveFilters)
                                    <a href="{{ route('admin.employees.index') }}" class="btn btn-light-secondary icon">
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
                                    <th>Pegawai</th>
                                    <th>NIP</th>
                                    <th>Email</th>
                                    <th>Terdaftar</th>
                                    <th class="text-end">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($employees as $employee)
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="avatar avatar-lg me-3 bg-light-primary">
                                                    <span class="avatar-content">{{ $employee['initials'] }}</span>
                                                </div>
                                                <div>
                                                    <h6 class="mb-0">{{ $employee['name'] }}</h6>
                                                    <small class="text-muted d-block">ID Akun {{ $employee['account_id'] }}</small>
                                                    <span class="badge bg-light-secondary">{{ $employee['role'] }}</span>
                                                </div>
                                            </div>
                                        </td>
                                        <td>{{ $employee['nip'] ?: '-' }}</td>
                                        <td>
                                            <div>{{ $employee['email'] }}</div>
                                            <small class="text-muted">{{ $employee['email_note'] }}</small>
                                        </td>
                                        <td>
                                            <div>{{ $employee['registered_at'] }}</div>
                                            <small class="text-muted">{{ $employee['registered_time'] }}</small>
                                        </td>
                                        <td class="text-end">
                                            <div class="d-inline-flex flex-nowrap gap-2">
                                                <button type="button" class="btn btn-sm btn-light-primary icon icon-left" data-bs-toggle="modal" data-bs-target="#adminEmployeeEditModal-{{ $employee['id'] }}"><i class="bi bi-pencil-square"></i><span>Edit</span></button>
                                                <form action="{{ route('admin.employees.destroy', $employee['id']) }}" method="POST" class="d-inline-block" data-swal-confirm data-swal-title="Hapus pegawai?" data-swal-text="Apakah Anda yakin ingin menghapus akun pegawai ini?" data-swal-confirm-text="Ya, hapus">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-light-danger icon icon-left">
                                                        <i class="bi bi-trash"></i><span>Hapus</span>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center text-muted">Belum ada data pegawai.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    @include('admin.partials.table-footer', [
                        'from' => $employees->firstItem() ?? 0,
                        'to' => $employees->lastItem() ?? 0,
                        'total' => $employees->total(),
                        'label' => 'pegawai',
                        'paginator' => $employees,
                    ])
                </div>
            </div>
        </section>
    </div>
    @include('admin.partials.create-modal', ['resource' => 'employee'])
    @foreach ($employees as $employee)
        @include('admin.partials.edit-modal', ['resource' => 'employee', 'record' => $employee])
    @endforeach
@endsection
