@extends('layouts.app')

@section('title', 'Profile Pegawai')

@section('content')
    @php
        $profilePhotoUrl = $pegawaiUser->profilePhotoUrl();
        $signatureUrl = $pegawaiUser->signatureUrl();
    @endphp

    <div class="page-heading">
        @include('admin.partials.page-header', [
            'title' => 'Profile Pegawai',
            'subtitle' => 'Kelola informasi akun, foto profil, tanda tangan, dan keamanan akses.',
            'breadcrumb' => 'Profile',
            'homeRoute' => 'pegawai.dashboard',
        ])
    </div>

    <div class="page-content">
        <section class="section">
            <div class="row g-4">
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

                                <div class="pegawai-profile-meta row text-start g-3">
                                    <div class="col-12">
                                        <small class="text-muted d-block">ID Akun</small>
                                        <strong>#{{ $pegawaiUser->id }}</strong>
                                    </div>
                                    <div class="col-sm-6 col-12">
                                        <small class="text-muted d-block">Terdaftar</small>
                                        <strong>{{ optional($pegawaiUser->created_at)->format('d/m/Y') }}</strong>
                                    </div>
                                    <div class="col-sm-6 col-12">
                                        <small class="text-muted d-block">Role</small>
                                        <strong>{{ ucfirst($pegawaiUser->role) }}</strong>
                                    </div>
                                    <div class="col-sm-6 col-12">
                                        <small class="text-muted d-block">Email</small>
                                        <strong>{{ $pegawaiUser->email_verified_at ? 'Terverifikasi' : 'Belum terverifikasi' }}</strong>
                                    </div>
                                    <div class="col-sm-6 col-12">
                                        <small class="text-muted d-block">Tanda Tangan</small>
                                        <strong>{{ $pegawaiUser->hasSignature() ? 'Sudah diunggah' : 'Belum diunggah' }}</strong>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-xl-8">
                    <div class="row g-4">
                        <div class="col-12 col-lg-6">
                            <div class="card pegawai-panel h-100">
                                <div class="card-header">
                                    <h4 class="card-title mb-1">Foto Profil</h4>
                                    <p class="mb-0 text-muted">Unggah foto profil agar akun lebih mudah dikenali.</p>
                                </div>
                                <div class="card-body">
                                    @if ($errors->updatePhoto->any())
                                        <div class="alert alert-light-danger color-danger">
                                            <i class="bi bi-exclamation-circle me-1"></i>{{ $errors->updatePhoto->first() }}
                                        </div>
                                    @endif

                                    <div class="pegawai-profile-preview border rounded-3 p-3 mb-3 bg-light text-center">
                                        <small class="text-muted d-block mb-2">Foto saat ini</small>
                                        <div class="avatar avatar-xl {{ $profilePhotoUrl ? '' : 'bg-light-primary' }} mx-auto">
                                            @if ($profilePhotoUrl)
                                                <img src="{{ $profilePhotoUrl }}" alt="Foto profil {{ $pegawaiUser->name }}">
                                            @else
                                                <span class="avatar-content">{{ $pegawaiInitials }}</span>
                                            @endif
                                        </div>
                                    </div>

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
                                        <div class="d-flex flex-wrap gap-2 pt-2">
                                            <button type="submit" class="btn btn-primary icon icon-left">
                                                <i class="bi bi-upload"></i><span>Simpan Foto</span>
                                            </button>

                                            @if ($pegawaiUser->hasProfilePhoto())
                                                <button type="submit" name="remove_profile_photo" value="1" class="btn btn-light-danger icon icon-left" onclick="return confirm('Hapus foto profil saat ini?')">
                                                    <i class="bi bi-trash"></i><span>Hapus Foto</span>
                                                </button>
                                            @endif
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <div class="col-12 col-lg-6">
                            <div class="card pegawai-panel h-100">
                                <div class="card-header">
                                    <h4 class="card-title mb-1">Tanda Tangan Digital</h4>
                                    <p class="mb-0 text-muted">Dipakai pada surat peminjaman aset yang dibuat dari akun ini.</p>
                                </div>
                                <div class="card-body">
                                    @if ($errors->updateSignature->any())
                                        <div class="alert alert-light-danger color-danger">
                                            <i class="bi bi-exclamation-circle me-1"></i>{{ $errors->updateSignature->first() }}
                                        </div>
                                    @endif

                                    <div class="pegawai-profile-preview border rounded-3 p-3 mb-3 bg-light text-center">
                                        <small class="text-muted d-block mb-2">Preview tanda tangan</small>
                                        @if ($signatureUrl)
                                            <img src="{{ $signatureUrl }}" alt="Tanda tangan {{ $pegawaiUser->name }}" style="max-width: 280px; max-height: 120px; width: auto; height: auto;">
                                        @else
                                            <span class="text-muted">Belum ada tanda tangan yang diunggah.</span>
                                        @endif
                                    </div>

                                    <form action="{{ route('pegawai.profile.update') }}" method="POST" enctype="multipart/form-data">
                                        @csrf
                                        @method('PATCH')
                                        <div class="form-group">
                                            <label for="signature_file">Upload Tanda Tangan</label>
                                            <input type="file" id="signature_file" name="signature_file" class="form-control @error('signature_file', 'updateSignature') is-invalid @enderror" accept=".png,.jpg,.jpeg">
                                            <small class="text-muted d-block mt-2">Format PNG, JPG, atau JPEG dengan ukuran maksimal 2 MB. PNG transparan tetap disarankan agar tampil rapi pada surat.</small>
                                            @error('signature_file', 'updateSignature')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        <div class="d-flex flex-wrap gap-2 pt-2">
                                            <button type="submit" class="btn btn-primary icon icon-left">
                                                <i class="bi bi-pen"></i><span>Simpan Tanda Tangan</span>
                                            </button>

                                            @if ($pegawaiUser->hasSignature())
                                                <button type="submit" name="remove_signature" value="1" class="btn btn-light-danger icon icon-left" onclick="return confirm('Hapus tanda tangan saat ini?')">
                                                    <i class="bi bi-trash"></i><span>Hapus Tanda Tangan</span>
                                                </button>
                                            @endif
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <div class="col-12">
                            <div class="card pegawai-panel">
                                <div class="card-header">
                                    <h4 class="card-title mb-1">Ubah Password</h4>
                                    <p class="mb-0 text-muted">Gunakan password yang kuat untuk menjaga keamanan akun.</p>
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
                                        <div class="row g-3">
                                            <div class="col-lg-4 col-md-6 col-12">
                                                <div class="form-group">
                                                    <label for="current_password">Password Saat Ini</label>
                                                    <input type="password" id="current_password" name="current_password" class="form-control @error('current_password', 'updatePassword') is-invalid @enderror" autocomplete="current-password">
                                                    @error('current_password', 'updatePassword')
                                                        <div class="invalid-feedback">{{ $message }}</div>
                                                    @enderror
                                                </div>
                                            </div>
                                            <div class="col-lg-4 col-md-6 col-12">
                                                <div class="form-group">
                                                    <label for="password">Password Baru</label>
                                                    <input type="password" id="password" name="password" class="form-control @error('password', 'updatePassword') is-invalid @enderror" autocomplete="new-password">
                                                    @error('password', 'updatePassword')
                                                        <div class="invalid-feedback">{{ $message }}</div>
                                                    @enderror
                                                </div>
                                            </div>
                                            <div class="col-lg-4 col-md-6 col-12">
                                                <div class="form-group">
                                                    <label for="password_confirmation">Konfirmasi Password Baru</label>
                                                    <input type="password" id="password_confirmation" name="password_confirmation" class="form-control @error('password_confirmation', 'updatePassword') is-invalid @enderror" autocomplete="new-password">
                                                    @error('password_confirmation', 'updatePassword')
                                                        <div class="invalid-feedback">{{ $message }}</div>
                                                    @enderror
                                                </div>
                                            </div>
                                            <div class="col-12 d-flex justify-content-end pt-1">
                                                <button type="submit" class="btn btn-primary icon icon-left">
                                                    <i class="bi bi-shield-lock"></i><span>Simpan Password Baru</span>
                                                </button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
@endsection
