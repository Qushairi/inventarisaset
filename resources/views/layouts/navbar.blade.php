@php
    $user = $pageUser ?? auth()->user();
    $roleLabel = $user?->role ? ucfirst($user->role) : 'Administrator';
    $profileRouteName = $profileRoute ?? match ($user?->role) {
        'admin' => 'admin.profile.index',
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
                            <li class="px-3 py-2 bg-light border-bottom d-flex justify-content-between align-items-center gap-3">
                                <div>
                                    <h6 class="dropdown-header px-0 mb-0 font-13 fw-bold text-dark">Notifikasi</h6>
                                    <small class="text-muted font-11">{{ $navbarUnreadNotificationCount ?? 0 }} belum dibaca</small>
                                </div>
                                @if (!empty($notificationMarkAllUrl) && ($navbarUnreadNotificationCount ?? 0) > 0)
                                    <form method="POST" action="{{ $notificationMarkAllUrl }}" data-swal-confirm data-swal-icon="question" data-swal-title="Tandai notifikasi dibaca?" data-swal-text="Semua notifikasi {{ $user?->role === 'pegawai' ? 'pegawai' : 'admin' }} akan ditandai sudah dibaca." data-swal-confirm-text="Ya, tandai" data-swal-confirm-color="#435ebe">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="btn btn-sm btn-light-primary px-2 py-0.5 font-11">Tandai dibaca</button>
                                    </form>
                                @endif
                            </li>

                            <div class="notification-dropdown-body">
                                @forelse (($navbarNotifications ?? collect()) as $notification)
                                    @php
                                        $variant = $notification->data['variant'] ?? 'primary';
                                        $icon = $notification->data['icon'] ?? 'bell';
                                        $isUnread = is_null($notification->read_at);
                                    @endphp
                                    <li>
                                        @if (!empty($notificationShowRouteName))
                                            <a class="dropdown-item notification-dropdown-item border-bottom {{ $isUnread ? 'notification-unread' : '' }}" href="{{ route($notificationShowRouteName, $notification) }}">
                                                <div class="d-flex align-items-start gap-2.5">
                                                    <div class="avatar avatar-md notification-icon bg-light-{{ $variant }} flex-shrink-0">
                                                        <span class="avatar-content">
                                                            <i class="bi bi-{{ $icon }}"></i>
                                                        </span>
                                                    </div>
                                                    <div class="flex-grow-1 min-w-0">
                                                        <div class="d-flex justify-content-between align-items-start gap-1">
                                                            <strong class="d-block text-truncate font-12 text-dark mb-0.5" style="max-width: 170px;">{{ $notification->data['title'] ?? 'Notifikasi baru' }}</strong>
                                                            @if ($isUnread)
                                                                <span class="badge bg-primary font-9 px-1.5 py-0.5 flex-shrink-0">Baru</span>
                                                            @endif
                                                        </div>
                                                        <small class="d-block text-muted font-11 lh-sm text-wrap">{{ $notification->data['message'] ?? '-' }}</small>
                                                        <small class="d-block text-muted font-10 mt-1"><i class="bi bi-clock me-1"></i>{{ $notification->created_at->diffForHumans() }}</small>
                                                    </div>
                                                </div>
                                            </a>
                                        @else
                                            <div class="dropdown-item border-bottom">
                                                <div class="d-flex align-items-start gap-2.5">
                                                    <div class="avatar avatar-md notification-icon bg-light-{{ $variant }} flex-shrink-0">
                                                        <span class="avatar-content">
                                                            <i class="bi bi-{{ $icon }}"></i>
                                                        </span>
                                                    </div>
                                                    <div class="flex-grow-1 min-w-0">
                                                        <strong class="d-block text-truncate font-12 text-dark mb-0.5">{{ $notification->data['title'] ?? 'Notifikasi baru' }}</strong>
                                                        <small class="d-block text-muted font-11 lh-sm text-wrap">{{ $notification->data['message'] ?? '-' }}</small>
                                                    </div>
                                                </div>
                                            </div>
                                        @endif
                                    </li>
                                @empty
                                    <li><span class="dropdown-item text-muted text-center py-3 font-12">Belum ada notifikasi</span></li>
                                @endforelse
                            </div>

                            @if (!empty($notificationIndexUrl))
                                <li class="bg-light p-2 text-center border-top">
                                    <a class="dropdown-item text-center text-primary font-12 fw-bold py-1 text-decoration-none" href="{{ $notificationIndexUrl }}">
                                        Lihat semua notifikasi <i class="bi bi-arrow-right ms-1"></i>
                                    </a>
                                </li>
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
                            <form method="POST" action="{{ route('logout') }}" data-swal-confirm data-swal-icon="question" data-swal-title="Logout akun?" data-swal-text="Apakah Anda yakin ingin keluar dari akun {{ $user?->role === 'pegawai' ? 'pegawai' : 'admin' }}?" data-swal-confirm-text="Ya, logout" data-swal-confirm-color="#dc3545">
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
