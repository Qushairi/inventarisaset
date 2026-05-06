@extends('layouts.app')

@section('title', 'Profil Admin')

@section('content')
    @php
        $profilePhotoUrl = $adminUser->profilePhotoUrl();
        $signatureUrl = $adminUser->signatureUrl();
    @endphp

    <div class="page-heading">
        @include('admin.partials.page-header', [
            'title' => 'Profil Admin',
            'subtitle' => 'Kelola akun admin, foto profil, tanda tangan digital, dan keamanan akun.',
            'breadcrumb' => 'Profil',
            'homeRoute' => 'admin.dashboard',
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
                <div class="card">
                    <div class="card-body text-center">
                        <div class="avatar avatar-xl {{ $profilePhotoUrl ? '' : 'bg-light-primary' }} mb-3">
                            @if ($profilePhotoUrl)
                                <img src="{{ $profilePhotoUrl }}" alt="Foto profil {{ $adminUser->name }}">
                            @else
                                <span class="avatar-content">{{ $adminUser->initials() }}</span>
                            @endif
                        </div>
                        <h4 class="mb-1">{{ $adminUser->name }}</h4>
                        <p class="text-muted mb-2">{{ $adminUser->email }}</p>
                        <span class="badge bg-light-primary">Admin</span>

                        <div class="mt-4 text-start">
                            <div class="mb-3">
                                <small class="text-muted d-block">Status Tanda Tangan</small>
                                <strong>{{ $adminUser->hasSignature() ? 'Sudah tersedia' : 'Belum diunggah' }}</strong>
                            </div>
                            <div class="mb-3">
                                <small class="text-muted d-block">Terdaftar</small>
                                <strong>{{ optional($adminUser->created_at)->format('d/m/Y H:i') }} WIB</strong>
                            </div>
                            <div>
                                <small class="text-muted d-block">Update TTD Terakhir</small>
                                <strong>{{ $adminUser->signature_updated_at ? $adminUser->signature_updated_at->format('d/m/Y H:i') . ' WIB' : '-' }}</strong>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title mb-1">Ringkasan</h4>
                        <p class="mb-0 text-muted">Ikhtisar aktivitas yang dikelola akun admin ini.</p>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            @foreach ($adminStats as $stat)
                                <div class="col-12 col-sm-6 col-xl-12">
                                    <div class="border rounded-3 p-3 h-100">
                                        <div class="d-flex align-items-center gap-3">
                                            <div class="avatar bg-light-{{ $stat['variant'] }}">
                                                <span class="avatar-content">
                                                    <i class="bi bi-{{ $stat['icon'] }}"></i>
                                                </span>
                                            </div>
                                            <div>
                                                <small class="text-muted d-block">{{ $stat['label'] }}</small>
                                                <strong>{{ $stat['value'] }}</strong>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-xl-8">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title mb-1">Foto Profil</h4>
                        <p class="mb-0 text-muted">Gunakan foto profil untuk memudahkan identifikasi akun admin.</p>
                    </div>
                    <div class="card-body">
                        @if ($errors->updatePhoto->any())
                            <div class="alert alert-light-danger color-danger">
                                <i class="bi bi-exclamation-circle me-1"></i>{{ $errors->updatePhoto->first() }}
                            </div>
                        @endif

                        <form action="{{ route('admin.profile.update') }}" method="POST" enctype="multipart/form-data">
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

                        @if ($adminUser->hasProfilePhoto())
                            <form action="{{ route('admin.profile.update') }}" method="POST" class="mt-3">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="remove_profile_photo" value="1">
                                <button type="submit" class="btn btn-light-danger icon icon-left" onclick="return confirm('Hapus foto profil admin saat ini?')">
                                    <i class="bi bi-trash"></i><span>Hapus Foto</span>
                                </button>
                            </form>
                        @endif
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title mb-1">Tanda Tangan Digital</h4>
                        <p class="mb-0 text-muted">Tanda tangan ini akan dipakai otomatis pada surat yang disetujui admin.</p>
                    </div>
                    <div class="card-body">
                        @if ($errors->updateSignature->any())
                            <div class="alert alert-light-danger color-danger">
                                <i class="bi bi-exclamation-circle me-1"></i>{{ $errors->updateSignature->first() }}
                            </div>
                        @endif

                        @if ($signatureUrl)
                            <div class="border rounded-3 p-3 mb-3 bg-light">
                                <small class="text-muted d-block mb-2">Preview tanda tangan</small>
                                <img src="{{ $signatureUrl }}" alt="Tanda tangan {{ $adminUser->name }}" style="max-width: 280px; max-height: 120px; width: auto; height: auto;">
                            </div>
                        @endif

                        <form action="{{ route('admin.profile.update') }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            @method('PATCH')
                            <div class="form-group">
                                <label for="signature_file">Upload Tanda Tangan</label>
                                <input type="file" id="signature_file" name="signature_file" class="form-control @error('signature_file', 'updateSignature') is-invalid @enderror" accept=".png">
                                <small class="text-muted d-block mt-2">Gunakan file PNG transparan dengan ukuran maksimal 2 MB agar tampil rapi pada surat peminjaman.</small>
                                @error('signature_file', 'updateSignature')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <button type="submit" class="btn btn-primary icon icon-left">
                                <i class="bi bi-pen"></i><span>Simpan Tanda Tangan</span>
                            </button>
                        </form>

                        @if ($adminUser->hasSignature())
                            <form action="{{ route('admin.profile.update') }}" method="POST" class="mt-3">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="remove_signature" value="1">
                                <button type="submit" class="btn btn-light-danger icon icon-left" onclick="return confirm('Hapus tanda tangan admin saat ini?')">
                                    <i class="bi bi-trash"></i><span>Hapus Tanda Tangan</span>
                                </button>
                            </form>
                        @endif
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title mb-1">Ubah Password</h4>
                        <p class="mb-0 text-muted">Gunakan password yang kuat untuk menjaga keamanan akun admin.</p>
                    </div>
                    <div class="card-body">
                        @if ($errors->updatePassword->any())
                            <div class="alert alert-light-danger color-danger">
                                <i class="bi bi-exclamation-circle me-1"></i>{{ $errors->updatePassword->first() }}
                            </div>
                        @endif

                        <form action="{{ route('admin.profile.password.update') }}" method="POST">
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
                                        <input type="password" id="password_confirmation" name="password_confirmation" class="form-control" autocomplete="new-password">
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
        </section>
    </div>
@endsection
