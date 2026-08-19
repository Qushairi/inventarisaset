<?php

namespace Tests\Feature\Admin;

use App\Models\Asset;
use App\Models\Category;
use App\Models\Location;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssetDuplicateCodeTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_adds_identical_asset_quantity_to_the_existing_row(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $category = Category::query()->create([
            'name' => 'Peralatan dan Mesin',
            'code' => '05',
            'description' => 'Peralatan kantor',
        ]);

        $location = Location::query()->create([
            'name' => 'Audit',
            'code' => 'AUDIT',
            'address' => 'Ruang audit',
            'description' => 'Lokasi aset audit',
        ]);

        $payload = [
            'category_id' => $category->id,
            'location_id' => $location->id,
            'name' => 'Komputer Disk',
            'code' => '05.17.03.08.03',
            'serial_number' => '-',
            'size' => '-',
            'material' => 'Plastik/Besi',
            'condition' => 'Baik',
            'status' => 'Tersedia',
            'quantity' => 1,
            'acquisition_price' => 500000,
            'acquisition_year' => 2026,
        ];

        $this->actingAs($admin)
            ->post(route('admin.assets.store'), $payload)
            ->assertRedirect(route('admin.assets.index'));

        $canonicalAssetId = Asset::query()->sole()->id;

        $this->actingAs($admin)
            ->post(route('admin.assets.store'), array_merge($payload, ['quantity' => 4]))
            ->assertRedirect(route('admin.assets.index'))
            ->assertSessionHas('success', 'Aset "Komputer Disk" sudah ada. Jumlah berhasil digabung menjadi 5 unit.');

        $this->assertDatabaseCount('assets', 1);
        $this->assertDatabaseHas('assets', [
            'id' => $canonicalAssetId,
            'quantity' => 5,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.assets.index', ['search' => 'Komputer Disk']))
            ->assertOk()
            ->assertSee('<strong>5</strong> Unit', false);
    }

    public function test_admin_can_create_assets_with_the_same_code(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $category = Category::query()->create([
            'name' => 'Elektronik',
            'code' => 'ELK',
            'description' => 'Peralatan elektronik',
        ]);

        $location = Location::query()->create([
            'name' => 'Gudang',
            'code' => 'GDG',
            'address' => 'Lantai 1',
            'description' => 'Gudang utama',
        ]);

        $payload = [
            'category_id' => $category->id,
            'location_id' => $location->id,
            'name' => 'Komputer',
            'code' => '02.10.01.02.01',
            'condition' => 'Baik',
            'status' => 'Tersedia',
            'quantity' => 1,
            'acquisition_price' => 1000000,
        ];

        $this->actingAs($admin)
            ->post(route('admin.assets.store'), $payload)
            ->assertRedirect(route('admin.assets.index'));

        $this->actingAs($admin)
            ->post(route('admin.assets.store'), array_merge($payload, [
                'name' => 'Komputer Cadangan',
            ]))
            ->assertRedirect(route('admin.assets.index'));

        $this->assertSame(2, Asset::query()->where('code', '02.10.01.02.01')->count());
    }

    public function test_assets_with_same_name_and_code_remain_separate_when_their_details_differ(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $category = Category::query()->create([
            'name' => 'Peralatan',
            'code' => 'PRL',
            'description' => 'Peralatan kantor',
        ]);
        $location = Location::query()->create([
            'name' => 'Bidang PAUD',
            'code' => 'PAUD',
            'address' => 'Kantor PAUD',
            'description' => 'Lokasi bidang PAUD',
        ]);

        foreach ([
            ['quantity' => 1, 'acquisition_price' => 3120000, 'acquisition_year' => 2015, 'brand_model' => 'Brother'],
            ['quantity' => 1, 'acquisition_price' => 915000, 'acquisition_year' => 2018, 'brand_model' => 'Epson'],
            ['quantity' => 4, 'acquisition_price' => 3351650, 'acquisition_year' => 2015, 'brand_model' => 'Canon'],
        ] as $attributes) {
            Asset::query()->create(array_merge($attributes, [
                'category_id' => $category->id,
                'location_id' => $location->id,
                'name' => 'Printer',
                'code' => '02.10.02.03.003',
                'condition' => 'Baik',
                'status' => 'Tersedia',
            ]));
        }

        $this->actingAs($admin)
            ->get(route('admin.assets.index'))
            ->assertOk()
            ->assertViewHas('assets', fn ($assets) => $assets->total() === 3)
            ->assertSee('<strong>1</strong> Unit', false)
            ->assertSee('<strong>4</strong> Unit', false)
            ->assertDontSee('<strong>6</strong> Unit', false)
            ->assertSee('Merk/Model: Brother')
            ->assertSee('Merk/Model: Epson')
            ->assertSee('Merk/Model: Canon');
    }
}
