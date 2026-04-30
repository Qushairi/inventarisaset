@php
    $user = auth()->user();
@endphp

<header class="topbar-shell mb-3">
    <nav class="navbar navbar-light">
        <div class="container-fluid px-0">
            <div class="d-flex align-items-center gap-3">
                <a href="#" class="burger-btn d-block topbar-burger">
                    <i class="bi bi-justify fs-3"></i>
                </a>

                <div class="topbar-heading d-none d-md-block">
                    <h6 class="mb-0">Dashboard</h6>
                    <small class="text-muted">Ringkasan inventaris aset</small>
                </div>
            </div>

            <div class="d-flex align-items-center gap-2 gap-md-3 ms-auto">
                <div class="dropdown">
                    <a class="topbar-icon" href="#" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Pesan">
                        <i class="bi bi-envelope fs-5"></i>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><h6 class="dropdown-header">Pesan</h6></li>
                        <li><a class="dropdown-item" href="#">Belum ada pesan baru</a></li>
                    </ul>
                </div>

                <div class="dropdown">
                    <a class="topbar-icon" href="#" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Notifikasi">
                        <i class="bi bi-bell fs-5"></i>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><h6 class="dropdown-header">Notifikasi</h6></li>
                        <li><a class="dropdown-item" href="#">Belum ada notifikasi</a></li>
                    </ul>
                </div>

                <div class="dropdown">
                    <a href="#" class="topbar-user" data-bs-toggle="dropdown" aria-expanded="false">
                        <div class="user-meta text-end">
                            <strong>{{ $user?->name ?? 'Admin Inventaris' }}</strong>
                            <small>{{ $user ? 'Pengguna aktif' : 'Administrator' }}</small>
                        </div>
                        <div class="avatar avatar-md">
                            <img src="{{ asset('assets/images/faces/1.jpg') }}" alt="User Avatar">
                        </div>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><h6 class="dropdown-header">Halo, {{ $user?->name ?? 'Admin' }}!</h6></li>
                        <li><a class="dropdown-item" href="#"><i class="icon-mid bi bi-person me-2"></i> Profil</a></li>
                        <li><a class="dropdown-item" href="#"><i class="icon-mid bi bi-gear me-2"></i> Pengaturan</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="#"><i class="icon-mid bi bi-box-arrow-right me-2"></i> Keluar</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>
</header>
