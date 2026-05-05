@php
    $user = $pageUser ?? auth()->user();
    $roleLabel = $user?->role ? ucfirst($user->role) : 'Administrator';
    $profileRouteName = $profileRoute ?? match ($user?->role) {
        'pegawai' => 'pegawai.profile.index',
        default => null,
    };
    $hasProfileRoute = !empty($profileRouteName) && Route::has($profileRouteName);
    $profileUrl = $hasProfileRoute ? route($profileRouteName) : null;
    $profilePhotoUrl = $user?->profilePhotoUrl();
    $avatarInitials = $user?->name ? $user->initials() : 'AD';
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
                        <a class="nav-link position-relative" href="#" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-bell fs-5 text-gray-600"></i>
                            @if (($navbarUnreadNotificationCount ?? 0) > 0)
                                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                    {{ $navbarUnreadNotificationCount > 9 ? '9+' : $navbarUnreadNotificationCount }}
                                </span>
                            @endif
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end notification-dropdown-menu">
                            <li class="px-3 pt-2 pb-1 d-flex justify-content-between align-items-center gap-3">
                                <div>
                                    <h6 class="dropdown-header px-0 mb-0">Notifikasi</h6>
                                    <small class="text-muted">{{ $navbarUnreadNotificationCount ?? 0 }} belum dibaca</small>
                                </div>
                                @if (!empty($notificationMarkAllUrl) && ($navbarUnreadNotificationCount ?? 0) > 0)
                                    <form method="POST" action="{{ $notificationMarkAllUrl }}">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="btn btn-sm btn-light-primary">Tandai dibaca</button>
                                    </form>
                                @endif
                            </li>

                            @forelse (($navbarNotifications ?? collect()) as $notification)
                                @php
                                    $variant = $notification->data['variant'] ?? 'primary';
                                    $icon = $notification->data['icon'] ?? 'bell';
                                    $isUnread = is_null($notification->read_at);
                                @endphp
                                <li>
                                    @if (!empty($notificationShowRouteName))
                                        <a class="dropdown-item notification-dropdown-item {{ $isUnread ? 'notification-unread' : '' }}" href="{{ route($notificationShowRouteName, $notification) }}">
                                            <div class="d-flex align-items-start gap-3">
                                                <div class="avatar avatar-md notification-icon bg-light-{{ $variant }}">
                                                    <span class="avatar-content">
                                                        <i class="bi bi-{{ $icon }}"></i>
                                                    </span>
                                                </div>
                                                <div class="flex-grow-1">
                                                    <div class="d-flex justify-content-between align-items-start gap-2">
                                                        <strong class="d-block">{{ $notification->data['title'] ?? 'Notifikasi baru' }}</strong>
                                                        @if ($isUnread)
                                                            <span class="badge bg-primary">Baru</span>
                                                        @endif
                                                    </div>
                                                    <small class="d-block text-muted">{{ $notification->data['message'] ?? '-' }}</small>
                                                    <small class="d-block text-muted mt-1">{{ $notification->created_at->diffForHumans() }}</small>
                                                </div>
                                            </div>
                                        </a>
                                    @else
                                        <div class="dropdown-item">
                                            <div class="d-flex align-items-start gap-3">
                                                <div class="avatar avatar-md notification-icon bg-light-{{ $variant }}">
                                                    <span class="avatar-content">
                                                        <i class="bi bi-{{ $icon }}"></i>
                                                    </span>
                                                </div>
                                                <div class="flex-grow-1">
                                                    <strong class="d-block">{{ $notification->data['title'] ?? 'Notifikasi baru' }}</strong>
                                                    <small class="d-block text-muted">{{ $notification->data['message'] ?? '-' }}</small>
                                                </div>
                                            </div>
                                        </div>
                                    @endif
                                </li>
                            @empty
                                <li><span class="dropdown-item text-muted">Belum ada notifikasi</span></li>
                            @endforelse

                            @if (!empty($notificationIndexUrl))
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item text-center text-primary fw-semibold" href="{{ $notificationIndexUrl }}">Lihat semua notifikasi</a></li>
                            @endif
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
                                <div class="avatar avatar-md bg-light-primary">
                                    @if ($profilePhotoUrl)
                                        <img src="{{ $profilePhotoUrl }}" alt="Foto profil {{ $user?->name }}">
                                    @else
                                        <span class="avatar-content">{{ $avatarInitials }}</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li>
                            <h6 class="dropdown-header">Halo, {{ $user?->name ?? 'Admin' }}!</h6>
                        </li>
                        @if ($hasProfileRoute)
                            <li><a class="dropdown-item" href="{{ $profileUrl }}"><i class="icon-mid bi bi-person me-2"></i> Profil</a></li>
                        @endif
                        <li>
                            <hr class="dropdown-divider">
                        </li>
                        <li>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="dropdown-item">
                                    <i class="icon-mid bi bi-box-arrow-right me-2"></i> Keluar
                                </button>
                            </form>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>
</header>
