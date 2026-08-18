@extends('layouts.app')

@section('title', 'Log Aktivitas Pegawai')

@section('content')
    <div class="page-heading">
        @include('pegawai.partials.page-heading', [
            'title' => 'Log Aktivitas',
            'breadcrumb' => 'Log Aktivitas',
        ])
    </div>

    <div class="page-content">
        <section class="section">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-3">
                    <div class="pegawai-toolbar-wrapper ms-auto">
                        <form method="GET" action="{{ route('pegawai.activity-logs.index') }}" id="activityLogsFilterForm" class="d-flex align-items-center gap-2">
                            <!-- Sleek Search Input Box -->
                            <div class="pegawai-search-box">
                                <i class="bi bi-search pegawai-search-icon"></i>
                                <input
                                    type="search"
                                    name="search"
                                    class="form-control pegawai-search-input"
                                    placeholder="Cari aktivitas atau deskripsi..."
                                    value="{{ $search }}"
                                >
                            </div>

                            <!-- Floating Filter Dropdown Popover -->
                            <div class="dropdown">
                                <button
                                    class="btn pegawai-filter-btn d-inline-flex align-items-center gap-2 {{ $selectedEvent ? 'border-primary bg-light-primary text-primary' : '' }}"
                                    type="button"
                                    id="pegawaiLogFilterDropdownBtn"
                                    data-bs-toggle="dropdown"
                                    data-bs-auto-close="outside"
                                    aria-expanded="false"
                                    title="Filter Log Aktivitas"
                                >
                                    <i class="bi bi-funnel-fill text-primary"></i>
                                    <span class="fw-semibold">Filter</span>
                                    @if ($selectedEvent)
                                        <span class="badge bg-primary rounded-pill font-10">1</span>
                                    @endif
                                </button>

                                <div class="dropdown-menu dropdown-menu-end p-3 shadow-lg border-0 pegawai-asset-filter-menu" style="min-width: 280px;" aria-labelledby="pegawaiLogFilterDropdownBtn">
                                    <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
                                        <h6 class="mb-0 fw-bold text-dark"><i class="bi bi-sliders me-1 text-primary"></i> Filter Log Aktivitas</h6>
                                        @if ($search || $selectedEvent)
                                            <a href="{{ route('pegawai.activity-logs.index') }}" class="btn btn-sm btn-link text-danger p-0 text-decoration-none">
                                                <i class="bi bi-arrow-counterclockwise me-1"></i>Reset
                                            </a>
                                        @endif
                                    </div>

                                    <div class="row g-2">
                                        <div class="col-12">
                                            <label for="eventFilterSelect" class="form-label text-muted small font-semibold mb-1">Kategori Aktivitas</label>
                                            <select
                                                name="event"
                                                id="eventFilterSelect"
                                                class="form-select form-select-sm"
                                            >
                                                <option value="">Semua Kategori Aktivitas</option>
                                                @foreach ($eventOptions as $key => $label)
                                                    <option value="{{ $key }}" @selected($selectedEvent === $key)>{{ $label }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle table-lg mb-0">
                            <thead>
                                <tr>
                                    <th class="text-nowrap" style="min-width: 180px;">User</th>
                                    <th class="text-nowrap" style="min-width: 170px;">Aktivitas</th>
                                    <th>Detail Deskripsi</th>
                                    <th class="text-nowrap" style="min-width: 180px;">Waktu & Tanggal</th>
                                    <th class="text-nowrap pe-4 text-end" style="min-width: 130px;">IP Address</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($logs as $log)
                                    @php
                                        $user = $log->user;
                                        $photoUrl = $user?->profilePhotoUrl();
                                        $initials = $user?->initials() ?: 'PG';
                                    @endphp
                                    <tr>
                                        <!-- User -->
                                        <td class="text-nowrap">
                                            <div class="d-flex align-items-center">
                                                <div class="avatar avatar-lg me-3 flex-shrink-0 bg-light-primary">
                                                    @if ($photoUrl)
                                                        <img src="{{ $photoUrl }}" alt="{{ $user?->name }}">
                                                    @else
                                                        <span class="avatar-content font-12 font-semibold text-primary">{{ $initials }}</span>
                                                    @endif
                                                </div>
                                                <div>
                                                    <h6 class="mb-0 text-dark fw-bold font-13">{{ $user?->name ?? 'User' }}</h6>
                                                    <small class="text-muted d-block font-11">
                                                        {{ $user?->nip ? 'NIP: ' . $user->nip : ucfirst($log->role) }}
                                                    </small>
                                                </div>
                                            </div>
                                        </td>

                                        <!-- Aktivitas -->
                                        <td class="text-nowrap">
                                            <span class="badge {{ $log->event_badge }} px-2.5 py-1.5 font-12 font-semibold">
                                                {{ $log->title }}
                                            </span>
                                        </td>

                                        <!-- Detail Deskripsi -->
                                        <td>
                                            <span class="text-dark font-12 d-block lh-sm">{{ $log->description ?: '-' }}</span>
                                        </td>

                                        <!-- Waktu & Tanggal -->
                                        <td class="text-nowrap">
                                            <div class="fw-bold text-dark font-12 mb-0.5">
                                                {{ $log->created_at->translatedFormat('d F Y') }}
                                            </div>
                                            <small class="text-muted font-11 d-block">
                                                Pukul {{ $log->created_at->format('H:i:s') }} WIB
                                            </small>
                                        </td>

                                        <!-- IP Address -->
                                        <td class="text-nowrap pe-4 text-end">
                                            <span class="badge bg-light-secondary text-secondary font-11 font-mono fw-normal px-2.5 py-1.5" title="IP Address perangkat">
                                                <i class="bi bi-laptop me-1"></i>{{ $log->ip_address ?: '127.0.0.1' }}
                                            </span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center text-muted">Belum ada log aktivitas.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @include('pegawai.partials.table-footer', ['paginator' => $logs])
                </div>
            </div>
        </section>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const filterMenu = document.querySelector('.pegawai-asset-filter-menu');
            const eventFilterSelect = document.getElementById('eventFilterSelect');

            if (filterMenu) {
                ['click', 'mousedown', 'pointerdown'].forEach(function (evtType) {
                    filterMenu.addEventListener(evtType, function (e) {
                        e.stopPropagation();
                    });
                });
            }

            if (eventFilterSelect) {
                eventFilterSelect.addEventListener('change', function () {
                    this.form.submit();
                });
            }

            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.has('event') || urlParams.has('search') || urlParams.has('per_page') || urlParams.has('page')) {
                const activityLogsCard = document.getElementById('activityLogsCard');
                if (activityLogsCard) {
                    activityLogsCard.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            }
        });
    </script>
@endsection
