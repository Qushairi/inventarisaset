@extends('layouts.app')

@section('title', 'Tambah Pegawai')

@section('content')
    <div class="page-heading">
        @include('admin.partials.page-header', [
            'title' => 'Tambah Pegawai',
            'subtitle' => 'Buat akun pegawai baru untuk mengakses sistem.',
            'breadcrumb' => 'Tambah Pegawai',
        ])
    </div>

    <div class="page-content">
        <section class="section">
            <div class="row match-height">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-3">
                            <h4 class="card-title mb-0">Form Pegawai</h4>
                            <a href="{{ route('admin.employees.index') }}" class="btn btn-light-secondary btn-sm icon icon-left">
                                <i class="bi bi-arrow-left"></i><span>Kembali</span>
                            </a>
                        </div>
                        <div class="card-content">
                            <div class="card-body">
                                @if ($errors->any())
                                    <div class="alert alert-light-danger color-danger">
                                        <i class="bi bi-exclamation-circle me-1"></i>{{ $errors->first() }}
                                    </div>
                                @endif

                                <form class="form" action="{{ route('admin.employees.store') }}" method="POST">
                                    @csrf
                                    <div class="row">
                                        <div class="col-md-6 col-12">
                                            <div class="form-group">
                                                <label for="name">Nama Pegawai</label>
                                                <input type="text" id="name" name="name" class="form-control @error('name') is-invalid @enderror" placeholder="Nama pegawai" value="{{ old('name') }}">
                                            </div>
                                        </div>
                                        <div class="col-md-6 col-12">
                                            <div class="form-group">
                                                <label for="email">Email</label>
                                                <input type="email" id="email" name="email" class="form-control @error('email') is-invalid @enderror" placeholder="pegawai@example.com" value="{{ old('email') }}">
                                            </div>
                                        </div>
                                        <div class="col-md-6 col-12">
                                            <div class="form-group">
                                                <label for="password">Password</label>
                                                <input type="password" id="password" name="password" class="form-control @error('password') is-invalid @enderror" placeholder="Minimal 8 karakter">
                                            </div>
                                        </div>
                                        <div class="col-md-6 col-12">
                                            <div class="form-group">
                                                <label for="password_confirmation">Konfirmasi Password</label>
                                                <input type="password" id="password_confirmation" name="password_confirmation" class="form-control" placeholder="Ulangi password">
                                            </div>
                                        </div>
                                        <div class="col-12 d-flex justify-content-end">
                                            <a href="{{ route('admin.employees.index') }}" class="btn btn-light-secondary icon icon-left me-1 mb-1"><i class="bi bi-arrow-left"></i><span>Batal</span></a>
                                            <button type="submit" class="btn btn-primary icon icon-left me-1 mb-1"><i class="bi bi-check-circle"></i><span>Simpan</span></button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
@endsection
