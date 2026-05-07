@extends('layouts.app')

@section('title', 'Data Pegawai')

@section('content')
    <div class="page-heading">
        @include('admin.partials.page-header', [
            'title' => 'Data Pegawai',
            'subtitle' => 'Kelola akun pegawai (dibuat oleh admin).',
            'breadcrumb' => 'Pegawai',
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
                        <h4 class="card-title mb-1">Daftar Pegawai</h4>
                        <p class="mb-0 text-muted">Daftar akun pegawai yang dapat mengakses sistem inventaris aset.</p>
                    </div>
                    <a href="{{ route('admin.employees.create') }}" class="btn btn-primary btn-sm icon icon-left">
                        <i class="bi bi-plus-circle"></i><span>Tambah Pegawai</span>
                    </a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover table-lg">
                            <thead>
                                <tr>
                                    <th>Pegawai</th>
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
                                                <a href="{{ route('admin.employees.edit', $employee['id']) }}" class="btn btn-sm btn-light-primary icon icon-left"><i class="bi bi-pencil-square"></i><span>Edit</span></a>
                                                <form action="{{ route('admin.employees.destroy', $employee['id']) }}" method="POST" class="d-inline-block">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-light-danger icon icon-left" onclick="return confirm('Hapus pegawai ini?')">
                                                        <i class="bi bi-trash"></i><span>Hapus</span>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center text-muted">Belum ada data pegawai.</td>
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
                    ])
                </div>
            </div>
        </section>
    </div>
@endsection
