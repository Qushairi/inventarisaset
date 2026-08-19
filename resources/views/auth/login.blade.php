<x-guest-layout title="Login">
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
                <h1 class="login-title">Login</h1>
                <p class="login-subtitle">Inventaris Aset Dinas Pendidikan</p>
            </div>

            <x-auth-session-status :status="session('status')" class="mb-4" />

            @if ($errors->any())
                <div class="alert alert-danger mb-4">
                    <i class="bi bi-exclamation-circle me-2"></i>
                    @if ($errors->has('email'))
                        {{ $errors->first('email') }}
                    @elseif ($errors->has('password'))
                        {{ $errors->first('password') }}
                    @else
                        {{ $errors->first() }}
                    @endif
                </div>
            @endif

            <form action="{{ route('login') }}" method="POST">
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
                            autocomplete="username"
                        >
                    </div>
                    @error('email')
                        <div class="invalid-feedback d-block mt-1">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-group mb-4">
                    <label for="password" class="form-label">Password</label>
                    <div class="login-input-wrap">
                        <span class="login-input-icon" aria-hidden="true">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V7.875a4.5 4.5 0 1 0-9 0V10.5m-.75 0h10.5A2.25 2.25 0 0 1 19.5 12.75v6A2.25 2.25 0 0 1 17.25 21h-10.5A2.25 2.25 0 0 1 4.5 18.75v-6A2.25 2.25 0 0 1 6.75 10.5Z"/>
                            </svg>
                        </span>
                        <input
                            id="password"
                            type="password"
                            name="password"
                            class="form-control @error('password') is-invalid @enderror"
                            placeholder="Masukkan password"
                            required
                            autocomplete="current-password"
                        >
                        <button type="button" class="login-password-toggle" id="togglePassword" aria-label="Tampilkan password">
                            <svg data-eye="show" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5.25 12 5.25c4.478 0 8.268 2.693 9.542 6.75-1.274 4.057-5.064 6.75-9.542 6.75-4.477 0-8.268-2.693-9.542-6.75Z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 15.75a3.75 3.75 0 1 0 0-7.5 3.75 3.75 0 0 0 0 7.5Z"/>
                            </svg>
                            <svg data-eye="hide" class="d-none" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m3 3 18 18"/>
                                <path stroke-linecap="round" stroke-linejoin="round" d="M10.58 10.58a2 2 0 0 0 2.83 2.83"/>
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9.88 5.09A10.92 10.92 0 0 1 12 4.875c4.478 0 8.268 2.693 9.542 6.75a11.02 11.02 0 0 1-4.176 5.94M6.228 6.228A11.01 11.01 0 0 0 2.458 12c1.274 4.057 5.065 6.75 9.542 6.75a10.96 10.96 0 0 0 5.227-1.32"/>
                            </svg>
                        </button>
                    </div>
                    @error('password')
                        <div class="invalid-feedback d-block mt-1">{{ $message }}</div>
                    @enderror
                </div>

                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-4">
                    <div class="form-check mb-0">
                        <input class="form-check-input" type="checkbox" value="1" id="remember" name="remember" @checked(old('remember'))>
                        <label class="form-check-label text-muted" for="remember">Ingat saya</label>
                    </div>
                    <a href="{{ route('password.request') }}" class="login-link">Lupa password?</a>
                </div>

                <button type="submit" class="btn btn-primary btn-block btn-lg shadow-lg login-submit">
                    Login
                </button>
            </form>

            <p class="login-footer-note mt-4">&copy; {{ now()->year }} Sistem Inventaris Aset - Dinas Pendidikan Kabupaten Bengkalis</p>
        </div>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const toggleButton = document.getElementById('togglePassword');
                const passwordInput = document.getElementById('password');

                if (!toggleButton || !passwordInput) {
                    return;
                }

                const showIcon = toggleButton.querySelector('[data-eye="show"]');
                const hideIcon = toggleButton.querySelector('[data-eye="hide"]');

                toggleButton.addEventListener('click', function () {
                    const isHidden = passwordInput.type === 'password';

                    passwordInput.type = isHidden ? 'text' : 'password';
                    toggleButton.setAttribute('aria-label', isHidden ? 'Sembunyikan password' : 'Tampilkan password');
                    showIcon.classList.toggle('d-none', isHidden);
                    hideIcon.classList.toggle('d-none', !isHidden);
                });
            });
        </script>
    @endpush
</x-guest-layout>
