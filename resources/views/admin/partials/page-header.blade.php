<div class="page-title">
    <div class="row align-items-center">
        <div class="col-12 col-md-6 order-md-1 order-last mb-3 mb-md-0">
            <h3 class="mb-2">{{ $title }}</h3>
            @if (!empty($subtitle))
                <p class="text-subtitle text-muted mb-0">{{ $subtitle }}</p>
            @endif
        </div>
        <div class="col-12 col-md-6 order-md-2 order-first">
            <nav aria-label="breadcrumb" class="breadcrumb-header float-start float-lg-end mt-2 mt-md-0">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item active" aria-current="page">{{ $breadcrumb ?? $title }}</li>
                </ol>
            </nav>
        </div>
    </div>
</div>
