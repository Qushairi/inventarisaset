@php
    $sidebarMenus = [
        [
            'title' => true,
            'name' => 'Menu Pegawai',
        ],
        [
            'name' => 'Dashboard',
            'icon' => 'grid-fill',
            'route' => 'pegawai.dashboard',
            'active_patterns' => ['pegawai.dashboard'],
        ],
        [
            'name' => 'Data Aset',
            'icon' => 'box-seam-fill',
            'route' => 'pegawai.assets.index',
            'active_patterns' => ['pegawai.assets.*'],
        ],
        [
            'name' => 'Peminjaman',
            'icon' => 'journal-check',
            'route' => 'pegawai.loans.index',
            'active_patterns' => ['pegawai.loans.*'],
        ],
        [
            'name' => 'Pengembalian',
            'icon' => 'arrow-counterclockwise',
            'route' => 'pegawai.returns.index',
            'active_patterns' => ['pegawai.returns.*'],
        ],
    ];
@endphp

<div class="sidebar-wrapper active">
    <div class="sidebar-header">
        <div class="d-flex justify-content-between align-items-start">
            <div class="w-100 text-center pe-4">
                <a href="{{ route('pegawai.dashboard') }}" class="d-inline-block mb-3">
                    <img src="{{ asset('assets/images/logo/logobengkalis.png') }}" alt="Logo Inventaris Aset" style="height: 72px; width: auto;">
                </a>
                <h6 class="mb-1">Inventaris Aset</h6>
                <p class="mb-0 text-sm text-muted">Pegawai Panel</p>
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
                        <div class="d-inline-flex align-items-center justify-content-center flex-shrink-0" style="width: 1.25rem; height: 1.25rem;">
                            <i class="bi bi-{{ $menu['icon'] }}"></i>
                        </div>
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
