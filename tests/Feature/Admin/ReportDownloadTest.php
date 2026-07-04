<?php

namespace Tests\Feature\Admin;

use App\Models\Asset;
use App\Models\Category;
use App\Models\Location;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportDownloadTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_download_inventory_report_pdf(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $category = Category::query()->create([
            'name' => 'Elektronik',
            'code' => 'KTG-ELK',
            'description' => 'Kategori elektronik',
            'note' => 'Data pengujian',
        ]);

        $location = Location::query()->create([
            'name' => 'Gudang Utama',
            'code' => 'LOC-GDG',
            'address' => 'Jl. Pengujian No. 1',
            'address_note' => 'Dekat ruang admin',
            'description' => 'Lokasi penyimpanan',
            'note' => 'Data pengujian',
        ]);

        Asset::query()->create([
            'category_id' => $category->id,
            'location_id' => $location->id,
            'name' => 'Laptop Laporan',
            'code' => 'AST-RPT-001',
            'condition' => 'Baik',
            'status' => 'Tersedia',
            'acquisition_price' => 10000000,
            'acquired_at' => '2026-05-01',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.reports.download', 'inventaris'));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
        $this->assertStringContainsString(
            'laporan-inventaris-aset.pdf',
            (string) $response->headers->get('content-disposition')
        );
    }

    public function test_admin_report_page_shows_download_filters(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.reports.index'));

        $response->assertOk();
        $response->assertSee('name="category_id"', false);
        $response->assertSee('name="location_id"', false);
        $response->assertSee('name="year"', false);
        $response->assertSee('name="user_id"', false);
        $response->assertSee('name="date_from"', false);
        $response->assertSee('name="date_to"', false);
    }

    public function test_admin_inventory_report_status_filter_does_not_include_diverifikasi(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.reports.index'));

        $response->assertOk();
        $response->assertSee('value="Tersedia"', false);
        $response->assertSee('value="Dipinjam"', false);
        $response->assertSee('value="Perbaikan"', false);
        $response->assertDontSee('value="Diverifikasi"', false);
    }

    public function test_admin_can_download_filtered_inventory_report_pdf(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $category = Category::query()->create([
            'name' => 'Peralatan Kantor',
            'code' => 'KTG-KTR',
            'description' => 'Kategori kantor',
        ]);

        $location = Location::query()->create([
            'name' => 'Ruang Administrasi',
            'code' => 'LOC-ADM',
            'address' => 'Gedung Utama',
            'description' => 'Ruang administrasi',
        ]);

        Asset::query()->create([
            'category_id' => $category->id,
            'location_id' => $location->id,
            'name' => 'Printer Laporan',
            'code' => 'AST-RPT-002',
            'condition' => 'Baik',
            'status' => 'Tersedia',
            'quantity' => 1,
            'acquisition_price' => 3000000,
            'acquisition_year' => 2025,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.reports.download', [
            'type' => 'inventaris',
            'category_id' => $category->id,
            'location_id' => $location->id,
            'condition' => 'Baik',
            'status' => 'Tersedia',
            'year' => 2025,
        ]));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
    }

    public function test_report_rejects_an_invalid_date_range(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

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
}
