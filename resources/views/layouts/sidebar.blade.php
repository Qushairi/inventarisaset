@php
    $sidebarMenus = $sidebarMenus ?? [
        [
            'title' => true,
            'name' => 'Menu Utama',
        ],
        [
            'name' => 'Dashboard',
            'icon' => 'grid-fill',
            'route' => 'dashboard',
        ],
        [
            'name' => 'Data Master',
            'icon' => 'archive-fill',
            'children' => [
                ['name' => 'Barang', 'url' => '#', 'active_patterns' => ['barang.*']],
                ['name' => 'Kategori', 'url' => '#', 'active_patterns' => ['kategori.*']],
                ['name' => 'Ruangan', 'url' => '#', 'active_patterns' => ['ruangan.*']],
            ],
        ],
        [
            'name' => 'Transaksi',
            'icon' => 'clipboard-check-fill',
            'children' => [
                ['name' => 'Peminjaman', 'url' => '#', 'active_patterns' => ['peminjaman.*']],
                ['name' => 'Pengembalian', 'url' => '#', 'active_patterns' => ['pengembalian.*']],
            ],
        ],
        [
            'name' => 'Laporan',
            'icon' => 'bar-chart-fill',
            'url' => '#',
            'active_patterns' => ['laporan.*'],
        ],
    ];
@endphp

<div class="sidebar-wrapper active">
    <div class="sidebar-header">
        <div class="d-flex justify-content-between">
            <div class="logo sidebar-brand-wrap">
                <a href="{{ route('dashboard') }}" class="sidebar-brand">
                    <span class="sidebar-brand-mark">
                        <i class="bi bi-box-seam"></i>
                    </span>
                    <span class="sidebar-brand-copy">
                        <strong>Inventaris</strong>
                        <small>Aset Sekolah</small>
                    </span>
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
                        <i class="bi bi-{{ $menu['icon'] }}"></i>
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
