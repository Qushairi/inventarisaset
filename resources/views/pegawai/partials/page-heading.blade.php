<div class="pegawai-page-heading">
    <h3 class="mb-1">{{ $title }}</h3>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item">
                <a href="{{ route($homeRoute ?? 'pegawai.dashboard') }}">{{ $homeLabel ?? 'Home' }}</a>
            </li>
            <li class="breadcrumb-item active" aria-current="page">{{ $breadcrumb ?? $title }}</li>
        </ol>
    </nav>
</div>
