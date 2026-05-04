<x-guest-layout title="Login">
    <div class="auth-logo">
        <a href="{{ route('login') }}">
            <img src="{{ asset('assets/images/logo/logobengkalis.png') }}" alt="Logo Inventaris Aset" style="height: 5rem; width: auto;">
        </a>
    </div>

    <h1 class="auth-title">Masuk</h1>
    <p class="auth-subtitle mb-5">Login sebagai admin atau pegawai untuk mengakses sistem inventaris aset.</p>

    @if (session('status'))
        <div class="alert alert-success alert-dismissible show fade">
            {{ session('status') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

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

    <form method="POST" action="{{ route('login') }}">
        @csrf

        <div class="form-group position-relative has-icon-left mb-4">
            <input
                id="email"
                type="email"
                name="email"
                class="form-control form-control-xl"
                placeholder="Email"
                value="{{ old('email') }}"
                required
                autofocus
                autocomplete="username"
            >
            <div class="form-control-icon">
                <i class="bi bi-envelope"></i>
            </div>
        </div>

        <div class="form-group position-relative has-icon-left mb-4">
            <input
                id="password"
                type="password"
                name="password"
                class="form-control form-control-xl"
                placeholder="Password"
                required
                autocomplete="current-password"
            >
            <div class="form-control-icon">
                <i class="bi bi-shield-lock"></i>
            </div>
        </div>

        <div class="form-check form-check-lg d-flex align-items-end mb-4">
            <input class="form-check-input me-2" type="checkbox" id="remember" name="remember">
            <label class="form-check-label text-gray-600" for="remember">
                Ingat saya
            </label>
        </div>

        <button class="btn btn-primary btn-block btn-lg shadow-lg mt-2" type="submit">Masuk</button>
    </form>

    <div class="mt-5">
        <div class="card border">
            <div class="card-body">
                <h6 class="mb-3">Akun demo</h6>
                <div class="mb-2">
                    <strong>Admin</strong><br>
                    <span class="text-muted">admin@inventarisaset.test / password</span>
                </div>
                <div>
                    <strong>Pegawai</strong><br>
                    <span class="text-muted">amien@bengkalis.go.id / password</span>
                </div>
            </div>
        </div>
    </div>
</x-guest-layout>
