<?php

namespace Tests\Feature\Admin;

use App\Models\Asset;
use App\Models\Category;
use App\Models\Location;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminEditModalTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_edit_buttons_open_modals_with_existing_form_fields(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $employee = User::factory()->create(['role' => 'pegawai']);
        $category = Category::query()->create([
            'name' => 'Elektronik',
            'code' => 'ELK',
            'description' => 'Peralatan elektronik',
            'note' => 'Catatan kategori',
        ]);
        $location = Location::query()->create([
            'name' => 'Gudang Utama',
            'code' => 'GDG',
            'address' => 'Lantai 1',
            'address_note' => 'Dekat pintu masuk',
            'description' => 'Lokasi penyimpanan utama',
            'note' => 'Catatan lokasi',
        ]);
        $asset = Asset::query()->create([
            'category_id' => $category->id,
            'location_id' => $location->id,
            'name' => 'Laptop',
            'code' => 'AST-001',
            'condition' => 'Baik',
            'status' => 'Tersedia',
            'quantity' => 2,
            'acquisition_price' => 10000000,
            'acquisition_year' => 2024,
        ]);
        $menus = [
            [
                'route' => 'admin.categories.index',
                'modal' => 'adminCategoryEditModal-'.$category->id,
                'action' => route('admin.categories.update', $category),
                'fields' => ['name', 'code', 'description', 'note'],
            ],
            [
                'route' => 'admin.locations.index',
                'modal' => 'adminLocationEditModal-'.$location->id,
                'action' => route('admin.locations.update', $location),
                'fields' => ['name', 'code', 'address', 'address_note', 'description', 'note'],
            ],
            [
                'route' => 'admin.assets.index',
                'modal' => 'adminAssetEditModal-'.$asset->id,
                'action' => route('admin.assets.update', $asset),
                'fields' => ['name', 'code', 'category_id', 'location_id', 'condition', 'status', 'quantity', 'acquisition_price', 'acquisition_year', 'image_file'],
            ],
            [
                'route' => 'admin.employees.index',
                'modal' => 'adminEmployeeEditModal-'.$employee->id,
                'action' => route('admin.employees.update', $employee),
                'fields' => ['name', 'nip', 'email', 'password', 'password_confirmation'],
            ],
        ];

        foreach ($menus as $menu) {
            $response = $this->actingAs($admin)->get(route($menu['route']));

            $response->assertOk();
            $response->assertSee('data-bs-target="#'.$menu['modal'].'"', false);
            $response->assertSee('id="'.$menu['modal'].'"', false);
            $response->assertSee('class="modal-content transaction-modal is-loan"', false);
            $response->assertSee('action="'.$menu['action'].'"', false);
            $response->assertSee('name="_method" value="PUT"', false);

            foreach ($menu['fields'] as $field) {
                $response->assertSee('name="'.$field.'"', false);
            }
        }
    }

    public function test_failed_edit_validation_reopens_the_related_modal(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $category = Category::query()->create([
            'name' => 'Elektronik',
            'code' => 'ELK',
            'description' => 'Peralatan elektronik',
        ]);

        $response = $this->actingAs($admin)
            ->followingRedirects()
            ->from(route('admin.categories.index'))
            ->put(route('admin.categories.update', $category), [
                '_edit_modal' => 'category',
                '_edit_key' => $category->id,
            ]);

        $response->assertOk();
        $response->assertSee('The name field is required.');
        $response->assertSee('adminCategoryEditModal-'.$category->id, false);
        $response->assertSee('new bootstrap.Modal(modalElement).show()', false);
    }

    public function test_legacy_edit_routes_redirect_to_index_and_open_the_modal(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $category = Category::query()->create([
            'name' => 'Elektronik',
            'code' => 'ELK',
            'description' => 'Peralatan elektronik',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.categories.edit', $category))
            ->assertRedirect(route('admin.categories.index', ['edit' => $category->id]));

        $response = $this->followingRedirects()
            ->get(route('admin.categories.edit', $category));

        $response->assertOk();
        $response->assertSee('adminCategoryEditModal-'.$category->id, false);
        $response->assertSee('new bootstrap.Modal(modalElement).show()', false);
    }
}
