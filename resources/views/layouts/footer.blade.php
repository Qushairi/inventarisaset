@php
    $isPegawaiPage = request()->routeIs('pegawai.*');
@endphp

<footer class="app-footer mt-auto py-3 border-top">
    <div class="container-fluid px-4">
        @if ($isPegawaiPage)
            <div class="d-flex flex-column flex-md-row align-items-center justify-content-between text-muted small font-12 gap-2">
                <div>
                    &copy; {{ now()->year }} <strong>Dinas Pendidikan Kabupaten Bengkalis</strong>. Hak Cipta Dilindungi.
                </div>
                <div class="text-muted">
                    Sistem Informasi Inventaris & Manajemen Aset
                </div>
            </div>
        @else
            <div class="d-flex flex-column flex-md-row align-items-center justify-content-between text-muted small font-12 gap-2">
                <div>
                    &copy; {{ now()->year }} <strong>{{ config('app.name', 'Inventaris Aset') }}</strong> — Dinas Pendidikan Kabupaten Bengkalis
                </div>
                <div>
                    {{ $footerLabel ?? 'Panel Admin Inventaris Aset' }}
                </div>
            </div>
        @endif
    </div>
</footer>
