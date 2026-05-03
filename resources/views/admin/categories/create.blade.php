@extends('layouts.app')

@section('title', 'Tambah Kategori')

@section('content')
    <div class="page-heading">
        @include('admin.partials.page-header', [
            'title' => 'Tambah Kategori',
            'subtitle' => 'Lengkapi informasi kategori aset baru.',
            'breadcrumb' => 'Tambah Kategori',
        ])
    </div>

    <div class="page-content">
        <section class="section">
            <div class="row match-height">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-3">
                            <h4 class="card-title mb-0">Tambah Kategori</h4>
                            <a href="{{ route('admin.categories.index') }}" class="btn btn-light-secondary btn-sm icon icon-left">
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

                                <form class="form" action="{{ route('admin.categories.store') }}" method="POST">
                                    @csrf

                                    <div class="row">
                                        <div class="col-md-4 col-12">
                                            <div class="form-group">
                                                <label for="name">Nama Kategori</label>
                                                <input
                                                    type="text"
                                                    id="name"
                                                    name="name"
                                                    class="form-control @error('name') is-invalid @enderror"
                                                    placeholder="Nama kategori"
                                                    value="{{ old('name') }}"
                                                >
                                            </div>
                                        </div>
                                        <div class="col-md-4 col-12">
                                            <div class="form-group">
                                                <label for="code">Kode Kategori</label>
                                                <input
                                                    type="text"
                                                    id="code"
                                                    name="code"
                                                    class="form-control @error('code') is-invalid @enderror"
                                                    placeholder="Kode kategori"
                                                    value="{{ old('code') }}"
                                                >
                                            </div>
                                        </div>
                                        <div class="col-md-4 col-12">
                                            <div class="form-group">
                                                <label for="description">Deskripsi</label>
                                                <input
                                                    type="text"
                                                    id="description"
                                                    name="description"
                                                    class="form-control @error('description') is-invalid @enderror"
                                                    placeholder="Deskripsi"
                                                    value="{{ old('description') }}"
                                                >
                                            </div>
                                        </div>
                                        <div class="col-12">
                                            <div class="form-group">
                                                <label for="note">Catatan</label>
                                                <textarea
                                                    id="note"
                                                    name="note"
                                                    class="form-control @error('note') is-invalid @enderror"
                                                    rows="3"
                                                    placeholder="Catatan tambahan kategori"
                                                >{{ old('note') }}</textarea>
                                            </div>
                                        </div>
                                        <div class="col-12 d-flex justify-content-end">
                                            <a href="{{ route('admin.categories.index') }}" class="btn btn-light-secondary icon icon-left me-1 mb-1"><i class="bi bi-arrow-left"></i><span>Batal</span></a>
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
