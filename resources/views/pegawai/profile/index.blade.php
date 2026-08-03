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
        <section class="row g-4">
            <div class="col-12">
                @if (session('success'))
                    <div class="alert alert-light-success color-success mb-0">
                        <i class="bi bi-check-circle me-1"></i>{{ session('success') }}
                    </div>
                @endif
            </div>

            <!-- Left Column: User Summary Card -->
            <div class="col-12 col-xl-4">
                <div class="card h-100">
                    <div class="card-body text-center p-4">
                        <div class="avatar pegawai-avatar-lg {{ $profilePhotoUrl ? '' : 'bg-light-primary' }} mb-3 mx-auto">
                            @if ($profilePhotoUrl)
                                <img src="{{ $profilePhotoUrl }}" alt="Foto profil {{ $pegawaiUser->name }}">
                            @else
                                <span class="avatar-content">{{ $pegawaiInitials }}</span>
                            @endif
                        </div>
                        <h4 class="mb-1 fw-bold text-dark text-capitalize">{{ $pegawaiUser->name }}</h4>
                        <p class="text-muted mb-2 font-14">{{ $pegawaiUser->email }}</p>
                        <span class="badge bg-light-primary px-3 py-1.5 fw-semibold">Pegawai</span>

                        <hr class="my-4">

                        <div class="text-start">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <span class="text-muted small">ID Akun</span>
                                <span class="fw-bold text-dark">#{{ $pegawaiUser->id }}</span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <span class="text-muted small">Status Email</span>
                                <span class="fw-semibold text-dark">{{ $pegawaiUser->email_verified_at ? 'Terverifikasi' : 'Belum Terverifikasi' }}</span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <span class="text-muted small">NIP</span>
                                <span class="fw-semibold text-dark">{{ $pegawaiUser->nip ?? '-' }}</span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="text-muted small">Terdaftar</span>
                                <span class="fw-semibold text-dark">{{ optional($pegawaiUser->created_at)->translatedFormat('d F Y') }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column: Tabbed Profile Card -->
            <div class="col-12 col-xl-8">
                <div class="card h-100">
                    <div class="card-header border-bottom">
                        <ul class="nav nav-pills card-header-pills flex-wrap gap-2" id="profileTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active fw-semibold px-3 py-2" id="detail-tab" data-bs-toggle="pill" data-bs-target="#detail-pane" type="button" role="tab" aria-controls="detail-pane" aria-selected="true">
                                    <i class="bi bi-person-vcard me-2"></i>Detail Informasi
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link fw-semibold px-3 py-2" id="photo-tab" data-bs-toggle="pill" data-bs-target="#photo-pane" type="button" role="tab" aria-controls="photo-pane" aria-selected="false">
                                    <i class="bi bi-image me-2"></i>Foto Profil
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link fw-semibold px-3 py-2" id="signature-tab" data-bs-toggle="pill" data-bs-target="#signature-pane" type="button" role="tab" aria-controls="signature-pane" aria-selected="false">
                                    <i class="bi bi-pen me-2"></i>Tanda Tangan
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link fw-semibold px-3 py-2" id="password-tab" data-bs-toggle="pill" data-bs-target="#password-pane" type="button" role="tab" aria-controls="password-pane" aria-selected="false">
                                    <i class="bi bi-shield-lock me-2"></i>Ubah Password
                                </button>
                            </li>
                        </ul>
                    </div>

                    <div class="card-body pt-4">
                        <div class="tab-content" id="profileTabsContent">
                            <!-- Tab 1: Detail Informasi -->
                            <div class="tab-pane fade show active" id="detail-pane" role="tabpanel" aria-labelledby="detail-tab">
                                <h5 class="fw-bold mb-3 text-dark"><i class="bi bi-card-heading text-primary me-2"></i>Informasi Akun Pegawai</h5>
                                <div class="row g-3">
                                    <div class="col-md-6 col-12">
                                        <div class="p-3 border rounded-3 bg-light-subtle">
                                            <small class="text-muted d-block mb-1">Nama Lengkap</small>
                                            <strong class="text-primary text-uppercase fs-6">{{ $pegawaiUser->name }}</strong>
                                        </div>
                                    </div>
                                    <div class="col-md-6 col-12">
                                        <div class="p-3 border rounded-3 bg-light-subtle">
                                            <small class="text-muted d-block mb-1">Alamat Email</small>
                                            <strong class="text-primary fs-6">{{ $pegawaiUser->email }}</strong>
                                        </div>
                                    </div>
                                    <div class="col-md-6 col-12">
                                        <div class="p-3 border rounded-3 bg-light-subtle">
                                            <small class="text-muted d-block mb-1">NIP (Nomor Induk Pegawai)</small>
                                            <strong class="text-dark fs-6">{{ $pegawaiUser->nip ?? '-' }}</strong>
                                        </div>
                                    </div>
                                    <div class="col-md-6 col-12">
                                        <div class="p-3 border rounded-3 bg-light-subtle">
                                            <small class="text-muted d-block mb-1">Role / Peran</small>
                                            <strong class="text-dark fs-6">{{ ucfirst($pegawaiUser->role) }}</strong>
                                        </div>
                                    </div>
                                    <div class="col-md-6 col-12">
                                        <div class="p-3 border rounded-3 bg-light-subtle">
                                            <small class="text-muted d-block mb-1">ID Pengguna</small>
                                            <strong class="text-dark fs-6">#{{ $pegawaiUser->id }}</strong>
                                        </div>
                                    </div>
                                    <div class="col-md-6 col-12">
                                        <div class="p-3 border rounded-3 bg-light-subtle">
                                            <small class="text-muted d-block mb-1">Waktu Pendaftaran</small>
                                            <strong class="text-dark fs-6">{{ optional($pegawaiUser->created_at)->format('d F Y, H:i') }} WIB</strong>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Tab 2: Foto Profil -->
                            <div class="tab-pane fade" id="photo-pane" role="tabpanel" aria-labelledby="photo-tab">
                                <h5 class="fw-bold mb-3 text-dark"><i class="bi bi-person-circle text-primary me-2"></i>Foto Profil Akun</h5>
                                <p class="text-muted small mb-4">Gunakan foto profil resmi untuk mempermudah identifikasi akun pegawai Anda.</p>

                                @if ($errors->updatePhoto->any())
                                    <div class="alert alert-light-danger color-danger mb-3">
                                        <i class="bi bi-exclamation-circle me-1"></i>{{ $errors->updatePhoto->first() }}
                                    </div>
                                @endif

                                <div class="row align-items-center g-4 mb-4">
                                    <div class="col-auto">
                                        <div class="avatar pegawai-avatar-lg {{ $profilePhotoUrl ? '' : 'bg-light-primary' }} border">
                                            @if ($profilePhotoUrl)
                                                <img src="{{ $profilePhotoUrl }}" alt="Foto profil {{ $pegawaiUser->name }}">
                                            @else
                                                <span class="avatar-content">{{ $pegawaiInitials }}</span>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="col">
                                        <h6 class="mb-1 text-dark fw-bold">Pratinjau Foto Saat Ini</h6>
                                        <small class="text-muted d-block">{{ $pegawaiUser->hasProfilePhoto() ? 'Foto profil kustom aktif.' : 'Menggunakan avatar inisial nama.' }}</small>
                                    </div>
                                </div>

                                <form action="{{ route('pegawai.profile.update') }}" method="POST" enctype="multipart/form-data">
                                    @csrf
                                    @method('PATCH')
                                    <div class="form-group mb-3">
                                        <label for="profile_photo" class="form-label fw-semibold">Pilih Foto Profil Baru</label>
                                        <input type="file" id="profile_photo" name="profile_photo" class="form-control @error('profile_photo', 'updatePhoto') is-invalid @enderror" accept=".jpg,.jpeg,.jfif,.png,.webp">
                                        <small class="text-muted d-block mt-1">Format gambar: JPG, PNG, WEBP, atau JFIF (Maksimal 2 MB).</small>
                                        @error('profile_photo', 'updatePhoto')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="d-flex align-items-center gap-2 pt-2">
                                        <button type="submit" class="btn btn-primary icon icon-left">
                                            <i class="bi bi-upload"></i><span>Simpan Foto Profil</span>
                                        </button>
                                        @if ($pegawaiUser->hasProfilePhoto())
                                            <button type="submit" name="remove_profile_photo" value="1" class="btn btn-light-danger icon icon-left">
                                                <i class="bi bi-trash"></i><span>Hapus Foto</span>
                                            </button>
                                        @endif
                                    </div>
                                </form>
                            </div>

                            <!-- Tab 3: Tanda Tangan Digital -->
                            <div class="tab-pane fade" id="signature-pane" role="tabpanel" aria-labelledby="signature-tab">
                                <h5 class="fw-bold mb-3 text-dark"><i class="bi bi-pen text-primary me-2"></i>Tanda Tangan Digital</h5>
                                <p class="text-muted small mb-4">Tanda tangan digital ini akan digunakan secara otomatis pada dokumen/surat peminjaman Anda.</p>

                                @if ($errors->updateSignature->any())
                                    <div class="alert alert-light-danger color-danger mb-3">
                                        <i class="bi bi-exclamation-circle me-1"></i>{{ $errors->updateSignature->first() }}
                                    </div>
                                @endif

                                <div class="row align-items-center g-4 mb-4">
                                    <div class="col-auto">
                                        <div class="border rounded-3 p-3 bg-light d-inline-flex align-items-center justify-content-center shadow-sm" style="min-width: 180px; min-height: 90px; max-width: 240px;">
                                            @if ($signatureUrl)
                                                <img src="{{ $signatureUrl }}" alt="Tanda tangan {{ $pegawaiUser->name }}" style="max-width: 200px; max-height: 75px; width: auto; height: auto;">
                                            @else
                                                <span class="text-muted small">Belum ada tanda tangan</span>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="col">
                                        <h6 class="mb-1 text-dark fw-bold">Pratinjau Tanda Tangan Saat Ini</h6>
                                        <small class="text-muted d-block">{{ $pegawaiUser->hasSignature() ? 'Tanda tangan digital aktif.' : 'Tanda tangan belum diunggah.' }}</small>
                                    </div>
                                </div>

                                <form action="{{ route('pegawai.profile.update') }}" method="POST" enctype="multipart/form-data">
                                    @csrf
                                    @method('PATCH')
                                    <div class="form-group mb-3">
                                        <label for="signature_file" class="form-label fw-semibold">Pilih File Tanda Tangan Baru</label>
                                        <input type="file" id="signature_file" name="signature_file" class="form-control @error('signature_file', 'updateSignature') is-invalid @enderror" accept=".jpg,.jpeg,.jfif,.png,.webp">
                                        <small class="text-muted d-block mt-1">Format gambar: PNG Transparan disarankan (Maksimal 2 MB).</small>
                                        @error('signature_file', 'updateSignature')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="d-flex align-items-center gap-2 pt-2">
                                        <button type="submit" class="btn btn-primary icon icon-left">
                                            <i class="bi bi-pen"></i><span>Simpan Tanda Tangan</span>
                                        </button>
                                        @if ($pegawaiUser->hasSignature())
                                            <button type="submit" name="remove_signature" value="1" class="btn btn-light-danger icon icon-left">
                                                <i class="bi bi-trash"></i><span>Hapus TTD</span>
                                            </button>
                                        @endif
                                    </div>
                                </form>
                            </div>

                            <!-- Tab 4: Ubah Password -->
                            <div class="tab-pane fade" id="password-pane" role="tabpanel" aria-labelledby="password-tab">
                                <h5 class="fw-bold mb-3 text-dark"><i class="bi bi-shield-lock text-primary me-2"></i>Keamanan & Ubah Password</h5>
                                <p class="text-muted small mb-4">Pastikan Anda menggunakan password yang kuat dan tidak membagikannya ke orang lain.</p>

                                @if ($errors->updatePassword->any())
                                    <div class="alert alert-light-danger color-danger mb-3">
                                        <i class="bi bi-exclamation-circle me-1"></i>{{ $errors->updatePassword->first() }}
                                    </div>
                                @endif

                                <form action="{{ route('pegawai.profile.password.update') }}" method="POST">
                                    @csrf
                                    @method('PUT')
                                    <div class="form-group mb-3">
                                        <label for="current_password" class="form-label fw-semibold">Password Saat Ini</label>
                                        <input type="password" id="current_password" name="current_password" class="form-control @error('current_password', 'updatePassword') is-invalid @enderror" autocomplete="current-password">
                                        @error('current_password', 'updatePassword')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6 col-12 mb-3">
                                            <div class="form-group">
                                                <label for="password" class="form-label fw-semibold">Password Baru</label>
                                                <input type="password" id="password" name="password" class="form-control @error('password', 'updatePassword') is-invalid @enderror" autocomplete="new-password">
                                                @error('password', 'updatePassword')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>
                                        <div class="col-md-6 col-12 mb-3">
                                            <div class="form-group">
                                                <label for="password_confirmation" class="form-label fw-semibold">Konfirmasi Password Baru</label>
                                                <input type="password" id="password_confirmation" name="password_confirmation" class="form-control" autocomplete="new-password">
                                            </div>
                                        </div>
                                    </div>
                                    <button type="submit" class="btn btn-primary icon icon-left mt-2">
                                        <i class="bi bi-shield-lock"></i><span>Simpan Password Baru</span>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            @if ($errors->updatePhoto->any())
                const photoTab = document.getElementById('photo-tab');
                if (photoTab) {
                    new bootstrap.Tab(photoTab).show();
                }
            @elseif ($errors->updateSignature->any())
                const signatureTab = document.getElementById('signature-tab');
                if (signatureTab) {
                    new bootstrap.Tab(signatureTab).show();
                }
            @elseif ($errors->updatePassword->any())
                const passwordTab = document.getElementById('password-tab');
                if (passwordTab) {
                    new bootstrap.Tab(passwordTab).show();
                }
            @endif
        });
    </script>
@endpush
