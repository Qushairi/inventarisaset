<x-guest-layout title="Login">
    <x-slot:visual>
        <div class="auth-hero">
            <span class="auth-hero-kicker">Portal Inventaris Aset</span>
            <h2 class="auth-hero-title">Akses inventaris dinas dalam satu ruang kerja yang lebih rapi.</h2>
            <p class="auth-hero-text">
                Pantau peminjaman, pengembalian, dan status aset dengan akses yang dikendalikan langsung oleh admin.
            </p>

            <div class="auth-hero-grid">
                <div class="auth-hero-card">
                    <div class="auth-hero-icon">
                        <i class="bi bi-shield-check"></i>
                    </div>
                    <div>
                        <h6>Login sederhana</h6>
                        <p>Masuk cukup dengan email dan password yang sudah didaftarkan.</p>
                    </div>
                </div>
                <div class="auth-hero-card">
                    <div class="auth-hero-icon">
                        <i class="bi bi-people"></i>
                    </div>
                    <div>
                        <h6>Akun terkontrol</h6>
                        <p>Penambahan akun pegawai dilakukan dari panel admin, bukan registrasi publik.</p>
                    </div>
                </div>
                <div class="auth-hero-card">
                    <div class="auth-hero-icon">
                        <i class="bi bi-key"></i>
                    </div>
                    <div>
                        <h6>Password mandiri</h6>
                        <p>Setelah masuk, setiap akun bisa mengganti password dari halaman profil masing-masing.</p>
                    </div>
                </div>
            </div>

            <div class="auth-hero-note">
                <i class="bi bi-info-circle"></i>
                <div>
                    <strong>Akses aman dan tertib</strong>
                    <span>Sistem ini ditata untuk membatasi pembuatan akun dari sisi admin agar data inventaris tetap terjaga.</span>
                </div>
            </div>
        </div>
    </x-slot:visual>

    <div class="auth-shell">
        <div class="auth-brand">
            <div class="auth-brand-mark">
                <img src="{{ asset('assets/images/logo/logobengkalis.png') }}" alt="Logo Inventaris Aset">
            </div>
            <div>
                <span class="auth-brand-kicker">Sistem Inventaris</span>
                <h1 class="auth-title">Masuk ke akun Anda</h1>
                <p class="auth-subtitle">
                    Gunakan email dan password yang sudah didaftarkan. Pembuatan akun pegawai hanya melalui admin.
                </p>
            </div>
        </div>

        <x-auth-session-status :status="session('status')" class="mb-4" />

        @if ($errors->any())
            <div class="alert alert-danger alert-dismissible show fade">
                <div class="fw-semibold mb-1">Login gagal.</div>
                <ul class="mb-0 ps-3">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <div class="auth-panel">
            <form method="POST" action="{{ route('login') }}" class="auth-form">
                @csrf

                <div class="auth-field">
                    <x-input-label for="email" value="Email" class="form-label auth-label" />
                    <div class="auth-input-wrap">
                        <span class="auth-input-icon">
                            <i class="bi bi-envelope"></i>
                        </span>
                        <x-text-input
                            id="email"
                            type="email"
                            name="email"
                            class="form-control auth-input @error('email') is-invalid @enderror"
                            placeholder="nama@instansi.go.id"
                            value="{{ old('email') }}"
                            required
                            autofocus
                            autocomplete="username"
                        />
                    </div>
                    <x-input-error :messages="$errors->get('email')" />
                </div>

                <div class="auth-field">
                    <x-input-label for="password" value="Password" class="form-label auth-label" />
                    <div class="auth-input-wrap">
                        <span class="auth-input-icon">
                            <i class="bi bi-shield-lock"></i>
                        </span>
                        <x-text-input
                            id="password"
                            type="password"
                            name="password"
                            class="form-control auth-input @error('password') is-invalid @enderror"
                            placeholder="Masukkan password"
                            required
                            autocomplete="current-password"
                        />
                        <button type="button" class="auth-toggle-password" data-password-toggle aria-label="Tampilkan password">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                    <x-input-error :messages="$errors->get('password')" />
                </div>

                <div class="form-check form-check-lg d-flex align-items-center mb-4">
                    <input class="form-check-input me-2" type="checkbox" id="remember" name="remember">
                    <label class="form-check-label text-gray-600" for="remember">
                        Ingat saya
                    </label>
                </div>

                <x-primary-button class="btn auth-submit w-100">Masuk ke dashboard</x-primary-button>
            </form>

            <div class="auth-help">
                <div class="auth-inline-note">
                    <i class="bi bi-chat-square-dots"></i>
                    <div>
                        Butuh akun baru atau perlu bantuan akses? Hubungi admin inventaris. Password akun bisa diganti kembali dari halaman profil setelah login.
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const toggleButton = document.querySelector('[data-password-toggle]');
                const passwordInput = document.getElementById('password');

                if (!toggleButton || !passwordInput) {
                    return;
                }

                toggleButton.addEventListener('click', function () {
                    const isHidden = passwordInput.type === 'password';

                    passwordInput.type = isHidden ? 'text' : 'password';
                    toggleButton.setAttribute('aria-label', isHidden ? 'Sembunyikan password' : 'Tampilkan password');
                    toggleButton.innerHTML = isHidden
                        ? '<i class="bi bi-eye-slash"></i>'
                        : '<i class="bi bi-eye"></i>';
                });
            });
        </script>
    @endpush
</x-guest-layout>
