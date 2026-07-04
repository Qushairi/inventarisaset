@extends('layouts.app')

@section('title', 'Profil Pegawai')

@section('content')
    @php
        $profilePhotoUrl = $pegawaiUser->profilePhotoUrl();
        $signatureUrl = $pegawaiUser->signatureUrl();
    @endphp

    <div class="page-heading">
        @include('pegawai.partials.page-heading', [
            'title' => 'Profile',
            'breadcrumb' => 'Profile',
        ])
    </div>

    <div class="page-content">
        <section class="section">
            @if (session('success'))
                <div class="alert alert-light-success color-success">
                    <i class="bi bi-check-circle me-1"></i>{{ session('success') }}
                </div>
            @endif

            <div class="card pegawai-panel pegawai-profile-hero">
                <div class="card-body">
                    <div class="d-flex align-items-center flex-wrap gap-4">
                        <div class="pegawai-profile-photo">
                            @if ($profilePhotoUrl)
                                <img src="{{ $profilePhotoUrl }}" alt="Foto profil {{ $pegawaiUser->name }}">
                            @else
                                <div class="avatar avatar-xl bg-light-primary">
                                    <span class="avatar-content">{{ $pegawaiInitials }}</span>
                                </div>
                            @endif
                        </div>
                        <div class="pegawai-profile-identity">
                            <h3 class="mb-2 text-uppercase">{{ $pegawaiUser->name }}</h3>
                            <div class="d-flex align-items-center flex-wrap gap-3 text-muted">
                                <span><i class="bi bi-person-badge me-1"></i>#{{ $pegawaiUser->id }}</span>
                                <span><i class="bi bi-shield-check me-1"></i>{{ ucfirst($pegawaiUser->role) }}</span>
                                <span><i class="bi bi-envelope-fill me-1"></i>{{ $pegawaiUser->email }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card pegawai-panel">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-3">
                    <h4 class="card-title mb-0">Detail Profil</h4>
                    <span class="badge bg-light-primary">
                        {{ $pegawaiUser->email_verified_at ? 'Email terverifikasi' : 'Email belum terverifikasi' }}
                    </span>
                </div>
                <div class="card-body">
                    <div class="row g-4">
                        <div class="col-md-4 col-12">
                            <small class="text-muted d-block mb-1">Nama Lengkap</small>
                            <strong class="text-primary text-uppercase">{{ $pegawaiUser->name }}</strong>
                        </div>
                        <div class="col-md-4 col-12">
                            <small class="text-muted d-block mb-1">Email</small>
                            <strong class="text-primary">{{ $pegawaiUser->email }}</strong>
                        </div>
                        <div class="col-md-4 col-12">
                            <small class="text-muted d-block mb-1">Role</small>
                            <strong>{{ ucfirst($pegawaiUser->role) }}</strong>
                        </div>
                        <div class="col-md-4 col-12">
                            <small class="text-muted d-block mb-1">ID Akun</small>
                            <strong>#{{ $pegawaiUser->id }}</strong>
                        </div>
                        <div class="col-md-4 col-12">
                            <small class="text-muted d-block mb-1">Terdaftar</small>
                            <strong>{{ optional($pegawaiUser->created_at)->format('d/m/Y') }}</strong>
                        </div>
                        <div class="col-md-4 col-12">
                            <small class="text-muted d-block mb-1">Tanda Tangan</small>
                            <strong>{{ $pegawaiUser->hasSignature() ? 'Sudah diunggah' : 'Belum diunggah' }}</strong>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-4 align-items-start">
                <div class="col-12 col-xl-4">
                    <div class="card pegawai-panel">
                        <div class="card-header">
                            <h4 class="card-title mb-0">Upload Foto</h4>
                        </div>
                        <div class="card-body">
                            @if ($errors->updatePhoto->any())
                                <div class="alert alert-light-danger color-danger">
                                    <i class="bi bi-exclamation-circle me-1"></i>{{ $errors->updatePhoto->first() }}
                                </div>
                            @endif

                            <div class="pegawai-profile-preview border rounded p-3 mb-3 bg-light text-center">
                                <small class="text-muted d-block mb-2">Foto saat ini</small>
                                @if ($profilePhotoUrl)
                                    <img src="{{ $profilePhotoUrl }}" alt="Foto profil {{ $pegawaiUser->name }}" class="pegawai-profile-preview-photo">
                                @else
                                    <div class="avatar avatar-xl bg-light-primary mx-auto">
                                        <span class="avatar-content">{{ $pegawaiInitials }}</span>
                                    </div>
                                @endif
                            </div>

                            <form action="{{ route('pegawai.profile.update') }}" method="POST" enctype="multipart/form-data" data-swal-confirm data-swal-icon="question" data-swal-title="Simpan foto profil?" data-swal-text="Foto profil akun akan diperbarui." data-swal-confirm-text="Ya, simpan" data-swal-confirm-color="#435ebe">
                                @csrf
                                @method('PATCH')
                                <div class="form-group">
                                    <label for="profile_photo">Pilih Foto</label>
                                    <input type="file" id="profile_photo" name="profile_photo" class="form-control @error('profile_photo', 'updatePhoto') is-invalid @enderror" accept=".jpg,.jpeg,.jfif,.png,.webp">
                                    <small class="text-muted d-block mt-2">Format JPG, JFIF, PNG, atau WEBP. Maksimal 2 MB.</small>
                                    @error('profile_photo', 'updatePhoto')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="d-flex flex-wrap gap-2 pt-2">
                                    <button type="submit" class="btn btn-primary icon icon-left">
                                        <i class="bi bi-upload"></i><span>Simpan Foto</span>
                                    </button>
                                    @if ($pegawaiUser->hasProfilePhoto())
                                        <button type="submit" name="remove_profile_photo" value="1" class="btn btn-light-danger icon icon-left" data-swal-confirm data-swal-icon="warning" data-swal-title="Hapus foto profil?" data-swal-text="Foto profil saat ini akan dihapus dari akun." data-swal-confirm-text="Ya, hapus" data-swal-confirm-color="#dc3545">
                                            <i class="bi bi-trash"></i><span>Hapus</span>
                                        </button>
                                    @endif
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-xl-4">
                    <div class="card pegawai-panel">
                        <div class="card-header">
                            <h4 class="card-title mb-0">Upload TTD</h4>
                        </div>
                        <div class="card-body">
                            @if ($errors->updateSignature->any())
                                <div class="alert alert-light-danger color-danger">
                                    <i class="bi bi-exclamation-circle me-1"></i>{{ $errors->updateSignature->first() }}
                                </div>
                            @endif

                            <div class="pegawai-profile-preview border rounded p-3 mb-3 bg-light text-center">
                                <small class="text-muted d-block mb-2">Preview tanda tangan</small>
                                @if ($signatureUrl)
                                    <img src="{{ $signatureUrl }}" alt="Tanda tangan {{ $pegawaiUser->name }}" class="pegawai-signature-preview">
                                @else
                                    <span class="text-muted">Belum ada tanda tangan.</span>
                                @endif
                            </div>

                            <form action="{{ route('pegawai.profile.update') }}" method="POST" enctype="multipart/form-data" data-swal-confirm data-swal-icon="question" data-swal-title="Simpan tanda tangan?" data-swal-text="Tanda tangan akun akan diperbarui." data-swal-confirm-text="Ya, simpan" data-swal-confirm-color="#435ebe">
                                @csrf
                                @method('PATCH')
                                <div class="form-group">
                                    <label for="signature_file">Pilih Tanda Tangan</label>
                                    <input type="file" id="signature_file" name="signature_file" class="form-control @error('signature_file', 'updateSignature') is-invalid @enderror" accept=".jpg,.jpeg,.jfif,.png,.webp">
                                    <small class="text-muted d-block mt-2">Format JPG, JFIF, PNG, atau WEBP. Maksimal 2 MB.</small>
                                    @error('signature_file', 'updateSignature')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="d-flex flex-wrap gap-2 pt-2">
                                    <button type="submit" class="btn btn-primary icon icon-left">
                                        <i class="bi bi-pen"></i><span>Simpan TTD</span>
                                    </button>
                                    @if ($pegawaiUser->hasSignature())
                                        <button type="submit" name="remove_signature" value="1" class="btn btn-light-danger icon icon-left" data-swal-confirm data-swal-icon="warning" data-swal-title="Hapus tanda tangan?" data-swal-text="Tanda tangan saat ini akan dihapus dari akun." data-swal-confirm-text="Ya, hapus" data-swal-confirm-color="#dc3545">
                                            <i class="bi bi-trash"></i><span>Hapus</span>
                                        </button>
                                    @endif
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-xl-4">
                    <div class="card pegawai-panel">
                        <div class="card-header">
                            <h4 class="card-title mb-0">Ubah Password</h4>
                        </div>
                        <div class="card-body">
                            @if ($errors->updatePassword->any())
                                <div class="alert alert-light-danger color-danger">
                                    <i class="bi bi-exclamation-circle me-1"></i>{{ $errors->updatePassword->first() }}
                                </div>
                            @endif

                            <form action="{{ route('pegawai.profile.password.update') }}" method="POST" data-swal-confirm data-swal-icon="question" data-swal-title="Ubah password akun?" data-swal-text="Pastikan password baru dan konfirmasinya sudah benar." data-swal-confirm-text="Ya, ubah password" data-swal-confirm-color="#435ebe">
                                @csrf
                                @method('PUT')
                                <div class="form-group">
                                    <label for="current_password">Password Saat Ini</label>
                                    <input type="password" id="current_password" name="current_password" class="form-control @error('current_password', 'updatePassword') is-invalid @enderror" autocomplete="current-password">
                                    @error('current_password', 'updatePassword')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="form-group">
                                    <label for="password">Password Baru</label>
                                    <input type="password" id="password" name="password" class="form-control @error('password', 'updatePassword') is-invalid @enderror" autocomplete="new-password">
                                    @error('password', 'updatePassword')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="form-group">
                                    <label for="password_confirmation">Konfirmasi Password Baru</label>
                                    <input type="password" id="password_confirmation" name="password_confirmation" class="form-control @error('password_confirmation', 'updatePassword') is-invalid @enderror" autocomplete="new-password">
                                    @error('password_confirmation', 'updatePassword')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="d-grid pt-2">
                                    <button type="submit" class="btn btn-primary icon icon-left">
                                        <i class="bi bi-shield-lock"></i><span>Simpan Password</span>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
@endsection
