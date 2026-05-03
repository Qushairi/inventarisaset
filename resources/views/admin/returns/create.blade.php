@extends('layouts.app')

@section('title', 'Tambah Pengembalian')

@section('content')
    <div class="page-heading">
        @include('admin.partials.page-header', [
            'title' => 'Tambah Pengembalian',
            'subtitle' => 'Catat proses pengembalian aset.',
            'breadcrumb' => 'Tambah Pengembalian',
        ])
    </div>

    <div class="page-content">
        <section class="section">
            <div class="row match-height">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-3">
                            <h4 class="card-title mb-0">Form Pengembalian</h4>
                            <a href="{{ route('admin.returns.index') }}" class="btn btn-light-secondary btn-sm icon icon-left">
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

                                <form class="form" action="{{ route('admin.returns.store') }}" method="POST">
                                    @csrf
                                    <div class="row">
                                        <div class="col-md-4 col-12">
                                            <div class="form-group">
                                                <label for="asset_id">Aset</label>
                                                <select id="asset_id" name="asset_id" class="form-select @error('asset_id') is-invalid @enderror">
                                                    <option value="">Pilih aset</option>
                                                    @foreach ($assets as $asset)
                                                        <option value="{{ $asset->id }}" @selected(old('asset_id') == $asset->id)>{{ $asset->name }} ({{ $asset->code }})</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-4 col-12">
                                            <div class="form-group">
                                                <label for="user_id">Pegawai</label>
                                                <select id="user_id" name="user_id" class="form-select @error('user_id') is-invalid @enderror">
                                                    <option value="">Pilih pegawai</option>
                                                    @foreach ($employees as $employee)
                                                        <option value="{{ $employee->id }}" @selected(old('user_id') == $employee->id)>{{ $employee->name }} ({{ $employee->email }})</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-4 col-12">
                                            <div class="form-group">
                                                <label for="loan_id">Peminjaman Terkait</label>
                                                <select id="loan_id" name="loan_id" class="form-select @error('loan_id') is-invalid @enderror">
                                                    <option value="">Opsional</option>
                                                    @foreach ($loans as $loan)
                                                        <option value="{{ $loan->id }}" @selected(old('loan_id') == $loan->id)>{{ $loan->asset?->name }} - {{ $loan->user?->name }} - {{ optional($loan->loan_date)->format('d/m/Y') }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-4 col-12">
                                            <div class="form-group">
                                                <label for="returned_at">Tanggal Pengembalian</label>
                                                <input type="date" id="returned_at" name="returned_at" class="form-control @error('returned_at') is-invalid @enderror" value="{{ old('returned_at') }}">
                                            </div>
                                        </div>
                                        <div class="col-md-4 col-12">
                                            <div class="form-group">
                                                <label for="condition">Kondisi</label>
                                                <select id="condition" name="condition" class="form-select @error('condition') is-invalid @enderror">
                                                    <option value="">Pilih kondisi</option>
                                                    @foreach ($conditions as $condition)
                                                        <option value="{{ $condition }}" @selected(old('condition') === $condition)>{{ $condition }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-4 col-12">
                                            <div class="form-group">
                                                <label for="status">Status</label>
                                                <select id="status" name="status" class="form-select @error('status') is-invalid @enderror">
                                                    <option value="">Pilih status</option>
                                                    @foreach ($statuses as $status)
                                                        <option value="{{ $status }}" @selected(old('status') === $status)>{{ $status }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-6 col-12">
                                            <div class="form-group">
                                                <label for="verified_note">Catatan Verifikasi</label>
                                                <input type="text" id="verified_note" name="verified_note" class="form-control @error('verified_note') is-invalid @enderror" placeholder="Contoh: Diverifikasi admin" value="{{ old('verified_note') }}">
                                            </div>
                                        </div>
                                        <div class="col-md-6 col-12">
                                            <div class="form-group">
                                                <label for="report_number">Nomor Berita Acara</label>
                                                <input type="text" id="report_number" name="report_number" class="form-control @error('report_number') is-invalid @enderror" placeholder="Nomor BA" value="{{ old('report_number') }}">
                                            </div>
                                        </div>
                                        <div class="col-md-6 col-12">
                                            <div class="form-group">
                                                <label for="status_note">Keterangan Status</label>
                                                <textarea id="status_note" name="status_note" class="form-control @error('status_note') is-invalid @enderror" rows="3" placeholder="Keterangan status">{{ old('status_note') }}</textarea>
                                            </div>
                                        </div>
                                        <div class="col-md-6 col-12">
                                            <div class="form-group">
                                                <label for="report_note">Catatan Berita Acara</label>
                                                <textarea id="report_note" name="report_note" class="form-control @error('report_note') is-invalid @enderror" rows="3" placeholder="Catatan berita acara">{{ old('report_note') }}</textarea>
                                            </div>
                                        </div>
                                        <div class="col-12 d-flex justify-content-end">
                                            <a href="{{ route('admin.returns.index') }}" class="btn btn-light-secondary icon icon-left me-1 mb-1"><i class="bi bi-arrow-left"></i><span>Batal</span></a>
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
