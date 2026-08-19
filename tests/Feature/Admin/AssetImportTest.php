<?php

namespace Tests\Feature\Admin;

use App\Models\Asset;
use App\Models\Category;
use App\Models\Location;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class AssetImportTest extends TestCase
{
    use RefreshDatabase;

    /** @var array<int, string> */
    private array $temporaryFiles = [];

    protected function tearDown(): void
    {
        foreach ($this->temporaryFiles as $path) {
            if (is_file($path)) {
                unlink($path);
            }
        }

        parent::tearDown();
    }

    public function test_admin_can_import_kir_workbook_with_its_complete_mapping(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->post(route('admin.assets.import'), [
            '_import_modal' => 'asset',
            'import_file' => $this->kirWorkbook(),
        ]);

        $response
            ->assertRedirect(route('admin.assets.index'))
            ->assertSessionHas('success', fn (string $message): bool => str_contains($message, '6 data aset (7 barang)')
                && str_contains($message, '2 lokasi dengan 1 kategori otomatis'));

        $this->assertSame(6, Asset::query()->count());
        $this->assertSame(2, Location::query()->count());
        $this->assertSame(1, Category::query()->count());
        $category = Category::query()->where('code', '02')->firstOrFail();
        $this->assertSame('Peralatan dan Mesin', $category->name);
        $this->assertSame(6, Asset::query()->where('category_id', $category->id)->count());
        $this->assertSame(2, Asset::query()->where('code', '02.06.02.04.04')->count());

        $kadis = Location::query()->where('code', '12.04.02.08.01.01')->firstOrFail();
        $sekretaris = Location::query()->where('code', '12.04.02.08.01.02')->firstOrFail();

        $this->assertSame('Kepala Dinas', $kadis->name);
        $this->assertSame('Sekretaris', $sekretaris->name);
        $this->assertSame('Dinas Pendidikan', $kadis->address);

        $ac = Asset::query()->where('name', 'AC Split')->firstOrFail();
        $this->assertSame($kadis->id, $ac->location_id);
        $this->assertSame('OSNC0960NA0', $ac->serial_number);
        $this->assertSame('1 PK', $ac->size);
        $this->assertSame('Plastik / Besi', $ac->material);
        $this->assertSame(2017, $ac->acquisition_year);
        $this->assertSame('4102134.57', $ac->acquisition_price);
        $this->assertSame('Baik', $ac->condition);
        $this->assertSame('Tersedia', $ac->status);
        $this->assertSame('LG', $ac->brand_model);
        $this->assertSame('Unit utama', $ac->note);

        $dispenser = Asset::query()->where('name', 'Dispenser')->firstOrFail();
        $this->assertSame(2, $dispenser->quantity);
        $this->assertSame('Rusak Ringan', $dispenser->condition);
        $this->assertSame('Tersedia', $dispenser->status);

        $infocus = Asset::query()->where('name', 'Infocus')->firstOrFail();
        $this->assertSame(1, $infocus->quantity);
        $this->assertSame('Rusak Berat', $infocus->condition);
        $this->assertSame('Perbaikan', $infocus->status);
        $this->assertSame('0.00', $infocus->acquisition_price);
        $this->assertSame('Epson', $infocus->brand_model);
        $this->assertNull($infocus->note);

        $dvd = Asset::query()->where('name', 'DVD')->firstOrFail();
        $speaker = Asset::query()->where('name', 'Speaker')->firstOrFail();
        $this->assertSame('0.00', $dvd->acquisition_price);
        $this->assertSame('0.00', $speaker->acquisition_price);
        $this->assertSame('Type E360', $dvd->brand_model);
        $this->assertSame('Type CS-450V', $speaker->brand_model);
        $this->assertNull($dvd->note);
        $this->assertNull($speaker->note);

        $this->assertDatabaseMissing('assets', ['name' => 'Jangan Diimpor']);
    }

    public function test_reimporting_identical_kir_rows_merges_their_quantities(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->post(route('admin.assets.import'), [
            '_import_modal' => 'asset',
            'import_file' => $this->kirWorkbook(),
        ])->assertRedirect(route('admin.assets.index'));

        $this->actingAs($admin)->post(route('admin.assets.import'), [
            '_import_modal' => 'asset',
            'import_file' => $this->kirWorkbook(),
        ])->assertRedirect(route('admin.assets.index'));

        $this->assertSame(6, Asset::query()->count());
        $this->assertSame(14, Asset::query()->sum('quantity'));
        $this->assertSame(4, Asset::query()->where('name', 'Dispenser')->sole()->quantity);
    }

    public function test_asset_page_contains_excel_import_modal(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->get(route('admin.assets.index'));

        $response
            ->assertOk()
            ->assertSee('Import Excel')
            ->assertSee('id="adminAssetImportModal"', false)
            ->assertSee('action="'.route('admin.assets.import').'"', false)
            ->assertSee('enctype="multipart/form-data"', false)
            ->assertSee('name="import_file"', false)
            ->assertDontSee('id="admin_asset_import_category"', false)
            ->assertSee('kategori dan lokasi akan ditentukan otomatis');
    }

    public function test_import_requires_a_valid_xlsx_file(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->from(route('admin.assets.index'))
            ->post(route('admin.assets.import'), [
                '_import_modal' => 'asset',
            ])
            ->assertRedirect(route('admin.assets.index'))
            ->assertSessionHasErrors('import_file');

        $this->actingAs($admin)
            ->from(route('admin.assets.index'))
            ->post(route('admin.assets.import'), [
                '_import_modal' => 'asset',
                'import_file' => UploadedFile::fake()->create('aset.csv', 10, 'text/csv'),
            ])
            ->assertRedirect(route('admin.assets.index'))
            ->assertSessionHasErrors('import_file');

        $this->actingAs($admin)
            ->from(route('admin.assets.index'))
            ->post(route('admin.assets.import'), [
                '_import_modal' => 'asset',
                'import_file' => UploadedFile::fake()->createWithContent('rusak.xlsx', 'bukan workbook Excel'),
            ])
            ->assertRedirect(route('admin.assets.index'))
            ->assertSessionHasErrors('import_file');

        $this->assertDatabaseCount('assets', 0);
        $this->assertDatabaseCount('locations', 0);
        $this->assertDatabaseCount('categories', 0);
    }

    public function test_invalid_detail_row_rolls_back_the_whole_import(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->from(route('admin.assets.index'))
            ->post(route('admin.assets.import'), [
                '_import_modal' => 'asset',
                'import_file' => $this->kirWorkbook(withInvalidRow: true),
            ])
            ->assertRedirect(route('admin.assets.index'))
            ->assertSessionHasErrors('import_file');

        $this->assertDatabaseCount('assets', 0);
        $this->assertDatabaseCount('locations', 0);
        $this->assertDatabaseCount('categories', 0);
    }

    public function test_unknown_golongan_rolls_back_the_whole_import(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->from(route('admin.assets.index'))
            ->post(route('admin.assets.import'), [
                '_import_modal' => 'asset',
                'import_file' => $this->kirWorkbook(firstAssetCode: '07.01.01.01.001'),
            ])
            ->assertRedirect(route('admin.assets.index'))
            ->assertSessionHasErrors('import_file');

        $this->assertDatabaseCount('assets', 0);
        $this->assertDatabaseCount('locations', 0);
        $this->assertDatabaseCount('categories', 0);
    }

    public function test_conflicting_category_code_rolls_back_the_whole_import(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Category::query()->create([
            'code' => '02',
            'name' => 'Kategori Lama yang Berbeda',
            'description' => 'Kategori yang tidak sesuai referensi kode barang.',
        ]);

        $this->actingAs($admin)
            ->from(route('admin.assets.index'))
            ->post(route('admin.assets.import'), [
                '_import_modal' => 'asset',
                'import_file' => $this->kirWorkbook(),
            ])
            ->assertRedirect(route('admin.assets.index'))
            ->assertSessionHasErrors('import_file');

        $this->assertDatabaseCount('assets', 0);
        $this->assertDatabaseCount('locations', 0);
        $this->assertDatabaseCount('categories', 1);
        $this->assertDatabaseHas('categories', [
            'code' => '02',
            'name' => 'Kategori Lama yang Berbeda',
        ]);
    }

    public function test_guest_and_employee_cannot_import_assets(): void
    {
        $this->post(route('admin.assets.import'), [
            'import_file' => $this->kirWorkbook(),
        ])->assertRedirect(route('login'));

        $employee = User::factory()->create(['role' => 'pegawai']);

        $this->actingAs($employee)->post(route('admin.assets.import'), [
            'import_file' => $this->kirWorkbook(),
        ])->assertForbidden();

        $this->assertDatabaseCount('assets', 0);
        $this->assertDatabaseCount('locations', 0);
        $this->assertDatabaseCount('categories', 0);
    }

    private function kirWorkbook(bool $withInvalidRow = false, ?string $firstAssetCode = null): UploadedFile
    {
        $spreadsheet = new Spreadsheet;
        $kadis = $spreadsheet->getActiveSheet();
        $kadis->setTitle('Ruangan Kadis');
        $this->setKirHeader($kadis, ': Kepala Dinas', ': 12.04.02.08.01.01');

        $kadis->fromArray([
            1,
            'AC Split',
            'LG',
            'OSNC0960NA0',
            '1 PK',
            'Plastik / Besi',
            2017,
            $firstAssetCode ?? '02.06.02.04.04',
            1,
            '=2000000+2102134.57',
            'Baik',
            '-',
            '-',
            'Unit utama',
        ], null, 'B19');
        $kadis->fromArray([
            2,
            'Dispenser',
            'Miyako',
            '-',
            '-',
            'Plastik',
            2009,
            '02.06.02.04.04',
            2,
            500000,
            '-',
            'Kurang Baik',
            '-',
            null,
        ], null, 'B20');
        $kadis->fromArray([
            3,
            'Infocus',
            'Epson',
            '-',
            '-',
            'Plastik',
            '-',
            '02.05.01.05.023',
            '1 Set',
            '-',
            '-',
            '-',
            'Rusak Berat',
            null,
        ], null, 'B21');
        $kadis->fromArray([
            'J U M L A H',
            'Jangan Diimpor',
            null,
            null,
            null,
            null,
            null,
            '99.99.99',
            99,
            '=SUM(K19:K21)',
        ], null, 'B22');

        $sekretaris = $spreadsheet->createSheet();
        $sekretaris->setTitle('Ruangan Sekretaris');
        $this->setKirHeader($sekretaris, ': S e k r e t a r i s', ': 12.04.02.08.01.02');
        $sekretaris->setCellValue('B19', 1);
        $sekretaris->setCellValue('C19', 'Sound System Ruang Rapat');
        $sekretaris->setCellValue('K19', '=20000000+900000');
        $sekretaris->fromArray([
            'a. DVD',
            'Type E360',
            '-',
            '-',
            'Besi / Kara',
            2016,
            '02.06.02.06.09',
            1,
            null,
            'Baik',
            '-',
            '-',
        ], null, 'C20');
        $sekretaris->fromArray([
            'b. Speaker',
            'Type CS-450V',
            '-',
            '10 Inch',
            'Besi / Kara',
            2016,
            '02.06.02.06.07',
            1,
            null,
            'Baik',
            '-',
            '-',
        ], null, 'C21');
        $sekretaris->fromArray([
            2,
            'Televisi',
            'Sharp',
            '-',
            '40 Inch',
            'Plastik',
            2020,
            '02.06.02.06.03',
            $withInvalidRow ? null : 1,
            6000000,
            'Baik',
            '-',
            '-',
        ], null, 'B22');
        $sekretaris->setCellValue('B23', 'J U M L A H');
        $sekretaris->setCellValue('K23', '=SUM(K19:K22)');

        $path = tempnam(sys_get_temp_dir(), 'kir-import-test-');

        if ($path === false) {
            self::fail('Tidak dapat membuat berkas sementara untuk pengujian.');
        }

        unlink($path);
        $path .= '.xlsx';
        (new Xlsx($spreadsheet))->save($path);
        $spreadsheet->disconnectWorksheets();
        $this->temporaryFiles[] = $path;

        return new UploadedFile(
            $path,
            'KIR Test.xlsx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            null,
            true,
        );
    }

    private function setKirHeader(Worksheet $worksheet, string $room, string $locationCode): void
    {
        $worksheet->setCellValue('C2', 'KARTU INVENTARIS RUANGAN');
        $worksheet->setCellValue('D11', ': Dinas Pendidikan');
        $worksheet->setCellValue('D12', $room);
        $worksheet->setCellValue('D14', $locationCode);
        $worksheet->setCellValue('C15', 'Jenis Barang / Nama Barang');
        $worksheet->setCellValue('I16', 'No. Kode Barang');
        $worksheet->setCellValue('J16', 'Jumlah Barang');
        $worksheet->setCellValue('K16', 'Harga Beli / Perolehan');
        $worksheet->setCellValue('L16', 'Baik');
        $worksheet->setCellValue('M16', 'Kurang Baik');
        $worksheet->setCellValue('N16', 'Rusak Berat');
        $worksheet->fromArray(range(1, 14), null, 'B17');
    }
}
