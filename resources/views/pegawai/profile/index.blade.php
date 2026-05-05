@extends('layouts.app')

@section('title', 'Profile Pegawai')

@section('content')
    @php
        $profilePhotoUrl = $pegawaiUser->profilePhotoUrl();
    @endphp

    <div class="page-heading">
        @include('admin.partials.page-header', [
            'title' => 'Profile Pegawai',
            'subtitle' => 'Informasi akun pegawai dan aktivitas inventaris terbaru.',
            'breadcrumb' => 'Profile',
            'homeRoute' => 'pegawai.dashboard',
        ])
    </div>

    <div class="page-content">
        <section class="row">
            <div class="col-12">
                @if (session('success'))
                    <div class="alert alert-light-success color-success">
                        <i class="bi bi-check-circle me-1"></i>{{ session('success') }}
                    </div>
                @endif
            </div>

            <div class="col-12 col-xl-4">
                <div class="card pegawai-panel pegawai-profile-card">
                    <div class="card-body text-center py-4">
                        <div class="pegawai-profile-summary">
                            <div class="avatar avatar-xl {{ $profilePhotoUrl ? '' : 'bg-light-primary' }} mx-auto mb-3">
                                @if ($profilePhotoUrl)
                                    <img src="{{ $profilePhotoUrl }}" alt="Foto profil {{ $pegawaiUser->name }}">
                                @else
                                    <span class="avatar-content">{{ $pegawaiInitials }}</span>
                                @endif
                            </div>
                            <h4 class="mb-1">{{ $pegawaiUser->name }}</h4>
                            <p class="text-muted mb-2">{{ $pegawaiUser->email }}</p>
                            <span class="badge bg-light-secondary">{{ ucfirst($pegawaiUser->role) }}</span>

                            <div class="pegawai-profile-meta row text-start">
                                <div class="col-12 mb-3">
                                    <small class="text-muted d-block">ID Akun</small>
                                    <strong>#{{ $pegawaiUser->id }}</strong>
                                </div>
                                <div class="col-12 mb-3">
                                    <small class="text-muted d-block">Terdaftar</small>
                                    <strong>{{ optional($pegawaiUser->created_at)->format('d/m/Y H:i') }} WIB</strong>
                                </div>
                                <div class="col-12">
                                    <small class="text-muted d-block">Email Terverifikasi</small>
                                    <strong>{{ $pegawaiUser->email_verified_at ? 'Sudah' : 'Belum' }}</strong>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card pegawai-panel">
                    <div class="card-header">
                        <h4 class="card-title mb-1">Foto Profil</h4>
                        <p class="mb-0 text-muted">Unggah foto profil baru agar akun lebih mudah dikenali.</p>
                    </div>
                    <div class="card-body">
                        @if ($errors->updatePhoto->any())
                            <div class="alert alert-light-danger color-danger">
                                <i class="bi bi-exclamation-circle me-1"></i>{{ $errors->updatePhoto->first() }}
                            </div>
                        @endif

                        <form action="{{ route('pegawai.profile.update') }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            @method('PATCH')
                            <div class="form-group">
                                <label for="profile_photo">Pilih Foto</label>
                                <input type="file" id="profile_photo" name="profile_photo" class="form-control @error('profile_photo', 'updatePhoto') is-invalid @enderror" accept=".jpg,.jpeg,.png,.webp">
                                <small class="text-muted d-block mt-2">Format JPG, PNG, atau WEBP dengan ukuran maksimal 2 MB.</small>
                                @error('profile_photo', 'updatePhoto')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <button type="submit" class="btn btn-primary icon icon-left">
                                <i class="bi bi-upload"></i><span>Upload Foto</span>
                            </button>
                        </form>

                        @if ($pegawaiUser->hasProfilePhoto())
                            <form action="{{ route('pegawai.profile.update') }}" method="POST" class="mt-3">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="remove_profile_photo" value="1">
                                <button type="submit" class="btn btn-light-danger icon icon-left" onclick="return confirm('Hapus foto profil saat ini?')">
                                    <i class="bi bi-trash"></i><span>Hapus Foto</span>
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>

            <div class="col-12 col-xl-8">
                <div class="row">
                    <div class="col-12">
                        <div class="card pegawai-panel">
                            <div class="card-header">
                                <h4 class="card-title mb-1">Ubah Password</h4>
                                <p class="mb-0 text-muted">Gunakan password yang kuat agar akun tetap aman.</p>
                            </div>
                            <div class="card-body">
                                @if ($errors->updatePassword->any())
                                    <div class="alert alert-light-danger color-danger">
                                        <i class="bi bi-exclamation-circle me-1"></i>{{ $errors->updatePassword->first() }}
                                    </div>
                                @endif

                                <form action="{{ route('pegawai.profile.password.update') }}" method="POST">
                                    @csrf
                                    @method('PUT')
                                    <div class="row">
                                        <div class="col-md-4 col-12">
                                            <div class="form-group">
                                                <label for="current_password">Password Saat Ini</label>
                                                <input type="password" id="current_password" name="current_password" class="form-control @error('current_password', 'updatePassword') is-invalid @enderror" autocomplete="current-password">
                                                @error('current_password', 'updatePassword')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>
                                        <div class="col-md-4 col-12">
                                            <div class="form-group">
                                                <label for="password">Password Baru</label>
                                                <input type="password" id="password" name="password" class="form-control @error('password', 'updatePassword') is-invalid @enderror" autocomplete="new-password">
                                                @error('password', 'updatePassword')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>
                                        <div class="col-md-4 col-12">
                                            <div class="form-group">
                                                <label for="password_confirmation">Konfirmasi Password Baru</label>
                                                <input type="password" id="password_confirmation" name="password_confirmation" class="form-control @error('password_confirmation', 'updatePassword') is-invalid @enderror" autocomplete="new-password">
                                                @error('password_confirmation', 'updatePassword')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>
                                        <div class="col-12 d-flex justify-content-end">
                                            <button type="submit" class="btn btn-primary icon icon-left">
                                                <i class="bi bi-shield-lock"></i><span>Simpan Password Baru</span>
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    @foreach ($profileStats as $stat)
                        @php
                            $iconClass = match ($stat['variant']) {
                                'success' => 'green',
                                'warning' => 'red',
                                'info' => 'blue',
                                default => 'purple',
                            };
                        @endphp
                        <div class="col-12 col-md-6">
                            <div class="card pegawai-panel pegawai-stat-card h-100">
                                <div class="card-body px-3 py-4-5">
                                    <div class="row">
                                        <div class="col-3">
                                            <div class="stats-icon {{ $iconClass }}">
                                                <i class="bi bi-{{ $stat['icon'] }}"></i>
                                            </div>
                                        </div>
                                        <div class="col-9">
                                            <h6 class="text-muted font-semibold">{{ $stat['label'] }}</h6>
                                            <h5 class="font-extrabold mb-0">{{ $stat['value'] }}</h5>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach

                    <div class="col-12">
                        <div class="card pegawai-panel pegawai-table-card">
                            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-3">
                                <div>
                                    <h4 class="card-title mb-1">Peminjaman Terakhir</h4>
                                    <p class="mb-0 text-muted">Ringkasan peminjaman terbaru yang dilakukan dari akun ini.</p>
                                </div>
                                <a href="{{ route('pegawai.loans.index') }}" class="btn btn-light-primary btn-sm icon icon-left">
                                    <i class="bi bi-box-arrow-up-right"></i><span>Lihat Riwayat</span>
                                </a>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-hover table-lg mb-0">
                                        <thead>
                                            <tr>
                                                <th>Aset</th>
                                                <th>Tanggal Pinjam</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse ($recentLoans as $loan)
                                                <tr>
                                                    <td>
                                                        <div>{{ $loan->asset?->name }}</div>
                                                        <small class="text-muted">{{ $loan->asset?->code }}</small>
                                                    </td>
                                                    <td>
                                                        <div>{{ optional($loan->loan_date)->format('d/m/Y') }}</div>
                                                        <small class="text-muted">Rencana kembali {{ optional($loan->planned_return_date)->format('d/m/Y') }}</small>
                                                    </td>
                                                    <td>
                                                        <span class="badge {{ $loan->status === 'Menunggu' ? 'bg-light-warning' : ($loan->status === 'Ditolak' ? 'bg-light-danger' : 'bg-light-success') }}">{{ $loan->status }}</span>
                                                        <div><small class="text-muted">{{ $loan->status_note }}</small></div>
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="3" class="text-center text-muted">Belum ada data peminjaman.</td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-12">
                        <div class="card pegawai-panel pegawai-table-card">
                            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-3">
                                <div>
                                    <h4 class="card-title mb-1">Pengembalian Terakhir</h4>
                                    <p class="mb-0 text-muted">Status pengembalian aset terbaru yang sudah Anda ajukan.</p>
                                </div>
                                <a href="{{ route('pegawai.returns.index') }}" class="btn btn-light-primary btn-sm icon icon-left">
                                    <i class="bi bi-box-arrow-up-right"></i><span>Lihat Riwayat</span>
                                </a>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-hover table-lg mb-0">
                                        <thead>
                                            <tr>
                                                <th>Aset</th>
                                                <th>Tanggal Kembali</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse ($recentReturns as $return)
                                                <tr>
                                                    <td>
                                                        <div>{{ $return->asset?->name }}</div>
                                                        <small class="text-muted">{{ $return->asset?->code }}</small>
                                                    </td>
                                                    <td>
                                                        <div>{{ optional($return->returned_at)->format('d/m/Y') }}</div>
                                                        <small class="text-muted">{{ $return->report_number }}</small>
                                                    </td>
                                                    <td>
                                                        <span class="badge {{ $return->status === 'Terverifikasi' ? 'bg-light-success' : 'bg-light-info' }}">{{ $return->status }}</span>
                                                        <div><small class="text-muted">{{ $return->status_note }}</small></div>
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="3" class="text-center text-muted">Belum ada data pengembalian.</td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
@endsection
