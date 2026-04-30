<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

class AdminPageController extends Controller
{
    public function dashboard()
    {
        $categories = $this->categoriesData();
        $locations = $this->locationsData();
        $assets = $this->assetsData();
        $employees = $this->employeesData();
        $loans = $this->loansData();
        $returns = $this->returnsData();

        $availableAssets = array_filter($assets, fn (array $asset) => $asset['status'] === 'Tersedia');
        $attentionAssets = array_filter($assets, fn (array $asset) => $asset['condition_variant'] !== 'success');

        return view('admin.dashboard', [
            'statCards' => [
                [
                    'label' => 'Total Aset',
                    'value' => count($assets),
                    'helper' => 'Aset aktif yang tercatat saat ini.',
                    'icon' => 'boxes',
                    'variant' => 'primary',
                ],
                [
                    'label' => 'Kategori',
                    'value' => count($categories),
                    'helper' => 'Kelompok aset yang tersedia.',
                    'icon' => 'tags',
                    'variant' => 'success',
                ],
                [
                    'label' => 'Peminjaman',
                    'value' => $loans['total'],
                    'helper' => 'Riwayat pengajuan peminjaman aset.',
                    'icon' => 'clipboard-check',
                    'variant' => 'warning',
                ],
                [
                    'label' => 'Pengembalian',
                    'value' => $returns['total'],
                    'helper' => 'Data pengembalian yang sudah diverifikasi.',
                    'icon' => 'arrow-counterclockwise',
                    'variant' => 'info',
                ],
            ],
            'highlights' => [
                [
                    'title' => 'Aset tersedia',
                    'value' => count($availableAssets),
                    'note' => 'Siap digunakan atau dipinjam.',
                ],
                [
                    'title' => 'Butuh perhatian',
                    'value' => count($attentionAssets),
                    'note' => 'Perlu pengecekan kondisi.',
                ],
                [
                    'title' => 'Akun pegawai',
                    'value' => count($employees),
                    'note' => 'Sudah dapat mengakses sistem.',
                ],
            ],
            'quickLinks' => [
                [
                    'title' => 'Kelola Kategori',
                    'description' => 'Atur kelompok aset agar data inventaris rapi.',
                    'route' => 'admin.categories.index',
                    'icon' => 'tags',
                ],
                [
                    'title' => 'Kelola Lokasi',
                    'description' => 'Tetapkan posisi dan ruangan penyimpanan aset.',
                    'route' => 'admin.locations.index',
                    'icon' => 'geo-alt',
                ],
                [
                    'title' => 'Kelola Aset',
                    'description' => 'Pantau kondisi, status, dan nilai perolehan barang.',
                    'route' => 'admin.assets.index',
                    'icon' => 'boxes',
                ],
                [
                    'title' => 'Kelola Pegawai',
                    'description' => 'Atur akun user pegawai yang memakai sistem.',
                    'route' => 'admin.employees.index',
                    'icon' => 'people',
                ],
                [
                    'title' => 'Peminjaman',
                    'description' => 'Setujui atau tolak pengajuan peminjaman aset.',
                    'route' => 'admin.loans.index',
                    'icon' => 'journal-check',
                ],
                [
                    'title' => 'Laporan',
                    'description' => 'Unduh rekap inventaris, peminjaman, dan pengembalian.',
                    'route' => 'admin.reports.index',
                    'icon' => 'bar-chart',
                ],
            ],
            'recentAssets' => array_slice($assets, 0, 3),
            'recentLoans' => array_slice($loans['items'], 0, 4),
        ]);
    }

    public function categories()
    {
        $categories = $this->categoriesData();

        return view('admin.categories.index', [
            'categories' => $categories,
        ]);
    }

    public function locations()
    {
        $locations = $this->locationsData();

        return view('admin.locations.index', [
            'locations' => $locations,
        ]);
    }

    public function assets()
    {
        $assets = $this->assetsData();

        return view('admin.assets.index', [
            'assets' => $assets,
        ]);
    }

    public function employees()
    {
        $employees = $this->employeesData();

        return view('admin.employees.index', [
            'employees' => $employees,
        ]);
    }

    public function loans()
    {
        $loans = $this->loansData();

        return view('admin.loans.index', [
            'loans' => $loans['items'],
            'loanTotal' => $loans['total'],
        ]);
    }

    public function returns()
    {
        $returns = $this->returnsData();

        return view('admin.returns.index', [
            'returns' => $returns['items'],
            'returnTotal' => $returns['total'],
        ]);
    }

    public function reports()
    {
        $assets = $this->assetsData();
        $loans = $this->loansData();
        $returns = $this->returnsData();
        $availableAssets = array_filter($assets, fn (array $asset) => $asset['status'] === 'Tersedia');

        return view('admin.reports.index', [
            'summaryCards' => [
                ['label' => 'Total Aset', 'value' => count($assets)],
                ['label' => 'Total Peminjaman', 'value' => $loans['total']],
                ['label' => 'Total Pengembalian', 'value' => $returns['total']],
                ['label' => 'Aset Tersedia', 'value' => count($availableAssets)],
            ],
            'loanPreview' => array_slice($loans['items'], 0, 3),
            'loanTotal' => $loans['total'],
            'returnPreview' => array_slice($returns['items'], 0, 3),
            'returnTotal' => $returns['total'],
        ]);
    }

    private function categoriesData(): array
    {
        return [
            [
                'name' => 'Transportasi',
                'code' => '12399',
                'description' => 'Kendaraan',
                'note' => 'Deskripsi kategori sudah tersedia.',
            ],
            [
                'name' => 'Bangunan',
                'code' => '154',
                'description' => 'Gedung dan Ruang Kerja',
                'note' => 'Deskripsi kategori sudah tersedia.',
            ],
            [
                'name' => 'Mesin',
                'code' => '123',
                'description' => 'Hardware',
                'note' => 'Deskripsi kategori sudah tersedia.',
            ],
        ];
    }

    private function locationsData(): array
    {
        return [
            [
                'name' => 'Ruang Bidang SMP',
                'code' => '23371',
                'address' => 'Jl. Pertanian',
                'address_note' => 'Alamat lokasi tersimpan pada sistem.',
                'description' => 'Ruangan Bidang SMP',
                'note' => 'Catatan lokasi tersedia.',
            ],
            [
                'name' => 'Ruangan Bidang SMP',
                'code' => '234',
                'address' => 'r.234',
                'address_note' => 'Alamat lokasi tersimpan pada sistem.',
                'description' => 'Area Penyimpanan Perangkat',
                'note' => 'Catatan lokasi tersedia.',
            ],
        ];
    }

    private function assetsData(): array
    {
        return [
            [
                'name' => 'Mobil',
                'code' => '123999',
                'note' => 'Bisa dipinjam',
                'avatar_type' => 'image',
                'avatar_value' => 'assets/images/samples/motorcycle.jpg',
                'category' => 'Transportasi',
                'category_note' => 'Kategori aset aktif pada sistem inventaris.',
                'location' => 'Ruang Bidang SMP',
                'location_note' => 'Jl. Pertanian',
                'condition' => 'Baik',
                'condition_variant' => 'success',
                'status' => 'Tersedia',
                'status_variant' => 'success',
                'price' => 'Rp 300.000.000',
                'acquired_at' => '13/03/2026',
            ],
            [
                'name' => 'Komputer',
                'code' => '142',
                'note' => 'Siap digunakan',
                'avatar_type' => 'image',
                'avatar_value' => 'assets/images/samples/1.png',
                'category' => 'Mesin',
                'category_note' => 'Kategori aset aktif pada sistem inventaris.',
                'location' => 'Ruangan Bidang SMP',
                'location_note' => 'r.234',
                'condition' => 'Baik',
                'condition_variant' => 'success',
                'status' => 'Tersedia',
                'status_variant' => 'success',
                'price' => 'Rp 10.000.000',
                'acquired_at' => '07/03/2026',
            ],
            [
                'name' => 'Komputer',
                'code' => '1233',
                'note' => 'Siap digunakan',
                'avatar_type' => 'initial',
                'avatar_value' => 'K',
                'category' => 'Mesin',
                'category_note' => 'Kategori aset aktif pada sistem inventaris.',
                'location' => 'Ruangan Bidang SMP',
                'location_note' => 'r.234',
                'condition' => 'Baik',
                'condition_variant' => 'success',
                'status' => 'Tersedia',
                'status_variant' => 'success',
                'price' => 'Rp 1.000.000',
                'acquired_at' => '07/03/2026',
            ],
            [
                'name' => 'Komputer',
                'code' => '1234',
                'note' => 'Perlu pengecekan minor',
                'avatar_type' => 'initial',
                'avatar_value' => 'K',
                'category' => 'Mesin',
                'category_note' => 'Kategori aset aktif pada sistem inventaris.',
                'location' => 'Ruangan Bidang SMP',
                'location_note' => 'r.234',
                'condition' => 'Rusak Ringan',
                'condition_variant' => 'warning',
                'status' => 'Tersedia',
                'status_variant' => 'success',
                'price' => 'Rp 1.000.000',
                'acquired_at' => '07/03/2026',
            ],
        ];
    }

    private function employeesData(): array
    {
        return [
            [
                'name' => 'Jidan',
                'account_id' => '#5',
                'initials' => 'J',
                'role' => 'Pegawai',
                'email' => 'jidan@gmail.com',
                'email_note' => 'Digunakan sebagai akun login ke sistem.',
                'registered_at' => '15/04/2026',
                'registered_time' => 'Pukul 17:46 WIB',
            ],
            [
                'name' => 'Muhammad Amien',
                'account_id' => '#4',
                'initials' => 'MA',
                'role' => 'Pegawai',
                'email' => 'amien@bengkalis.go.id',
                'email_note' => 'Digunakan sebagai akun login ke sistem.',
                'registered_at' => '09/03/2026',
                'registered_time' => 'Pukul 14:21 WIB',
            ],
            [
                'name' => 'Pegawai1',
                'account_id' => '#3',
                'initials' => 'P',
                'role' => 'Pegawai',
                'email' => 'pegawai1@bengkalis.go.id',
                'email_note' => 'Digunakan sebagai akun login ke sistem.',
                'registered_at' => '07/03/2026',
                'registered_time' => 'Pukul 20:00 WIB',
            ],
            [
                'name' => 'Pegawai Dinas',
                'account_id' => '#1',
                'initials' => 'PD',
                'role' => 'Pegawai',
                'email' => 'pegawai@bengkalis.go.id',
                'email_note' => 'Digunakan sebagai akun login ke sistem.',
                'registered_at' => '06/03/2026',
                'registered_time' => 'Pukul 19:49 WIB',
            ],
        ];
    }

    private function loansData(): array
    {
        return [
            'total' => 11,
            'items' => [
                [
                    'asset_name' => 'Komputer',
                    'asset_code' => '1234',
                    'employee_name' => 'Muhammad Amien',
                    'employee_email' => 'amien@bengkalis.go.id',
                    'loan_date' => '22/04/2026',
                    'return_plan' => 'Rencana kembali 22/04/2026',
                    'status' => 'Selesai',
                    'status_variant' => 'success',
                    'status_note' => 'Diproses oleh Admin Dinas',
                ],
                [
                    'asset_name' => 'Komputer',
                    'asset_code' => '1234',
                    'employee_name' => 'Muhammad Amien',
                    'employee_email' => 'amien@bengkalis.go.id',
                    'loan_date' => '17/04/2026',
                    'return_plan' => 'Rencana kembali 17/04/2026',
                    'status' => 'Selesai',
                    'status_variant' => 'success',
                    'status_note' => 'Diproses oleh Admin Dinas',
                ],
                [
                    'asset_name' => 'Komputer',
                    'asset_code' => '1234',
                    'employee_name' => 'Muhammad Amien',
                    'employee_email' => 'amien@bengkalis.go.id',
                    'loan_date' => '17/04/2026',
                    'return_plan' => 'Rencana kembali 17/04/2026',
                    'status' => 'Ditolak',
                    'status_variant' => 'danger',
                    'status_note' => 'Tidak ada stok tersedia saat pengajuan.',
                ],
                [
                    'asset_name' => 'Mobil',
                    'asset_code' => '123999',
                    'employee_name' => 'Muhammad Amien',
                    'employee_email' => 'amien@bengkalis.go.id',
                    'loan_date' => '15/04/2026',
                    'return_plan' => 'Rencana kembali 15/04/2026',
                    'status' => 'Selesai',
                    'status_variant' => 'success',
                    'status_note' => 'Diproses oleh Admin Dinas',
                ],
                [
                    'asset_name' => 'Komputer',
                    'asset_code' => '1233',
                    'employee_name' => 'Muhammad Amien',
                    'employee_email' => 'amien@bengkalis.go.id',
                    'loan_date' => '10/04/2026',
                    'return_plan' => 'Rencana kembali 10/04/2026',
                    'status' => 'Menunggu',
                    'status_variant' => 'warning',
                    'status_note' => 'Menunggu konfirmasi akhir dari admin.',
                ],
            ],
        ];
    }

    private function returnsData(): array
    {
        return [
            'total' => 10,
            'items' => [
                [
                    'asset_name' => 'Komputer',
                    'asset_code' => '1234',
                    'returned_at' => '20/04/2026',
                    'verified_note' => 'Diverifikasi Admin Dinas',
                    'condition' => 'Rusak Ringan',
                    'condition_variant' => 'warning',
                    'status' => 'Terverifikasi',
                    'status_variant' => 'success',
                    'status_note' => 'Rusak ringan',
                    'report_number' => 'BA-20260422065603-0010',
                    'report_note' => 'Berita acara sudah tersedia.',
                ],
                [
                    'asset_name' => 'Komputer',
                    'asset_code' => '1234',
                    'returned_at' => '17/04/2026',
                    'verified_note' => 'Diverifikasi Admin Dinas',
                    'condition' => 'Rusak Berat',
                    'condition_variant' => 'danger',
                    'status' => 'Terverifikasi',
                    'status_variant' => 'success',
                    'status_note' => 'Rusak',
                    'report_number' => 'BA-20260417064102-0009',
                    'report_note' => 'Berita acara sudah tersedia.',
                ],
                [
                    'asset_name' => 'Mobil',
                    'asset_code' => '123999',
                    'returned_at' => '16/04/2026',
                    'verified_note' => 'Diverifikasi Admin Dinas',
                    'condition' => 'Rusak Ringan',
                    'condition_variant' => 'warning',
                    'status' => 'Terverifikasi',
                    'status_variant' => 'success',
                    'status_note' => 'Rusak ringan',
                    'report_number' => 'BA-20260416080246-0008',
                    'report_note' => 'Berita acara sudah tersedia.',
                ],
                [
                    'asset_name' => 'Komputer',
                    'asset_code' => '1233',
                    'returned_at' => '15/04/2026',
                    'verified_note' => 'Diverifikasi Admin Dinas',
                    'condition' => 'Baik',
                    'condition_variant' => 'success',
                    'status' => 'Terverifikasi',
                    'status_variant' => 'success',
                    'status_note' => 'Mntp',
                    'report_number' => 'BA-20260422063454-0007',
                    'report_note' => 'Berita acara sudah tersedia.',
                ],
                [
                    'asset_name' => 'Komputer',
                    'asset_code' => '1234',
                    'returned_at' => '10/04/2026',
                    'verified_note' => 'Diverifikasi Admin Dinas',
                    'condition' => 'Rusak Berat',
                    'condition_variant' => 'danger',
                    'status' => 'Terverifikasi',
                    'status_variant' => 'success',
                    'status_note' => 'Rusak',
                    'report_number' => 'BA-20260410094909-0006',
                    'report_note' => 'Berita acara sudah tersedia.',
                ],
            ],
        ];
    }
}
