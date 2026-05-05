@extends('layouts.app')

@section('title', 'Notifikasi Pegawai')

@section('content')
    <div class="page-heading">
        @include('admin.partials.page-header', [
            'title' => 'Notifikasi Pegawai',
            'subtitle' => 'Pantau persetujuan peminjaman, verifikasi pengembalian, dan pengingat jatuh tempo.',
            'breadcrumb' => 'Notifikasi',
            'homeRoute' => 'pegawai.dashboard',
        ])
    </div>

    <div class="page-content">
        <section class="section">
            @if (session('success'))
                <div class="alert alert-light-success color-success">
                    <i class="bi bi-check-circle me-1"></i>{{ session('success') }}
                </div>
            @endif

            <div class="card pegawai-panel">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-3">
                    <div>
                        <h4 class="card-title mb-1">Pusat Notifikasi</h4>
                        <p class="mb-0 text-muted">Semua notifikasi untuk akun pegawai Anda ditampilkan di sini.</p>
                    </div>
                    <div class="d-flex align-items-center flex-wrap gap-2">
                        <span class="badge bg-light-primary">{{ $notifications->total() }} notifikasi</span>
                        <span class="badge bg-light-warning">{{ $unreadNotificationCount }} belum dibaca</span>
                        @if ($unreadNotificationCount > 0)
                            <form method="POST" action="{{ route('pegawai.notifications.read-all') }}">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="btn btn-light-primary btn-sm icon icon-left">
                                    <i class="bi bi-check2-all"></i><span>Tandai Semua Dibaca</span>
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
                <div class="card-body">
                    @forelse ($notifications as $notification)
                        @php
                            $variant = $notification->data['variant'] ?? 'primary';
                            $icon = $notification->data['icon'] ?? 'bell';
                            $isUnread = is_null($notification->read_at);
                            $actionLabel = $notification->data['action_label'] ?? 'Buka';
                            $meta = $notification->data['meta'] ?? [];
                            $occurredAt = filled($notification->data['occurred_at'] ?? null)
                                ? \Illuminate\Support\Carbon::parse($notification->data['occurred_at'])
                                : $notification->created_at;
                        @endphp
                        <div class="notification-list-item border rounded-3 p-3 {{ $loop->last ? '' : 'mb-3' }} {{ $isUnread ? 'bg-light' : 'bg-white' }}">
                            <div class="d-flex align-items-start gap-3 flex-wrap flex-md-nowrap">
                                <div class="avatar avatar-lg notification-icon bg-light-{{ $variant }}">
                                    <span class="avatar-content">
                                        <i class="bi bi-{{ $icon }}"></i>
                                    </span>
                                </div>
                                <div class="flex-grow-1">
                                    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-2">
                                        <div>
                                            <div class="d-flex align-items-center flex-wrap gap-2">
                                                <h5 class="mb-0">{{ $notification->data['title'] ?? 'Notifikasi baru' }}</h5>
                                                @if ($isUnread)
                                                    <span class="badge bg-primary">Baru</span>
                                                @endif
                                            </div>
                                            <small class="text-muted">
                                                {{ $occurredAt->translatedFormat('d M Y H:i') }} WIB
                                                <span class="mx-1">•</span>{{ $notification->created_at->diffForHumans() }}
                                            </small>
                                        </div>
                                        <a href="{{ route('pegawai.notifications.show', $notification) }}" class="btn btn-light-primary btn-sm icon icon-left">
                                            <i class="bi bi-arrow-right-circle"></i><span>{{ $actionLabel }}</span>
                                        </a>
                                    </div>

                                    <p class="mb-2">{{ $notification->data['message'] ?? '-' }}</p>

                                    @if (!empty($meta))
                                        <div class="d-flex align-items-center flex-wrap gap-2">
                                            @if (!empty($meta['asset_name']))
                                                <span class="badge bg-light-secondary">{{ $meta['asset_name'] }}</span>
                                            @endif
                                            @if (!empty($meta['asset_code']))
                                                <span class="badge bg-light-secondary">{{ $meta['asset_code'] }}</span>
                                            @endif
                                            @if (!empty($meta['planned_return_date']))
                                                <span class="badge bg-light-warning">Jatuh tempo {{ $meta['planned_return_date'] }}</span>
                                            @endif
                                            @if (!empty($meta['report_number']))
                                                <span class="badge bg-light-info">{{ $meta['report_number'] }}</span>
                                            @endif
                                            @if (!empty($meta['overdue_days']))
                                                <span class="badge bg-light-danger">Terlambat {{ $meta['overdue_days'] }} hari</span>
                                            @endif
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-5">
                            <div class="avatar avatar-xl bg-light-primary mb-3">
                                <span class="avatar-content">
                                    <i class="bi bi-bell"></i>
                                </span>
                            </div>
                            <h5 class="mb-2">Belum ada notifikasi</h5>
                            <p class="text-muted mb-0">Notifikasi persetujuan, pengembalian, dan pengingat akan muncul di halaman ini.</p>
                        </div>
                    @endforelse

                    @if ($notifications->hasPages())
                        <div class="mt-4">
                            {{ $notifications->links('pagination::bootstrap-5') }}
                        </div>
                    @endif
                </div>
            </div>
        </section>
    </div>
@endsection
