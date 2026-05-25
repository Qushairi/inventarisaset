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
}
