@extends('layouts.app')

@section('title', 'Edit Lokasi')

@section('content')
    <div class="page-heading">
        @include('admin.partials.page-header', [
            'title' => 'Edit Lokasi',
            'subtitle' => 'Perbarui data lokasi penyimpanan aset.',
            'breadcrumb' => 'Edit Lokasi',
        ])
    </div>

    <div class="page-content">
        <section class="section">
            <div class="row match-height">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-3">
                            <h4 class="card-title mb-0">Form Lokasi</h4>
                            <a href="{{ route('admin.locations.index') }}" class="btn btn-light-secondary btn-sm icon icon-left">
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

                                <form class="form" action="{{ route('admin.locations.update', $location) }}" method="POST">
                                    @csrf
                                    @method('PUT')
                                    <div class="row">
                                        <div class="col-md-6 col-12">
                                            <div class="form-group">
                                                <label for="name">Nama Lokasi</label>
                                                <input type="text" id="name" name="name" class="form-control @error('name') is-invalid @enderror" placeholder="Nama lokasi" value="{{ old('name', $location->name) }}">
                                            </div>
                                        </div>
                                        <div class="col-md-6 col-12">
                                            <div class="form-group">
                                                <label for="code">Kode Lokasi</label>
                                                <input type="text" id="code" name="code" class="form-control @error('code') is-invalid @enderror" placeholder="Kode lokasi" value="{{ old('code', $location->code) }}">
                                            </div>
                                        </div>
                                        <div class="col-md-6 col-12">
                                            <div class="form-group">
                                                <label for="address">Alamat</label>
                                                <input type="text" id="address" name="address" class="form-control @error('address') is-invalid @enderror" placeholder="Alamat lokasi" value="{{ old('address', $location->address) }}">
                                            </div>
                                        </div>
                                        <div class="col-md-6 col-12">
                                            <div class="form-group">
                                                <label for="address_note">Catatan Alamat</label>
                                                <input type="text" id="address_note" name="address_note" class="form-control @error('address_note') is-invalid @enderror" placeholder="Catatan alamat" value="{{ old('address_note', $location->address_note) }}">
                                            </div>
                                        </div>
                                        <div class="col-12">
                                            <div class="form-group">
                                                <label for="description">Deskripsi</label>
                                                <textarea id="description" name="description" class="form-control @error('description') is-invalid @enderror" rows="3" placeholder="Deskripsi lokasi">{{ old('description', $location->description) }}</textarea>
                                            </div>
                                        </div>
                                        <div class="col-12">
                                            <div class="form-group">
                                                <label for="note">Catatan</label>
                                                <textarea id="note" name="note" class="form-control @error('note') is-invalid @enderror" rows="3" placeholder="Catatan tambahan">{{ old('note', $location->note) }}</textarea>
                                            </div>
                                        </div>
                                        <div class="col-12 d-flex justify-content-end">
                                            <a href="{{ route('admin.locations.index') }}" class="btn btn-light-secondary icon icon-left me-1 mb-1"><i class="bi bi-arrow-left"></i><span>Batal</span></a>
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
