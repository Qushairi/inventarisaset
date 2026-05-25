@php
    $isPegawaiPage = request()->routeIs('pegawai.*');
@endphp

<footer>
    @if ($isPegawaiPage)
        <div class="footer clearfix mb-0 text-muted text-center">
            <p class="mb-0">&copy; 2025 Sistem Inventaris Aset - Dinas Pendidikan Kabupaten Bengkalis</p>
        </div>
    @else
        <div class="footer clearfix mb-0 text-muted">
            <div class="float-start">
                <p class="mb-0">{{ now()->year }} &copy; {{ config('app.name', 'Inventaris Aset') }}</p>
            </div>
            <div class="float-end">
                <p class="mb-0">{{ $footerLabel ?? 'Panel admin inventaris aset.' }}</p>
            </div>
        </div>
    @endif
</footer>
