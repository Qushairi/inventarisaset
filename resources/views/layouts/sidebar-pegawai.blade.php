@php
    $isFromReturns = request()->query('from') === 'returns';
    $sidebarMenus = [
        [
            'name' => 'Dashboard',
            'icon' => 'grid-fill',
            'route' => 'pegawai.dashboard',
            'active_patterns' => ['pegawai.dashboard'],
        ],
        [
            'name' => 'Data Aset',
            'icon' => 'box-seam',
            'route' => 'pegawai.assets.index',
            'active_patterns' => ['pegawai.assets.*'],
        ],
        [
            'name' => 'Peminjaman',
            'icon' => 'journal-check',
            'route' => 'pegawai.loans.index',
            'active_patterns' => $isFromReturns ? ['pegawai.loans.index', 'pegawai.loans.create'] : ['pegawai.loans.*'],
        ],
        [
            'name' => 'Pengembalian',
            'icon' => 'arrow-counterclockwise',
            'route' => 'pegawai.returns.index',
            'active_patterns' => $isFromReturns ? ['pegawai.returns.*', 'pegawai.loans.letter.*'] : ['pegawai.returns.*'],
        ],
        [
            'name' => 'Log Aktivitas',
            'icon' => 'clock-history',
            'route' => 'pegawai.activity-logs.index',
            'active_patterns' => ['pegawai.activity-logs.*'],
        ],
    ];
@endphp

<div class="sidebar-wrapper active">
    <div class="sidebar-header">
        <div class="d-flex justify-content-between">
            <div class="pegawai-sidebar-brand text-center w-100 pe-4">
                <a href="{{ route('pegawai.dashboard') }}" class="d-inline-flex flex-column align-items-center text-decoration-none">
                    <img src="{{ asset('images/logo-bengkalis.png') }}" alt="Logo Dinas Pendidikan Kabupaten Bengkalis" width="72" height="72" class="mb-3">
                    <span class="pegawai-sidebar-brand-title">Sistem Inventaris Aset</span>
                    <span class="pegawai-sidebar-brand-subtitle">Dinas Pendidikan Kabupaten Bengkalis</span>
                </a>
            </div>
            <div class="toggler">
                <a href="#" class="sidebar-hide d-xl-none d-block">
                    <i class="bi bi-x bi-middle"></i>
                </a>
            </div>
        </div>
    </div>

    <div class="sidebar-menu">
        <ul class="menu">
            @foreach ($sidebarMenus as $menu)
                @if (!empty($menu['title']))
                    <li class="sidebar-title">{{ $menu['name'] }}</li>
                    @continue
                @endif

                @php
                    $menuPatterns = $menu['active_patterns'] ?? [];
                    $isActive = !empty($menu['route']) && request()->routeIs($menu['route']);

                    foreach ($menuPatterns as $pattern) {
                        if (request()->routeIs($pattern)) {
                            $isActive = true;
                            break;
                        }
                    }
                @endphp

                <li class="sidebar-item {{ $isActive ? 'active' : '' }}">
                    <a href="{{ route($menu['route']) }}" class="sidebar-link">
                        <i class="bi bi-{{ $menu['icon'] }}"></i>
                        <span>{{ $menu['name'] }}</span>
                    </a>
                </li>
            @endforeach
        </ul>
    </div>

    <button class="sidebar-toggler btn x">
        <i data-feather="x"></i>
    </button>
</div>
