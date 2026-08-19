@extends('layouts.app')

@section('title', 'Edit Pengembalian')

@section('content')
    <div class="page-content">
        <section class="section">
            <div class="card transaction-form-card is-return">
                <div class="transaction-form-header">
                    <div class="transaction-form-title">
                        <span class="transaction-form-icon">
                            <i class="bi bi-arrow-return-left"></i>
                        </span>
                        <div>
                            <h4 class="mb-1">Edit Pengembalian</h4>
                            <p class="mb-0 text-muted">Perbarui pengembalian aset, kondisi, dan berita acara.</p>
                        </div>
                    </div>
                    <a href="{{ route('admin.returns.index') }}" class="btn btn-light-secondary btn-sm icon icon-left">
                        <i class="bi bi-arrow-left"></i><span>Kembali</span>
                    </a>
                </div>

                <div class="card-body transaction-form-body">
                    @if ($errors->any())
                        <div class="alert alert-light-danger color-danger transaction-form-alert">
                            <i class="bi bi-exclamation-circle me-1"></i>{{ $errors->first() }}
                        </div>
                    @endif

                    <form class="transaction-form" action="{{ route('admin.returns.update', $returnRecord) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="transaction-form-section">
                            <div class="transaction-section-heading">
                                <span><i class="bi bi-clipboard-data"></i></span>
                                <div>
                                    <h5>Data Pengembalian</h5>
                                    <small>Aset, pegawai, dan peminjaman terkait</small>
                                </div>
                            </div>
                            <div class="row g-3">
                                <div class="col-md-4 col-12">
                                    <div class="form-group transaction-field">
                                        <label for="asset_id">Aset</label>
                                        <div class="transaction-input-shell">
                                            <span class="transaction-input-icon"><i class="bi bi-archive"></i></span>
                                            <select id="asset_id" name="asset_id" class="form-select @error('asset_id') is-invalid @enderror">
                                                <option value="">Pilih aset</option>
                                                @foreach ($assets as $asset)
                                                    <option value="{{ $asset->id }}" @selected(old('asset_id', $returnRecord->asset_id) == $asset->id)>{{ $asset->name }} ({{ $asset->code }})</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        @error('asset_id')
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-4 col-12">
                                    <div class="form-group transaction-field">
                                        <label for="user_id">Pegawai</label>
                                        <div class="transaction-input-shell">
                                            <span class="transaction-input-icon"><i class="bi bi-person-badge"></i></span>
                                            <select id="user_id" name="user_id" class="form-select @error('user_id') is-invalid @enderror">
                                                <option value="">Pilih pegawai</option>
                                                @foreach ($employees as $employee)
                                                    <option value="{{ $employee->id }}" @selected(old('user_id', $returnRecord->user_id) == $employee->id)>{{ $employee->name }} ({{ $employee->email }})</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        @error('user_id')
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-4 col-12">
                                    <div class="form-group transaction-field">
                                        <label for="loan_id">Peminjaman Terkait</label>
                                        <div class="transaction-input-shell">
                                            <span class="transaction-input-icon"><i class="bi bi-link-45deg"></i></span>
                                            <select id="loan_id" name="loan_id" class="form-select @error('loan_id') is-invalid @enderror">
                                                <option value="">Opsional</option>
                                                @foreach ($loans as $loan)
                                                    <option value="{{ $loan->id }}" @selected(old('loan_id', $returnRecord->loan_id) == $loan->id)>{{ $loan->asset?->name }} - {{ $loan->user?->name }} - {{ optional($loan->loan_date)->format('d/m/Y') }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        @error('loan_id')
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="transaction-form-section">
                            <div class="transaction-section-heading">
                                <span><i class="bi bi-clipboard-check"></i></span>
                                <div>
                                    <h5>Pemeriksaan</h5>
                                    <small>Tanggal kembali dan kondisi aset</small>
                                </div>
                            </div>
                            <div class="row g-3">
                                <div class="col-md-4 col-12">
                                    <div class="form-group transaction-field">
                                        <label for="returned_at">Tanggal Pengembalian</label>
                                        <div class="transaction-input-shell">
                                            <span class="transaction-input-icon"><i class="bi bi-calendar-check"></i></span>
                                            <input type="date" id="returned_at" name="returned_at" class="form-control @error('returned_at') is-invalid @enderror" value="{{ old('returned_at', max(optional($returnRecord->returned_at)->format('Y-m-d') ?? now()->format('Y-m-d'), now()->format('Y-m-d'))) }}" min="{{ now()->format('Y-m-d') }}">
                                        </div>
                                        @error('returned_at')
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-4 col-12">
                                    <div class="form-group transaction-field">
                                        <label for="condition">Kondisi</label>
                                        <div class="transaction-input-shell">
                                            <span class="transaction-input-icon"><i class="bi bi-tools"></i></span>
                                            <select id="condition" name="condition" class="form-select @error('condition') is-invalid @enderror">
                                                <option value="">Pilih kondisi</option>
                                                @foreach ($conditions as $condition)
                                                    <option value="{{ $condition }}" @selected(old('condition', $returnRecord->condition) === $condition)>{{ $condition }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        @error('condition')
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-4 col-12">
                                    <div class="form-group transaction-field">
                                        <label for="verified_note">Catatan Verifikasi</label>
                                        <div class="transaction-input-shell">
                                            <span class="transaction-input-icon"><i class="bi bi-patch-check"></i></span>
                                            @if ($returnRecord->status === 'Terverifikasi')
                                                <input type="text" id="verified_note" name="verified_note" class="form-control @error('verified_note') is-invalid @enderror" placeholder="Contoh: Diverifikasi admin" value="{{ old('verified_note', $returnRecord->verified_note) }}">
                                            @else
                                                <input type="text" id="verified_note" class="form-control" value="Diisi saat verifikasi pengembalian" readonly>
                                            @endif
                                        </div>
                                        @error('verified_note')
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="transaction-form-section">
                            <div class="transaction-section-heading">
                                <span><i class="bi bi-file-earmark-text"></i></span>
                                <div>
                                    <h5>Berita Acara</h5>
                                    <small>Nomor dan catatan dokumen pengembalian</small>
                                </div>
                            </div>
                            <div class="row g-3">
                                <div class="col-md-6 col-12">
                                    <div class="form-group transaction-field">
                                        <label for="report_number">Nomor Berita Acara</label>
                                        <div class="transaction-input-shell">
                                            <span class="transaction-input-icon"><i class="bi bi-hash"></i></span>
                                            <input type="text" id="report_number" name="report_number" class="form-control @error('report_number') is-invalid @enderror" placeholder="Nomor BA" value="{{ old('report_number', $returnRecord->report_number) }}">
                                        </div>
                                        @error('report_number')
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-6 col-12">
                                    <div class="form-group transaction-field">
                                        <label for="status_note">Keterangan Status</label>
                                        <div class="transaction-input-shell transaction-textarea-shell">
                                            <span class="transaction-input-icon"><i class="bi bi-chat-left-text"></i></span>
                                            <textarea id="status_note" name="status_note" class="form-control @error('status_note') is-invalid @enderror" rows="4" placeholder="Keterangan status">{{ old('status_note', $returnRecord->status_note) }}</textarea>
                                        </div>
                                        @error('status_note')
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-6 col-12">
                                    <div class="form-group transaction-field mb-0">
                                        <label for="report_note">Catatan Berita Acara</label>
                                        <div class="transaction-input-shell transaction-textarea-shell">
                                            <span class="transaction-input-icon"><i class="bi bi-pencil-square"></i></span>
                                            <textarea id="report_note" name="report_note" class="form-control @error('report_note') is-invalid @enderror" rows="4" placeholder="Catatan berita acara">{{ old('report_note', $returnRecord->report_note) }}</textarea>
                                        </div>
                                        @error('report_note')
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="transaction-form-actions">
                            <a href="{{ route('admin.returns.index') }}" class="btn btn-light-secondary icon icon-left"><i class="bi bi-arrow-left"></i><span>Batal</span></a>
                            <button type="submit" class="btn btn-primary icon icon-left"><i class="bi bi-check-circle"></i><span>Simpan</span></button>
                        </div>
                    </form>
                </div>
            </div>
        </section>
    </div>
@endsection
