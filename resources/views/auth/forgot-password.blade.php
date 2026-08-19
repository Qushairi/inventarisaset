<x-guest-layout title="Lupa Password">
    <div class="card login-card">
        <div class="card-body">
            <div class="login-brand">
                <img src="{{ asset('images/logo-bengkalis.png') }}" alt="Logo Dinas Pendidikan Kabupaten Bengkalis">
                <div>
                    <div class="login-brand-primary">Dinas Pendidikan</div>
                    <div class="login-brand-secondary">Kabupaten Bengkalis</div>
                </div>
            </div>

            <hr class="login-divider">

            <div class="mb-4 text-center text-md-start">
                <h1 class="login-title">Lupa Password</h1>
                <p class="login-subtitle">Masukkan email akun Anda. Kami akan mengirimkan tautan untuk mengatur ulang password.</p>
            </div>

            <x-auth-session-status :status="session('status')" class="mb-4" />

            <form action="{{ route('password.email') }}" method="POST">
                @csrf

                <div class="form-group mb-4">
                    <label for="email" class="form-label">Email</label>
                    <div class="login-input-wrap">
                        <span class="login-input-icon" aria-hidden="true">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 7.5 10.94 13a2 2 0 0 0 2.12 0L21 7.5M5 19h14a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2Z"/>
                            </svg>
                        </span>
                        <input
                            id="email"
                            type="email"
                            name="email"
                            value="{{ old('email') }}"
                            class="form-control @error('email') is-invalid @enderror"
                            placeholder="nama@instansi.go.id"
                            required
                            autofocus
                            autocomplete="email"
                            @error('email') aria-invalid="true" aria-describedby="forgot-email-error" @enderror
                        >
                    </div>
                    @error('email')
                        <div id="forgot-email-error" class="invalid-feedback d-block" role="alert">{{ $message }}</div>
                    @enderror
                </div>

                <button type="submit" class="btn btn-primary btn-block btn-lg shadow-lg login-submit">
                    Kirim Tautan Reset
                </button>
            </form>

            <div class="mt-4 text-center">
                <a href="{{ route('login') }}" class="login-link">
                    <i class="bi bi-arrow-left me-1" aria-hidden="true"></i>Kembali ke Login
                </a>
            </div>

            <p class="login-footer-note mt-4">&copy; {{ now()->year }} Sistem Inventaris Aset - Dinas Pendidikan Kabupaten Bengkalis</p>
        </div>
    </div>
</x-guest-layout>
