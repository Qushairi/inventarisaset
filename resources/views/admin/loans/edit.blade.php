@extends('layouts.app')

@section('title', 'Edit Peminjaman')

@section('content')
    <div class="page-heading">
        @include('admin.partials.page-header', [
            'title' => 'Edit Peminjaman',
            'subtitle' => 'Perbarui data transaksi peminjaman aset.',
            'breadcrumb' => 'Edit Peminjaman',
        ])
    </div>

    <div class="page-content">
        <section class="section">
            <div class="row match-height">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-3">
                            <h4 class="card-title mb-0">Form Peminjaman</h4>
                            <a href="{{ route('admin.loans.index') }}" class="btn btn-light-secondary btn-sm icon icon-left">
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

                                <form class="form" action="{{ route('admin.loans.update', $loan) }}" method="POST">
                                    @csrf
                                    @method('PUT')
                                    <div class="row">
                                        <div class="col-md-6 col-12">
                                            <div class="form-group">
                                                <label for="asset_id">Aset</label>
                                                <select id="asset_id" name="asset_id" class="form-select @error('asset_id') is-invalid @enderror">
                                                    <option value="">Pilih aset</option>
                                                    @foreach ($assets as $asset)
                                                        <option value="{{ $asset->id }}" @selected(old('asset_id', $loan->asset_id) == $asset->id)>{{ $asset->name }} ({{ $asset->code }})</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-6 col-12">
                                            <div class="form-group">
                                                <label for="user_id">Pegawai</label>
                                                <select id="user_id" name="user_id" class="form-select @error('user_id') is-invalid @enderror">
                                                    <option value="">Pilih pegawai</option>
                                                    @foreach ($employees as $employee)
                                                        <option value="{{ $employee->id }}" @selected(old('user_id', $loan->user_id) == $employee->id)>{{ $employee->name }} ({{ $employee->email }})</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-4 col-12">
                                            <div class="form-group">
                                                <label for="loan_date">Tanggal Pinjam</label>
                                                <input type="date" id="loan_date" name="loan_date" class="form-control @error('loan_date') is-invalid @enderror" value="{{ old('loan_date', optional($loan->loan_date)->format('Y-m-d')) }}">
                                            </div>
                                        </div>
                                        <div class="col-md-4 col-12">
                                            <div class="form-group">
                                                <label for="planned_return_date">Rencana Kembali</label>
                                                <input type="date" id="planned_return_date" name="planned_return_date" class="form-control @error('planned_return_date') is-invalid @enderror" value="{{ old('planned_return_date', optional($loan->planned_return_date)->format('Y-m-d')) }}">
                                            </div>
                                        </div>
                                        <div class="col-md-4 col-12">
                                            <div class="form-group">
                                                <label for="status">Status</label>
                                                <select id="status" name="status" class="form-select @error('status') is-invalid @enderror">
                                                    <option value="">Pilih status</option>
                                                    @foreach ($statuses as $status)
                                                        <option value="{{ $status }}" @selected(old('status', $loan->status) === $status)>{{ $status }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-12">
                                            <div class="form-group">
                                                <label for="status_note">Keterangan</label>
                                                <textarea id="status_note" name="status_note" class="form-control @error('status_note') is-invalid @enderror" rows="3" placeholder="Keterangan status">{{ old('status_note', $loan->status_note) }}</textarea>
                                            </div>
                                        </div>
                                        <div class="col-12 d-flex justify-content-end">
                                            <a href="{{ route('admin.loans.index') }}" class="btn btn-light-secondary icon icon-left me-1 mb-1"><i class="bi bi-arrow-left"></i><span>Batal</span></a>
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
