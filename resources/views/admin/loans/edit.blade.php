@extends('layouts.app')

@section('title', 'Edit Peminjaman')

@section('content')
    <div class="page-content">
        <section class="section">
            <div class="card transaction-form-card is-loan">
                <div class="transaction-form-header">
                    <div class="transaction-form-title">
                        <span class="transaction-form-icon">
                            <i class="bi bi-box-arrow-up-right"></i>
                        </span>
                        <div>
                            <h4 class="mb-1">Edit Peminjaman</h4>
                            <p class="mb-0 text-muted">Perbarui aset, pegawai, periode, dan status peminjaman.</p>
                        </div>
                    </div>
                    <a href="{{ route('admin.loans.index') }}" class="btn btn-light-secondary btn-sm icon icon-left">
                        <i class="bi bi-arrow-left"></i><span>Kembali</span>
                    </a>
                </div>

                <div class="card-body transaction-form-body">
                    @if ($errors->any())
                        <div class="alert alert-light-danger color-danger transaction-form-alert">
                            <i class="bi bi-exclamation-circle me-1"></i>{{ $errors->first() }}
                        </div>
                    @endif

                    <form class="transaction-form" action="{{ route('admin.loans.update', $loan) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="transaction-form-section">
                            <div class="transaction-section-heading">
                                <span><i class="bi bi-clipboard-data"></i></span>
                                <div>
                                    <h5>Data Utama</h5>
                                    <small>Aset dan pegawai peminjam</small>
                                </div>
                            </div>
                            <div class="row g-3">
                                <div class="col-md-6 col-12">
                                    <div class="form-group transaction-field">
                                        <label for="asset_id">Aset</label>
                                        <div class="transaction-input-shell">
                                            <span class="transaction-input-icon"><i class="bi bi-archive"></i></span>
                                            <select id="asset_id" name="asset_id" class="form-select @error('asset_id') is-invalid @enderror">
                                                <option value="">Pilih aset</option>
                                                @foreach ($assets as $asset)
                                                    <option value="{{ $asset->id }}" @selected(old('asset_id', $loan->asset_id) == $asset->id)>{{ $asset->name }} ({{ $asset->code }}) - Stok {{ $asset->quantity }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        @error('asset_id')
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-6 col-12">
                                    <div class="form-group transaction-field">
                                        <label for="user_id">Pegawai</label>
                                        <div class="transaction-input-shell">
                                            <span class="transaction-input-icon"><i class="bi bi-person-badge"></i></span>
                                            <select id="user_id" name="user_id" class="form-select @error('user_id') is-invalid @enderror">
                                                <option value="">Pilih pegawai</option>
                                                @foreach ($employees as $employee)
                                                    <option value="{{ $employee->id }}" @selected(old('user_id', $loan->user_id) == $employee->id)>{{ $employee->name }} ({{ $employee->email }})</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        @error('user_id')
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="transaction-form-section">
                            <div class="transaction-section-heading">
                                <span><i class="bi bi-calendar-week"></i></span>
                                <div>
                                    <h5>Periode</h5>
                                    <small>Tanggal peminjaman dan rencana kembali</small>
                                </div>
                            </div>
                            <div class="row g-3">
                                <div class="col-md-3 col-12">
                                    <div class="form-group transaction-field">
                                        <label for="loan_date">Tanggal Pinjam</label>
                                        <div class="transaction-input-shell">
                                            <span class="transaction-input-icon"><i class="bi bi-calendar-event"></i></span>
                                            <input type="date" id="loan_date" name="loan_date" class="form-control @error('loan_date') is-invalid @enderror" value="{{ old('loan_date', optional($loan->loan_date)->format('Y-m-d')) }}">
                                        </div>
                                        @error('loan_date')
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-3 col-12">
                                    <div class="form-group transaction-field">
                                        <label for="planned_return_date">Rencana Kembali</label>
                                        <div class="transaction-input-shell">
                                            <span class="transaction-input-icon"><i class="bi bi-calendar-check"></i></span>
                                            <input type="date" id="planned_return_date" name="planned_return_date" class="form-control @error('planned_return_date') is-invalid @enderror" value="{{ old('planned_return_date', optional($loan->planned_return_date)->format('Y-m-d')) }}">
                                        </div>
                                        @error('planned_return_date')
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-3 col-12">
                                    <div class="form-group transaction-field">
                                        <label for="quantity">Jumlah</label>
                                        <div class="transaction-input-shell">
                                            <span class="transaction-input-icon"><i class="bi bi-123"></i></span>
                                            <input type="number" min="1" step="1" id="quantity" name="quantity" class="form-control @error('quantity') is-invalid @enderror" value="{{ old('quantity', $loan->quantity) }}">
                                        </div>
                                        @error('quantity')
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-3 col-12">
                                    <div class="form-group transaction-field">
                                        <label for="status">Status</label>
                                        <div class="transaction-input-shell">
                                            <span class="transaction-input-icon"><i class="bi bi-shield-check"></i></span>
                                            <select id="status" name="status" class="form-select @error('status') is-invalid @enderror">
                                                <option value="">Pilih status</option>
                                                @foreach ($statuses as $status)
                                                    <option value="{{ $status }}" @selected(old('status', $loan->status) === $status)>{{ $status }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        @error('status')
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="transaction-form-section">
                            <div class="transaction-section-heading">
                                <span><i class="bi bi-chat-left-text"></i></span>
                                <div>
                                    <h5>Keterangan</h5>
                                    <small>Catatan status peminjaman</small>
                                </div>
                            </div>
                            <div class="form-group transaction-field mb-0">
                                <label for="status_note">Keterangan</label>
                                <div class="transaction-input-shell transaction-textarea-shell">
                                    <span class="transaction-input-icon"><i class="bi bi-pencil-square"></i></span>
                                    <textarea id="status_note" name="status_note" class="form-control @error('status_note') is-invalid @enderror" rows="4" placeholder="Keterangan status">{{ old('status_note', $loan->status_note) }}</textarea>
                                </div>
                                @error('status_note')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="transaction-form-actions">
                            <a href="{{ route('admin.loans.index') }}" class="btn btn-light-secondary icon icon-left"><i class="bi bi-arrow-left"></i><span>Batal</span></a>
                            <button type="submit" class="btn btn-primary icon icon-left"><i class="bi bi-check-circle"></i><span>Simpan</span></button>
                        </div>
                    </form>
                </div>
            </div>
        </section>
    </div>
@endsection
