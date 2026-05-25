@extends('layouts.app')

@section('title', 'Edit Aset')

@section('content')
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

                                <form class="form" action="{{ route('admin.assets.update', $asset) }}" method="POST" enctype="multipart/form-data">
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
                                                <label for="serial_number">Nomor Seri</label>
                                                <input type="text" id="serial_number" name="serial_number" class="form-control @error('serial_number') is-invalid @enderror" placeholder="Nomor seri pabrik" value="{{ old('serial_number', $asset->serial_number) }}">
                                            </div>
                                        </div>
                                        <div class="col-md-4 col-12">
                                            <div class="form-group">
                                                <label for="size">Ukuran</label>
                                                <input type="text" id="size" name="size" class="form-control @error('size') is-invalid @enderror" placeholder="Ukuran" value="{{ old('size', $asset->size) }}">
                                            </div>
                                        </div>
                                        <div class="col-md-4 col-12">
                                            <div class="form-group">
                                                <label for="material">Bahan</label>
                                                <input type="text" id="material" name="material" class="form-control @error('material') is-invalid @enderror" placeholder="Bahan" value="{{ old('material', $asset->material) }}">
                                            </div>
                                        </div>
                                        <div class="col-md-4 col-12">
                                            <div class="form-group">
                                                <label for="quantity">Jumlah Aset</label>
                                                <input type="number" min="1" step="1" id="quantity" name="quantity" class="form-control @error('quantity') is-invalid @enderror" placeholder="1" value="{{ old('quantity', $asset->quantity) }}">
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
                                                <label for="acquisition_year">Tahun</label>
                                                <input type="number" min="1900" max="{{ now()->addYear()->year }}" id="acquisition_year" name="acquisition_year" class="form-control @error('acquisition_year') is-invalid @enderror" placeholder="Contoh: 2021" value="{{ old('acquisition_year', $asset->acquisition_year ?: optional($asset->acquired_at)->format('Y')) }}">
                                            </div>
                                        </div>
                                        <div class="col-md-4 col-12">
                                            <div class="form-group">
                                                <label for="image_file">Upload Gambar</label>
                                                <input type="file" id="image_file" name="image_file" class="form-control @error('image_file') is-invalid @enderror" accept=".jpg,.jpeg,.png,.webp">
                                                <small class="text-muted">Kosongkan jika tidak ingin mengganti gambar.</small>
                                            </div>
                                        </div>
                                        @if ($asset->hasImage())
                                            <div class="col-12">
                                                <div class="form-group">
                                                    <label>Gambar Saat Ini</label>
                                                    <div class="d-flex align-items-center gap-3 flex-wrap">
                                                        <img src="{{ $asset->imageUrl() }}" alt="{{ $asset->name }}" class="rounded" style="width: 96px; height: 96px; object-fit: cover;">
                                                        <div class="form-check">
                                                            <input type="checkbox" id="remove_image" name="remove_image" value="1" class="form-check-input">
                                                            <label for="remove_image" class="form-check-label">Hapus gambar saat ini</label>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        @endif
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
