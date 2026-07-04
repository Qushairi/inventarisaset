<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminCreateModalTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_data_menus_render_employee_style_create_modals(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $menus = [
            ['route' => 'admin.categories.index', 'modal' => 'adminCategoryCreateModal', 'fields' => ['name', 'code', 'description', 'note']],
            ['route' => 'admin.locations.index', 'modal' => 'adminLocationCreateModal', 'fields' => ['name', 'code', 'address', 'address_note', 'description', 'note']],
            ['route' => 'admin.assets.index', 'modal' => 'adminAssetCreateModal', 'fields' => ['name', 'code', 'category_id', 'location_id', 'condition', 'status', 'quantity', 'acquisition_price', 'image_file']],
            ['route' => 'admin.employees.index', 'modal' => 'adminEmployeeCreateModal', 'fields' => ['name', 'nip', 'email', 'password', 'password_confirmation']],
            ['route' => 'admin.loans.index', 'modal' => 'adminLoanCreateModal', 'fields' => ['asset_id', 'user_id', 'loan_date', 'planned_return_date', 'quantity', 'status', 'status_note']],
            ['route' => 'admin.returns.index', 'modal' => 'adminReturnCreateModal', 'fields' => ['loan_id', 'asset_id', 'user_id', 'returned_at', 'condition', 'verified_note', 'report_number', 'status_note', 'report_note']],
        ];

        foreach ($menus as $menu) {
            $response = $this->actingAs($admin)->get(route($menu['route']));

            $response->assertOk();
            $response->assertSee('data-bs-target="#'.$menu['modal'].'"', false);
            $response->assertSee('id="'.$menu['modal'].'"', false);
            $response->assertSee('modal-content transaction-modal', false);
            $response->assertSee('transaction-modal-header', false);
            $response->assertSee('transaction-modal-body', false);
            $response->assertSee('transaction-modal-footer', false);

            foreach ($menu['fields'] as $field) {
                $response->assertSee('name="'.$field.'"', false);
            }
        }
    }

    public function test_legacy_create_routes_redirect_to_index_and_open_the_modal(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.assets.create'))
            ->assertRedirect(route('admin.assets.index', ['create' => 1]));

        $response = $this->followingRedirects()
            ->get(route('admin.assets.create'));

        $response->assertOk();
        $response->assertSee('id="adminAssetCreateModal"', false);
        $response->assertSee('new bootstrap.Modal(modalElement).show()', false);
    }

    public function test_admin_asset_status_selection_does_not_include_diverifikasi(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.assets.index'));

        $response->assertOk();
        $response->assertSee('value="Tersedia"', false);
        $response->assertSee('value="Dipinjam"', false);
        $response->assertSee('value="Perbaikan"', false);
        $response->assertDontSee('value="Diverifikasi"', false);
    }
}
