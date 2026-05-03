@php
    $user = auth()->user();
    $roleLabel = $user?->role ? ucfirst($user->role) : 'Administrator';
@endphp

<header class="mb-3">
    <nav class="navbar navbar-expand navbar-light navbar-header">
        <div class="container-fluid">
            <a href="#" class="burger-btn d-block">
                <i class="bi bi-justify fs-3"></i>
            </a>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarSupportedContent">
                <ul class="navbar-nav ms-auto mb-2 mb-lg-0 align-items-center">
                    <li class="nav-item dropdown nav-icon me-2">
                        <a class="nav-link" href="#" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-envelope fs-5 text-gray-600"></i>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li>
                                <h6 class="dropdown-header">Pesan</h6>
                            </li>
                            <li><a class="dropdown-item" href="#">Belum ada pesan baru</a></li>
                        </ul>
                    </li>
                    <li class="nav-item dropdown nav-icon me-3">
                        <a class="nav-link" href="#" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-bell fs-5 text-gray-600"></i>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li>
                                <h6 class="dropdown-header">Notifikasi</h6>
                            </li>
                            <li><a class="dropdown-item" href="#">Belum ada notifikasi</a></li>
                        </ul>
                    </li>
                </ul>
                <div class="dropdown">
                    <a href="#" data-bs-toggle="dropdown" aria-expanded="false">
                        <div class="user-menu d-flex align-items-center">
                            <div class="user-name text-end me-3 d-none d-sm-block">
                                <h6 class="mb-0 text-gray-600">{{ $user?->name ?? 'Admin Inventaris' }}</h6>
                                <p class="mb-0 text-sm text-gray-600">{{ $roleLabel }}</p>
                            </div>
                            <div class="user-img d-flex align-items-center">
                                <div class="avatar avatar-md">
                                    <img src="{{ asset('assets/images/faces/1.jpg') }}" alt="User Avatar">
                                </div>
                            </div>
                        </div>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li>
                            <h6 class="dropdown-header">Halo, {{ $user?->name ?? 'Admin' }}!</h6>
                        </li>
                        <li><a class="dropdown-item" href="#"><i class="icon-mid bi bi-person me-2"></i> Profil</a></li>
                        <li><a class="dropdown-item" href="#"><i class="icon-mid bi bi-gear me-2"></i> Pengaturan</a></li>
                        <li>
                            <hr class="dropdown-divider">
                        </li>
                        <li><a class="dropdown-item" href="#"><i class="icon-mid bi bi-box-arrow-right me-2"></i> Keluar</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>
</header>
