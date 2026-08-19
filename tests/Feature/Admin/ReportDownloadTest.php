<?php

namespace Tests\Feature\Admin;

use App\Models\Asset;
use App\Models\AssetReturn;
use App\Models\Category;
use App\Models\Loan;
use App\Models\Location;
use App\Models\User;
use App\Support\ReportExcelService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use Tests\TestCase;

class ReportDownloadTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_download_filtered_inventory_report_as_kir_xlsx(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $category = $this->createCategory('Peralatan dan Mesin', '02');
        $excludedCategory = $this->createCategory('Gedung dan Bangunan', '03');
        $administrationRoom = $this->createLocation(
            'Ruang Administrasi',
            '12.04.02.08.01.01',
        );
        $archiveRoom = $this->createLocation(
            'Gudang Arsip',
            '12.04.02.08.01.02',
        );

        $this->createAsset($category, $administrationRoom, [
            'name' => 'Printer Laporan',
            'code' => '02.06.03.02.001',
            'brand_model' => 'Epson L3210',
            'note' => 'Siap pakai',
            'serial_number' => 'SN-PRN-001',
            'size' => 'A4',
            'material' => 'Plastik',
            'condition' => 'Baik',
            'status' => 'Tersedia',
            'quantity' => 2,
            'acquisition_price' => 3000000,
            'acquisition_year' => 2025,
        ]);
        $this->createAsset($category, $archiveRoom, [
            'name' => 'Lemari Arsip',
            'code' => '02.07.01.04.001',
            'note' => 'Engsel rusak',
            'serial_number' => null,
            'size' => '180 cm',
            'material' => 'Besi',
            'condition' => 'Rusak Berat',
            'status' => 'Perbaikan',
            'quantity' => 1,
            'acquisition_price' => 1500000,
            'acquisition_year' => null,
            'acquired_at' => '2024-05-01',
        ]);
        $this->createAsset($category, $archiveRoom, [
            'name' => 'Kursi Arsip',
            'code' => '02.07.01.04.001',
            'note' => null,
            'condition' => 'Rusak Ringan',
            'status' => 'Tersedia',
            'quantity' => 4,
            'acquisition_price' => 800000,
            'acquisition_year' => 2023,
        ]);
        $this->createAsset($excludedCategory, $administrationRoom, [
            'name' => 'Gedung yang Dikecualikan',
            'code' => '03.01.01.01.001',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.reports.download', [
            'type' => 'inventaris',
            'category_id' => $category->id,
        ]));

        $workbook = $this->workbookFromResponse(
            $response,
            'data-aset.xlsx',
        );

        try {
            $this->assertSame(2, $workbook->getSheetCount());

            $administrationSheet = $workbook->getSheetByName('Ruang Administrasi');
            $this->assertNotNull($administrationSheet);
            $this->assertSame('KARTU INVENTARIS RUANGAN', $administrationSheet->getCell('C2')->getValue());
            $this->assertSame('Per 31 Desember '.(now()->year - 1), $administrationSheet->getCell('C3')->getValue());
            $this->assertSame(': Dinas Pendidikan', $administrationSheet->getCell('D11')->getValue());
            $this->assertSame(': Ruang Administrasi', $administrationSheet->getCell('D12')->getValue());
            $this->assertSame(': 12.04.02.08.01.01', $administrationSheet->getCell('D14')->getValue());
            $this->assertSame("Jenis Barang /\nNama Barang", $administrationSheet->getCell('C15')->getValue());
            $this->assertSame("No. Seri\nPabrik", $administrationSheet->getCell('E15')->getValue());
            $this->assertSame('Tahun  Pembuatan / Pembelian', $administrationSheet->getCell('F15')->getValue());
            $this->assertSame('Jumlah Barang / Register', $administrationSheet->getCell('I15')->getValue());
            $this->assertSame("No. Kode\nBarang", $administrationSheet->getCell('I16')->getValue());
            $this->assertSame('Jumlah Barang', $administrationSheet->getCell('J16')->getValue());
            $this->assertSame("Harga Beli /\nPerolehan", $administrationSheet->getCell('K16')->getValue());
            $this->assertSame("Baik\n\n(B)", $administrationSheet->getCell('L16')->getValue());
            $this->assertSame("Kurang\nBaik\n(KB)", $administrationSheet->getCell('M16')->getValue());
            $this->assertSame("Rusak\nBerat\n(RB)", $administrationSheet->getCell('N16')->getValue());
            $this->assertSame(range(1, 14), $administrationSheet->rangeToArray('B17:O17', null, true, false)[0]);

            $this->assertSame(1, $administrationSheet->getCell('B19')->getValue());
            $this->assertSame('Printer Laporan', $administrationSheet->getCell('C19')->getValue());
            $this->assertSame('Epson L3210', $administrationSheet->getCell('D19')->getValue());
            $this->assertSame('SN-PRN-001', $administrationSheet->getCell('E19')->getValue());
            $this->assertSame('A4', $administrationSheet->getCell('F19')->getValue());
            $this->assertSame('Plastik', $administrationSheet->getCell('G19')->getValue());
            $this->assertSame(2025, $administrationSheet->getCell('H19')->getValue());
            $this->assertSame('02.06.03.02.001', $administrationSheet->getCell('I19')->getValue());
            $this->assertSame(2, $administrationSheet->getCell('J19')->getValue());
            $this->assertSame(3000000.0, $administrationSheet->getCell('K19')->getValue());
            $this->assertSame('Baik', $administrationSheet->getCell('L19')->getValue());
            $this->assertSame('-', $administrationSheet->getCell('M19')->getValue());
            $this->assertSame('-', $administrationSheet->getCell('N19')->getValue());
            $this->assertSame('Siap pakai', $administrationSheet->getCell('O19')->getValue());
            $this->assertSame('J U M L A H', $administrationSheet->getCell('B20')->getValue());
            $this->assertSame('=SUM(K19:K19)', $administrationSheet->getCell('K20')->getValue());
            $this->assertContains('B20:J20', $administrationSheet->getMergeCells());
            $this->assertSame(PageSetup::ORIENTATION_LANDSCAPE, $administrationSheet->getPageSetup()->getOrientation());
            $this->assertSame(PageSetup::PAPERSIZE_LEGAL, $administrationSheet->getPageSetup()->getPaperSize());
            $this->assertSame(1, $administrationSheet->getPageSetup()->getFitToWidth());
            $this->assertSame(1, $administrationSheet->getPageSetup()->getFitToHeight());

            $archiveSheet = $workbook->getSheetByName('Gudang Arsip');
            $this->assertNotNull($archiveSheet);
            $this->assertSame(': Gudang Arsip', $archiveSheet->getCell('D12')->getValue());
            $this->assertSame(': 12.04.02.08.01.02', $archiveSheet->getCell('D14')->getValue());
            $this->assertSame('Lemari Arsip', $archiveSheet->getCell('C19')->getValue());
            $this->assertSame(2024, $archiveSheet->getCell('H19')->getValue());
            $this->assertSame('-', $archiveSheet->getCell('L19')->getValue());
            $this->assertSame('-', $archiveSheet->getCell('M19')->getValue());
            $this->assertSame('Rusak Berat', $archiveSheet->getCell('N19')->getValue());
            $this->assertSame('Engsel rusak', $archiveSheet->getCell('O19')->getValue());
            $this->assertSame('Kursi Arsip', $archiveSheet->getCell('C20')->getValue());
            $this->assertSame('02.07.01.04.001', $archiveSheet->getCell('I19')->getValue());
            $this->assertSame('02.07.01.04.001', $archiveSheet->getCell('I20')->getValue());
            $this->assertSame('-', $archiveSheet->getCell('L20')->getValue());
            $this->assertSame('Kurang Baik', $archiveSheet->getCell('M20')->getValue());
            $this->assertSame('-', $archiveSheet->getCell('N20')->getValue());
            $this->assertSame('J U M L A H', $archiveSheet->getCell('B21')->getValue());
            $this->assertSame('=SUM(K19:K20)', $archiveSheet->getCell('K21')->getValue());

            $allValues = json_encode($workbook->getActiveSheet()->toArray());
            $this->assertIsString($allValues);
            $this->assertStringNotContainsString('Gedung yang Dikecualikan', $allValues);
        } finally {
            $workbook->disconnectWorksheets();
        }
    }

    public function test_admin_can_download_filtered_loan_report_as_xlsx(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $employee = User::factory()->create([
            'role' => 'pegawai',
            'name' => 'Pegawai Laporan',
            'email' => 'pegawai.laporan@example.com',
        ]);
        $otherEmployee = User::factory()->create(['role' => 'pegawai']);
        $category = $this->createCategory('Elektronik', 'ELK');
        $location = $this->createLocation('Gudang Utama', 'LOC-GDG');
        $includedAsset = $this->createAsset($category, $location, [
            'name' => 'Laptop Peminjaman',
            'code' => 'AST-LOAN-001',
        ]);
        $excludedAsset = $this->createAsset($category, $location, [
            'name' => 'Proyektor Dikecualikan',
            'code' => 'AST-LOAN-002',
        ]);

        Loan::query()->create([
            'asset_id' => $includedAsset->id,
            'user_id' => $employee->id,
            'loan_date' => '2026-06-05',
            'planned_return_date' => '2026-06-10',
            'quantity' => 1,
            'status' => 'Disetujui',
            'status_note' => 'Dipakai untuk presentasi dinas.',
        ]);
        Loan::query()->create([
            'asset_id' => $includedAsset->id,
            'user_id' => $employee->id,
            'loan_date' => '2025-06-05',
            'planned_return_date' => '2025-06-10',
            'quantity' => 1,
            'status' => 'Disetujui',
            'status_note' => 'Data tahun 2025.',
        ]);
        Loan::query()->create([
            'asset_id' => $excludedAsset->id,
            'user_id' => $otherEmployee->id,
            'loan_date' => '2026-05-01',
            'planned_return_date' => '2026-05-02',
            'quantity' => 1,
            'status' => 'Ditolak',
            'status_note' => 'Data di luar filter.',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.reports.download', [
            'type' => 'peminjaman',
            'user_id' => $employee->id,
            'status' => 'Disetujui',
            'year' => 2026,
            'date_from' => '2025-01-01',
            'date_to' => '2026-12-31',
        ]));

        $workbook = $this->workbookFromResponse($response, 'laporan-peminjaman-aset.xlsx');

        try {
            $sheet = $workbook->getActiveSheet();
            $this->assertSame('Peminjaman', $sheet->getTitle());
            $this->assertSame('Laporan Peminjaman Aset', $sheet->getCell('A1')->getValue());
            $this->assertSame('DINAS PENDIDIKAN KABUPATEN BENGKALIS', $sheet->getCell('A2')->getValue());
            $this->assertSame(
                'Filter: Pegawai: Pegawai Laporan | Status: Disetujui | Tahun: 2026 | Dari: 01/01/2025 | Sampai: 31/12/2026',
                $sheet->getCell('A4')->getValue(),
            );
            $this->assertSame([
                'No',
                'Kode Aset',
                'Nama Aset',
                'Pegawai',
                'Email',
                'Tanggal Pinjam',
                'Rencana Kembali',
                'Status',
                'Catatan',
            ], $sheet->rangeToArray('A6:I6', null, true, false)[0]);
            $this->assertSame([
                1,
                'AST-LOAN-001',
                'Laptop Peminjaman',
                'Pegawai Laporan',
                'pegawai.laporan@example.com',
                '05/06/2026',
                '10/06/2026',
                'Disetujui',
                'Dipakai untuk presentasi dinas.',
            ], $sheet->rangeToArray('A7:I7', null, true, false)[0]);
            $this->assertNull($sheet->getCell('A8')->getValue());

            $values = json_encode($sheet->toArray());
            $this->assertIsString($values);
            $this->assertStringNotContainsString('AST-LOAN-002', $values);
            $this->assertStringNotContainsString('Data tahun 2025.', $values);
        } finally {
            $workbook->disconnectWorksheets();
        }
    }

    public function test_admin_can_download_filtered_return_report_as_xlsx(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $employee = User::factory()->create([
            'role' => 'pegawai',
            'name' => 'Pegawai Pengembalian',
            'email' => 'pegawai.pengembalian@example.com',
        ]);
        $otherEmployee = User::factory()->create(['role' => 'pegawai']);
        $category = $this->createCategory('Peralatan Kantor', 'KTR');
        $location = $this->createLocation('Ruang Pelayanan', 'LOC-PLY');
        $includedAsset = $this->createAsset($category, $location, [
            'name' => 'Monitor Pengembalian',
            'code' => 'AST-RET-001',
        ]);
        $excludedAsset = $this->createAsset($category, $location, [
            'name' => 'Keyboard Dikecualikan',
            'code' => 'AST-RET-002',
        ]);

        AssetReturn::query()->create([
            'asset_id' => $includedAsset->id,
            'user_id' => $employee->id,
            'returned_at' => '2026-06-15',
            'condition' => 'Rusak Ringan',
            'status' => 'Terverifikasi',
            'status_note' => null,
            'report_number' => 'BA-20260615000001',
            'report_note' => 'Kabel perlu diganti.',
        ]);
        AssetReturn::query()->create([
            'asset_id' => $includedAsset->id,
            'user_id' => $employee->id,
            'returned_at' => '2025-06-15',
            'condition' => 'Rusak Ringan',
            'status' => 'Terverifikasi',
            'status_note' => 'Data tahun 2025.',
            'report_number' => 'BA-20250615000003',
        ]);
        AssetReturn::query()->create([
            'asset_id' => $excludedAsset->id,
            'user_id' => $otherEmployee->id,
            'returned_at' => '2026-05-01',
            'condition' => 'Baik',
            'status' => 'Terverifikasi',
            'status_note' => 'Data di luar filter.',
            'report_number' => 'BA-20260501000002',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.reports.download', [
            'type' => 'pengembalian',
            'user_id' => $employee->id,
            'condition' => 'Rusak Ringan',
            'year' => 2026,
            'date_from' => '2025-01-01',
            'date_to' => '2026-12-31',
        ]));

        $workbook = $this->workbookFromResponse($response, 'laporan-pengembalian-aset.xlsx');

        try {
            $sheet = $workbook->getActiveSheet();
            $this->assertSame('Pengembalian', $sheet->getTitle());
            $this->assertSame('Laporan Pengembalian Aset', $sheet->getCell('A1')->getValue());
            $this->assertSame(
                'Filter: Pegawai: Pegawai Pengembalian | Kondisi: Rusak Ringan | Tahun: 2026 | Dari: 01/01/2025 | Sampai: 31/12/2026',
                $sheet->getCell('A4')->getValue(),
            );
            $this->assertSame([
                'No',
                'No. BA',
                'Kode Aset',
                'Nama Aset',
                'Pegawai',
                'Tanggal Kembali',
                'Kondisi',
                'Status',
                'Catatan',
            ], $sheet->rangeToArray('A6:I6', null, true, false)[0]);
            $this->assertSame([
                1,
                'BA-20260615000001',
                'AST-RET-001',
                'Monitor Pengembalian',
                'Pegawai Pengembalian',
                '15/06/2026',
                'Rusak Ringan',
                'Terverifikasi',
                'Kabel perlu diganti.',
            ], $sheet->rangeToArray('A7:I7', null, true, false)[0]);
            $this->assertNull($sheet->getCell('A8')->getValue());

            $values = json_encode($sheet->toArray());
            $this->assertIsString($values);
            $this->assertStringNotContainsString('BA-20260501000002', $values);
            $this->assertStringNotContainsString('BA-20250615000003', $values);
        } finally {
            $workbook->disconnectWorksheets();
        }
    }

    public function test_admin_report_page_shows_excel_downloads_and_filters(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->get(route('admin.reports.index'));

        $response
            ->assertOk()
            ->assertSee('berkas Excel')
            ->assertSee('Unduh Data Aset')
            ->assertSee('Unduh Peminjaman')
            ->assertSee('Unduh Pengembalian')
            ->assertDontSee('Unduh PDF')
            ->assertSee('action="'.route('admin.reports.download', 'inventaris').'"', false)
            ->assertSee('action="'.route('admin.reports.download', 'peminjaman').'"', false)
            ->assertSee('action="'.route('admin.reports.download', 'pengembalian').'"', false)
            ->assertSee('name="category_id"', false)
            ->assertSee('name="location_id"', false)
            ->assertSee('name="year"', false)
            ->assertSee('id="report_loan_year"', false)
            ->assertSee('id="report_return_year"', false)
            ->assertSee('name="user_id"', false)
            ->assertSee('name="date_from"', false)
            ->assertSee('name="date_to"', false)
            ->assertSee('value="Tersedia"', false)
            ->assertSee('value="Dipinjam"', false)
            ->assertSee('value="Perbaikan"', false)
            ->assertDontSee('value="Diverifikasi"', false);
    }

    public function test_report_rejects_an_invalid_date_range(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)
            ->from(route('admin.reports.index'))
            ->get(route('admin.reports.download', [
                'type' => 'peminjaman',
                'date_from' => '2026-06-10',
                'date_to' => '2026-06-01',
            ]));

        $response->assertRedirect(route('admin.reports.index'));
        $response->assertSessionHasErrors('date_to');
    }

    private function workbookFromResponse(TestResponse $response, string $filename): Spreadsheet
    {
        $response
            ->assertOk()
            ->assertHeader('content-type', ReportExcelService::MIME_TYPE)
            ->assertDownload($filename);

        $bytes = $response->streamedContent();
        $this->assertNotSame('', $bytes);
        $this->assertStringStartsWith("PK\x03\x04", $bytes);

        $temporaryBase = tempnam(sys_get_temp_dir(), 'report-xlsx-test-');

        if ($temporaryBase === false) {
            self::fail('Tidak dapat membuat berkas sementara untuk membaca laporan Excel.');
        }

        if (is_file($temporaryBase)) {
            unlink($temporaryBase);
        }

        $temporaryPath = $temporaryBase.'.xlsx';

        if (file_put_contents($temporaryPath, $bytes) === false) {
            self::fail('Tidak dapat menulis respons laporan Excel ke berkas sementara.');
        }

        try {
            $reader = new Xlsx;
            $reader->setReadDataOnly(false);

            return $reader->load($temporaryPath);
        } finally {
            if (is_file($temporaryPath)) {
                unlink($temporaryPath);
            }
        }
    }

    private function createCategory(string $name, string $code): Category
    {
        return Category::query()->create([
            'name' => $name,
            'code' => $code,
            'description' => 'Kategori untuk pengujian laporan.',
            'note' => 'Data pengujian',
        ]);
    }

    private function createLocation(string $name, string $code): Location
    {
        return Location::query()->create([
            'name' => $name,
            'code' => $code,
            'address' => 'Dinas Pendidikan',
            'address_note' => 'Lokasi pengujian',
            'description' => 'Lokasi untuk pengujian laporan.',
            'note' => 'Data pengujian',
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function createAsset(Category $category, Location $location, array $overrides = []): Asset
    {
        return Asset::query()->create(array_merge([
            'category_id' => $category->id,
            'location_id' => $location->id,
            'name' => 'Aset Laporan',
            'code' => 'AST-RPT-DEFAULT',
            'note' => 'Aset untuk pengujian laporan.',
            'image_path' => null,
            'serial_number' => null,
            'size' => null,
            'material' => null,
            'condition' => 'Baik',
            'status' => 'Tersedia',
            'quantity' => 1,
            'acquisition_price' => 1000000,
            'acquisition_year' => 2025,
            'acquired_at' => null,
        ], $overrides));
    }
}
