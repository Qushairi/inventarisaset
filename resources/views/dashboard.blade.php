@extends('layouts.app')

@section('title', 'Dashboard')

@push('styles')
    <style>
        .dashboard-hero {
            background: linear-gradient(135deg, #435ebe 0%, #6f86e8 100%);
            color: #fff;
            overflow: hidden;
        }

        .dashboard-hero .hero-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.45rem 0.8rem;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.16);
            font-weight: 700;
            font-size: 0.82rem;
            letter-spacing: 0.02em;
        }

        .dashboard-hero .hero-title {
            color: #fff;
            font-size: 2rem;
            line-height: 1.2;
            margin-bottom: 0.75rem;
        }

        .dashboard-hero .hero-copy {
            color: rgba(255, 255, 255, 0.82);
            max-width: 38rem;
            margin-bottom: 0;
        }

        .hero-meta {
            display: flex;
            justify-content: end;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .hero-meta-item {
            min-width: 150px;
            padding: 1rem 1.1rem;
            border-radius: 1rem;
            background: rgba(255, 255, 255, 0.12);
            backdrop-filter: blur(4px);
        }

        .hero-meta-item span {
            display: block;
            font-size: 0.8rem;
            color: rgba(255, 255, 255, 0.75);
            margin-bottom: 0.35rem;
        }

        .hero-meta-item strong {
            font-size: 1.2rem;
            color: #fff;
        }

        .dashboard-stat {
            height: 100%;
        }

        .dashboard-stat .card-body {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 1rem;
        }

        .dashboard-stat .stat-label {
            display: block;
            color: #7c8db5;
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
        }

        .dashboard-stat .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: #25396f;
            line-height: 1;
            margin-bottom: 0.5rem;
        }

        .dashboard-stat .stat-icon {
            width: 3.2rem;
            height: 3.2rem;
            border-radius: 1rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 1.35rem;
            flex-shrink: 0;
        }

        .dashboard-stat .stat-icon.primary {
            background: #ebf3ff;
            color: #435ebe;
        }

        .dashboard-stat .stat-icon.success {
            background: #dff8eb;
            color: #198754;
        }

        .dashboard-stat .stat-icon.warning {
            background: #fff4da;
            color: #d99100;
        }

        .dashboard-stat .stat-icon.info {
            background: #e4f8ff;
            color: #0d6efd;
        }

        .dashboard-list {
            display: grid;
            gap: 1rem;
        }

        .dashboard-list-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            padding: 1rem 1.1rem;
            border: 1px solid #eef1f7;
            border-radius: 1rem;
        }

        .dashboard-list-item strong {
            display: block;
            color: #25396f;
            margin-bottom: 0.2rem;
        }

        .dashboard-list-item span {
            color: #7c8db5;
            font-size: 0.9rem;
        }

        .dashboard-chip {
            display: inline-flex;
            align-items: center;
            padding: 0.4rem 0.7rem;
            border-radius: 999px;
            font-size: 0.82rem;
            font-weight: 700;
        }

        .dashboard-chip.success {
            color: #12724c;
            background: #ddf7e9;
        }

        .dashboard-chip.warning {
            color: #9c6b00;
            background: #fff2d0;
        }

        .dashboard-chip.primary {
            color: #314bb5;
            background: #e8eeff;
        }

        .quick-links {
            display: grid;
            gap: 0.85rem;
        }

        .quick-link {
            display: flex;
            align-items: center;
            gap: 0.9rem;
            padding: 1rem 1.1rem;
            border-radius: 1rem;
            background: #f7f9fc;
            color: #25396f;
            transition: 0.2s ease;
        }

        .quick-link:hover {
            background: #ebf3ff;
            color: #435ebe;
        }

        .quick-link i {
            width: 2.5rem;
            height: 2.5rem;
            border-radius: 0.85rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: #fff;
            color: #435ebe;
            font-size: 1.1rem;
        }

        .quick-link strong {
            display: block;
            margin-bottom: 0.15rem;
        }

        .quick-link span {
            display: block;
            color: #7c8db5;
            font-size: 0.88rem;
        }

        @media (max-width: 767.98px) {
            .dashboard-hero .hero-title {
                font-size: 1.6rem;
            }

            .hero-meta {
                justify-content: start;
            }
        }
    </style>
@endpush

@section('content')
    <div class="page-heading dashboard-page">
        <section class="section">
            <div class="card dashboard-hero">
                <div class="card-body p-4 p-lg-5">
                    <div class="row align-items-center g-4">
                        <div class="col-12 col-lg-7">
                            <span class="hero-badge">
                                <i class="bi bi-stars"></i>
                                Panel Inventaris
                            </span>
                            <h3 class="hero-title mt-3">Dashboard Inventaris Aset</h3>
                            <p class="hero-copy">
                                Layout sudah dirapikan agar sidebar, navbar, kartu statistik, dan area konten terasa lebih
                                konsisten dan mudah dikembangkan untuk halaman berikutnya.
                            </p>
                        </div>
                        <div class="col-12 col-lg-5">
                            <div class="hero-meta">
                                <div class="hero-meta-item">
                                    <span>Total Barang</span>
                                    <strong>128 Unit</strong>
                                </div>
                                <div class="hero-meta-item">
                                    <span>Dipinjam</span>
                                    <strong>14 Unit</strong>
                                </div>
                                <div class="hero-meta-item">
                                    <span>Update Terakhir</span>
                                    <strong>Hari Ini</strong>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="section">
            <div class="row">
                <div class="col-12 col-md-6 col-xl-3">
                    <div class="card dashboard-stat">
                        <div class="card-body">
                            <div>
                                <span class="stat-label">Total Barang</span>
                                <div class="stat-value">128</div>
                                <p class="mb-0 text-muted">Seluruh aset yang sudah terdaftar.</p>
                            </div>
                            <span class="stat-icon primary">
                                <i class="bi bi-box-seam"></i>
                            </span>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-md-6 col-xl-3">
                    <div class="card dashboard-stat">
                        <div class="card-body">
                            <div>
                                <span class="stat-label">Barang Dipinjam</span>
                                <div class="stat-value">14</div>
                                <p class="mb-0 text-muted">Sedang digunakan oleh peminjam.</p>
                            </div>
                            <span class="stat-icon success">
                                <i class="bi bi-arrow-left-right"></i>
                            </span>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-md-6 col-xl-3">
                    <div class="card dashboard-stat">
                        <div class="card-body">
                            <div>
                                <span class="stat-label">Laporan Bulan Ini</span>
                                <div class="stat-value">6</div>
                                <p class="mb-0 text-muted">Rekap yang sudah dihasilkan bulan ini.</p>
                            </div>
                            <span class="stat-icon warning">
                                <i class="bi bi-bar-chart"></i>
                            </span>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-md-6 col-xl-3">
                    <div class="card dashboard-stat">
                        <div class="card-body">
                            <div>
                                <span class="stat-label">Butuh Tindak Lanjut</span>
                                <div class="stat-value">3</div>
                                <p class="mb-0 text-muted">Aset perlu pengecekan atau pembaruan.</p>
                            </div>
                            <span class="stat-icon info">
                                <i class="bi bi-exclamation-circle"></i>
                            </span>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-xl-8">
                    <div class="card">
                        <div class="card-header">
                            <h4>Aktivitas Terbaru</h4>
                        </div>
                        <div class="card-body">
                            <div class="dashboard-list">
                                <div class="dashboard-list-item">
                                    <div>
                                        <strong>Peminjaman proyektor ruang lab</strong>
                                        <span>Diperbarui 10 menit yang lalu oleh petugas inventaris.</span>
                                    </div>
                                    <span class="dashboard-chip success">Selesai</span>
                                </div>

                                <div class="dashboard-list-item">
                                    <div>
                                        <strong>Permintaan stok kursi lipat</strong>
                                        <span>Menunggu verifikasi untuk pemindahan aset antar ruangan.</span>
                                    </div>
                                    <span class="dashboard-chip warning">Proses</span>
                                </div>

                                <div class="dashboard-list-item">
                                    <div>
                                        <strong>Rekap inventaris semester genap</strong>
                                        <span>Laporan siap ditinjau dan diunduh oleh administrator.</span>
                                    </div>
                                    <span class="dashboard-chip primary">Siap</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-xl-4">
                    <div class="card">
                        <div class="card-header">
                            <h4>Aksi Cepat</h4>
                        </div>
                        <div class="card-body">
                            <div class="quick-links">
                                <a href="#" class="quick-link">
                                    <i class="bi bi-plus-square"></i>
                                    <div>
                                        <strong>Tambah Barang</strong>
                                        <span>Input aset baru ke sistem.</span>
                                    </div>
                                </a>

                                <a href="#" class="quick-link">
                                    <i class="bi bi-journal-text"></i>
                                    <div>
                                        <strong>Buat Laporan</strong>
                                        <span>Rekap inventaris dan transaksi.</span>
                                    </div>
                                </a>

                                <a href="#" class="quick-link">
                                    <i class="bi bi-building"></i>
                                    <div>
                                        <strong>Kelola Ruangan</strong>
                                        <span>Atur lokasi dan penempatan aset.</span>
                                    </div>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
@endsection
