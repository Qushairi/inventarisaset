@extends('layouts.app')

@section('title', 'Notifikasi Pegawai')

@section('content')
    <div class="page-heading">
        @include('pegawai.partials.page-heading', [
            'title' => 'Notifikasi Pegawai',
            'breadcrumb' => 'Notifikasi',
        ])
    </div>

    <div class="page-content">
        <section class="section">
            @if (session('success'))
                <div class="alert alert-light-success color-success mb-4">
                    <i class="bi bi-check-circle me-1"></i>{{ session('success') }}
                </div>
            @endif

            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-3">
                    <div>
                        <h4 class="mb-0">Daftar Notifikasi</h4>
                    </div>
                    @if ($unreadNotificationCount > 0)
                        <div>
                            <form method="POST" action="{{ route('pegawai.notifications.read-all') }}" data-swal-confirm data-swal-icon="question" data-swal-title="Tandai semua dibaca?" data-swal-text="Semua notifikasi akan ditandai sudah dibaca." data-swal-confirm-text="Ya, tandai" data-swal-confirm-color="#435ebe">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="btn btn-sm btn-light-primary icon icon-left">
                                    <i class="bi bi-check2-all"></i><span>Tandai Semua Dibaca</span>
                                </button>
                            </form>
                        </div>
                    @endif
                </div>

                <div class="card-body p-0">
                    <div class="list-group list-group-flush">
                        @forelse ($notifications as $notification)
                            @php
                                $variant = $notification->data['variant'] ?? 'primary';
                                $isUnread = is_null($notification->read_at);
                                $actionLabel = $notification->data['action_label'] ?? 'Lihat Detail';

                                $iconClass = match ($variant) {
                                    'success' => 'check-circle-fill text-success',
                                    'warning' => 'exclamation-triangle-fill text-warning',
                                    'danger' => 'x-circle-fill text-danger',
                                    default => 'info-circle-fill text-primary',
                                };

                                $bgClass = $isUnread ? 'bg-light-subtle' : '';
                            @endphp
                            <div class="list-group-item p-3 p-md-4 {{ $bgClass }}">
                                <div class="d-flex align-items-start justify-content-between flex-wrap gap-3">
                                    <div class="d-flex align-items-start gap-3 flex-grow-1">
                                        <div class="fs-4 flex-shrink-0 mt-1">
                                            <i class="bi bi-{{ $iconClass }}"></i>
                                        </div>
                                        <div>
                                            <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
                                                <h6 class="mb-0 fw-bold">{{ $notification->data['title'] ?? 'Notifikasi Baru' }}</h6>
                                                @if ($isUnread)
                                                    <span class="badge bg-primary rounded-pill">Baru</span>
                                                @endif
                                                <small class="text-muted">• {{ $notification->created_at->locale('id')->diffForHumans() }}</small>
                                            </div>
                                            <p class="text-muted mb-0">{{ $notification->data['message'] ?? '-' }}</p>
                                        </div>
                                    </div>
                                    <div class="flex-shrink-0 ms-auto">
                                        <a href="{{ route('pegawai.notifications.show', $notification) }}" class="btn btn-sm btn-light-primary icon icon-left">
                                            <i class="bi bi-eye"></i><span>{{ $actionLabel }}</span>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="text-center py-5 px-3">
                                <i class="bi bi-bell fs-1 text-muted d-block mb-3"></i>
                                <h6 class="fw-bold text-muted mb-1">Belum Ada Notifikasi</h6>
                                <small class="text-muted">Pemberitahuan terkait peminjaman dan pengembalian aset Anda akan muncul di sini.</small>
                            </div>
                        @endforelse
                    </div>

                    @if ($notifications->hasPages())
                        <div class="p-3 border-top">
                            {{ $notifications->links('pagination::bootstrap-5') }}
                        </div>
                    @endif
                </div>
            </div>
        </section>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const pills = document.querySelectorAll('[data-notif-filter]');
            const items = document.querySelectorAll('.pegawai-notif-item');

            pills.forEach((pill) => {
                pill.addEventListener('click', function () {
                    const filter = this.dataset.notifFilter;

                    pills.forEach((p) => p.classList.remove('active'));
                    this.classList.add('active');

                    items.forEach((item) => {
                        const notifType = item.dataset.notifType;
                        const notifRead = item.dataset.notifRead;

                        if (filter === 'all') {
                            item.classList.remove('d-none');
                        } else if (filter === 'unread') {
                            item.classList.toggle('d-none', notifRead !== 'unread');
                        } else if (filter === 'success') {
                            item.classList.toggle('d-none', notifType !== 'success');
                        } else if (filter === 'warning') {
                            item.classList.toggle('d-none', notifType !== 'warning' && notifType !== 'danger');
                        }
                    });
                });
            });
        });
    </script>
@endpush
