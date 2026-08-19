<x-guest-layout title="Reset Password">
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
                <h1 class="login-title">Reset Password</h1>
                <p class="login-subtitle">Buat password baru untuk akun Anda.</p>
            </div>

            <form action="{{ route('password.store') }}" method="POST">
                @csrf

                <input type="hidden" name="token" value="{{ $token }}">

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
                            value="{{ old('email', $email) }}"
                            class="form-control @error('email') is-invalid @enderror"
                            placeholder="nama@instansi.go.id"
                            required
                            autofocus
                            autocomplete="email"
                            @error('email') aria-invalid="true" aria-describedby="reset-email-error" @enderror
                        >
                    </div>
                    @error('email')
                        <div id="reset-email-error" class="invalid-feedback d-block" role="alert">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-group mb-4">
                    <label for="password" class="form-label">Password Baru</label>
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
                            placeholder="Masukkan password baru"
                            required
                            autocomplete="new-password"
                            @error('password') aria-invalid="true" aria-describedby="reset-password-error" @enderror
                        >
                        <button
                            type="button"
                            class="login-password-toggle"
                            data-password-toggle
                            data-target="password"
                            aria-label="Tampilkan password baru"
                            aria-controls="password"
                            aria-pressed="false"
                        >
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
                        <div id="reset-password-error" class="invalid-feedback d-block" role="alert">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-group mb-4">
                    <label for="password_confirmation" class="form-label">Konfirmasi Password Baru</label>
                    <div class="login-input-wrap">
                        <span class="login-input-icon" aria-hidden="true">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m6 2.25a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                            </svg>
                        </span>
                        <input
                            id="password_confirmation"
                            type="password"
                            name="password_confirmation"
                            class="form-control @error('password_confirmation') is-invalid @enderror"
                            placeholder="Ulangi password baru"
                            required
                            autocomplete="new-password"
                            @error('password_confirmation') aria-invalid="true" aria-describedby="reset-password-confirmation-error" @enderror
                        >
                        <button
                            type="button"
                            class="login-password-toggle"
                            data-password-toggle
                            data-target="password_confirmation"
                            aria-label="Tampilkan konfirmasi password baru"
                            aria-controls="password_confirmation"
                            aria-pressed="false"
                        >
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
                    @error('password_confirmation')
                        <div id="reset-password-confirmation-error" class="invalid-feedback d-block" role="alert">{{ $message }}</div>
                    @enderror
                </div>

                <button type="submit" class="btn btn-primary btn-block btn-lg shadow-lg login-submit">
                    Reset Password
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

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                document.querySelectorAll('[data-password-toggle]').forEach(function (toggleButton) {
                    const passwordInput = document.getElementById(toggleButton.dataset.target);

                    if (!passwordInput) {
                        return;
                    }

                    const showIcon = toggleButton.querySelector('[data-eye="show"]');
                    const hideIcon = toggleButton.querySelector('[data-eye="hide"]');
                    const visibleLabel = toggleButton.getAttribute('aria-label');
                    const hiddenLabel = visibleLabel.replace('Tampilkan', 'Sembunyikan');

                    toggleButton.addEventListener('click', function () {
                        const isHidden = passwordInput.type === 'password';

                        passwordInput.type = isHidden ? 'text' : 'password';
                        toggleButton.setAttribute('aria-label', isHidden ? hiddenLabel : visibleLabel);
                        toggleButton.setAttribute('aria-pressed', isHidden ? 'true' : 'false');
                        showIcon.classList.toggle('d-none', isHidden);
                        hideIcon.classList.toggle('d-none', !isHidden);
                    });
                });
            });
        </script>
    @endpush
</x-guest-layout>
