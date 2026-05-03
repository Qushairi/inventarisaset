@extends('layouts.app')

@section('title', 'Edit Aset')

@section('content')
    <div class="page-heading">
        @include('admin.partials.page-header', [
            'title' => 'Edit Aset',
            'subtitle' => 'Perbarui data inventaris aset.',
            'breadcrumb' => 'Edit Aset',
        ])
    </div>

    <div class="page-content">
        <section class="section">
            <div class="row match-height">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-3">
                            <h4 class="card-title mb-0">Form Aset</h4>
                            <a href="{{ route('admin.assets.index') }}" class="btn btn-light-secondary btn-sm icon icon-left">
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

                                <form class="form" action="{{ route('admin.assets.update', $asset) }}" method="POST">
                                    @csrf
                                    @method('PUT')
                                    <div class="row">
                                        <div class="col-md-6 col-12">
                                            <div class="form-group">
                                                <label for="name">Nama Aset</label>
                                                <input type="text" id="name" name="name" class="form-control @error('name') is-invalid @enderror" placeholder="Nama aset" value="{{ old('name', $asset->name) }}">
                                            </div>
                                        </div>
                                        <div class="col-md-6 col-12">
                                            <div class="form-group">
                                                <label for="code">Kode Aset</label>
                                                <input type="text" id="code" name="code" class="form-control @error('code') is-invalid @enderror" placeholder="Kode aset" value="{{ old('code', $asset->code) }}">
                                            </div>
                                        </div>
                                        <div class="col-md-6 col-12">
                                            <div class="form-group">
                                                <label for="category_id">Kategori</label>
                                                <select id="category_id" name="category_id" class="form-select @error('category_id') is-invalid @enderror">
                                                    <option value="">Pilih kategori</option>
                                                    @foreach ($categories as $category)
                                                        <option value="{{ $category->id }}" @selected(old('category_id', $asset->category_id) == $category->id)>{{ $category->name }} ({{ $category->code }})</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-6 col-12">
                                            <div class="form-group">
                                                <label for="location_id">Lokasi</label>
                                                <select id="location_id" name="location_id" class="form-select @error('location_id') is-invalid @enderror">
                                                    <option value="">Pilih lokasi</option>
                                                    @foreach ($locations as $location)
                                                        <option value="{{ $location->id }}" @selected(old('location_id', $asset->location_id) == $location->id)>{{ $location->name }} ({{ $location->code }})</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-6 col-12">
                                            <div class="form-group">
                                                <label for="condition">Kondisi</label>
                                                <select id="condition" name="condition" class="form-select @error('condition') is-invalid @enderror">
                                                    <option value="">Pilih kondisi</option>
                                                    @foreach ($conditions as $condition)
                                                        <option value="{{ $condition }}" @selected(old('condition', $asset->condition) === $condition)>{{ $condition }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-6 col-12">
                                            <div class="form-group">
                                                <label for="status">Status</label>
                                                <select id="status" name="status" class="form-select @error('status') is-invalid @enderror">
                                                    <option value="">Pilih status</option>
                                                    @foreach ($statuses as $status)
                                                        <option value="{{ $status }}" @selected(old('status', $asset->status) === $status)>{{ $status }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-4 col-12">
                                            <div class="form-group">
                                                <label for="acquisition_price">Nilai Perolehan</label>
                                                <input type="number" step="0.01" id="acquisition_price" name="acquisition_price" class="form-control @error('acquisition_price') is-invalid @enderror" placeholder="0" value="{{ old('acquisition_price', $asset->acquisition_price) }}">
                                            </div>
                                        </div>
                                        <div class="col-md-4 col-12">
                                            <div class="form-group">
                                                <label for="acquired_at">Tanggal Perolehan</label>
                                                <input type="date" id="acquired_at" name="acquired_at" class="form-control @error('acquired_at') is-invalid @enderror" value="{{ old('acquired_at', optional($asset->acquired_at)->format('Y-m-d')) }}">
                                            </div>
                                        </div>
                                        <div class="col-md-4 col-12">
                                            <div class="form-group">
                                                <label for="image_path">Path Gambar</label>
                                                <input type="text" id="image_path" name="image_path" class="form-control @error('image_path') is-invalid @enderror" placeholder="assets/images/..." value="{{ old('image_path', $asset->image_path) }}">
                                            </div>
                                        </div>
                                        <div class="col-12">
                                            <div class="form-group">
                                                <label for="note">Catatan</label>
                                                <textarea id="note" name="note" class="form-control @error('note') is-invalid @enderror" rows="3" placeholder="Catatan aset">{{ old('note', $asset->note) }}</textarea>
                                            </div>
                                        </div>
                                        <div class="col-12 d-flex justify-content-end">
                                            <a href="{{ route('admin.assets.index') }}" class="btn btn-light-secondary icon icon-left me-1 mb-1"><i class="bi bi-arrow-left"></i><span>Batal</span></a>
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
