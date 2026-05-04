@php
    $sidebarMenus = $sidebarMenus ?? [
        [
            'title' => true,
            'name' => 'Menu Admin',
        ],
        [
            'name' => 'Dashboard',
            'icon' => 'grid-fill',
            'route' => 'admin.dashboard',
            'active_patterns' => ['admin.dashboard'],
        ],
        [
            'name' => 'Kategori',
            'icon' => 'tags-fill',
            'route' => 'admin.categories.index',
            'active_patterns' => ['admin.categories.*'],
        ],
        [
            'name' => 'Data Lokasi',
            'icon' => 'geo-alt-fill',
            'route' => 'admin.locations.index',
            'active_patterns' => ['admin.locations.*'],
        ],
        [
            'name' => 'Data Aset',
            'icon' => 'box-seam',
            'route' => 'admin.assets.index',
            'active_patterns' => ['admin.assets.*'],
        ],
        [
            'name' => 'Data Pegawai',
            'icon' => 'people-fill',
            'route' => 'admin.employees.index',
            'active_patterns' => ['admin.employees.*'],
        ],
        [
            'name' => 'Peminjaman',
            'icon' => 'journal-check',
            'route' => 'admin.loans.index',
            'active_patterns' => ['admin.loans.*'],
        ],
        [
            'name' => 'Pengembalian',
            'icon' => 'arrow-counterclockwise',
            'route' => 'admin.returns.index',
            'active_patterns' => ['admin.returns.*'],
        ],
        [
            'name' => 'Laporan',
            'icon' => 'bar-chart-fill',
            'route' => 'admin.reports.index',
            'active_patterns' => ['admin.reports.*'],
        ],
    ];
@endphp

<div class="sidebar-wrapper active">
    <div class="sidebar-header">
        <div class="d-flex justify-content-between align-items-start">
            <div class="w-100 text-center pe-4">
                <a href="{{ route('admin.dashboard') }}" class="d-inline-block mb-3">
                    <img src="{{ asset('assets/images/logo/logobengkalis.png') }}" alt="Logo Inventaris Aset" style="height: 72px; width: auto;">
                </a>
                <h6 class="mb-1">Inventaris Aset</h6>
                <p class="mb-0 text-sm text-muted">Administrator Panel</p>
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
                    $hasChildren = !empty($menu['children']);
                    $menuPatterns = $menu['active_patterns'] ?? [];
                    $isActive = !empty($menu['route']) && request()->routeIs($menu['route']);

                    foreach ($menuPatterns as $pattern) {
                        if (request()->routeIs($pattern)) {
                            $isActive = true;
                            break;
                        }
                    }

                    if ($hasChildren) {
                        foreach ($menu['children'] as $child) {
                            $childPatterns = $child['active_patterns'] ?? [];

                            if (!empty($child['route']) && request()->routeIs($child['route'])) {
                                $isActive = true;
                                break;
                            }

                            foreach ($childPatterns as $pattern) {
                                if (request()->routeIs($pattern)) {
                                    $isActive = true;
                                    break 2;
                                }
                            }
                        }
                    }

                    $menuLink = !empty($menu['route']) && Route::has($menu['route'])
                        ? route($menu['route'])
                        : ($menu['url'] ?? 'javascript:void(0)');
                @endphp

                <li class="sidebar-item {{ $isActive ? 'active' : '' }} {{ $hasChildren ? 'has-sub' : '' }}">
                    <a href="{{ $hasChildren ? '#' : $menuLink }}" class="sidebar-link">
                        <div class="d-inline-flex align-items-center justify-content-center flex-shrink-0" style="width: 1.25rem; height: 1.25rem;">
                            <i class="bi bi-{{ $menu['icon'] }}"></i>
                        </div>
                        <span>{{ $menu['name'] }}</span>
                    </a>

                    @if ($hasChildren)
                        <ul class="submenu {{ $isActive ? 'active' : '' }}">
                            @foreach ($menu['children'] as $child)
                                @php
                                    $childPatterns = $child['active_patterns'] ?? [];
                                    $isChildActive = !empty($child['route']) && request()->routeIs($child['route']);

                                    foreach ($childPatterns as $pattern) {
                                        if (request()->routeIs($pattern)) {
                                            $isChildActive = true;
                                            break;
                                        }
                                    }

                                    $childLink = !empty($child['route']) && Route::has($child['route'])
                                        ? route($child['route'])
                                        : ($child['url'] ?? 'javascript:void(0)');
                                @endphp

                                <li class="submenu-item {{ $isChildActive ? 'active' : '' }}">
                                    <a href="{{ $childLink }}">{{ $child['name'] }}</a>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </li>
            @endforeach
        </ul>
    </div>

    <button class="sidebar-toggler btn x">
        <i data-feather="x"></i>
    </button>
</div>
