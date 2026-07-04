<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeeNipTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_employee_with_nip(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->post(route('admin.employees.store'), [
            'name' => 'Pegawai Baru',
            'nip' => '198801012010011003',
            'email' => 'pegawai.baru@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertRedirect(route('admin.employees.index'));

        $this->assertDatabaseHas('users', [
            'name' => 'Pegawai Baru',
            'nip' => '198801012010011003',
            'email' => 'pegawai.baru@example.com',
            'role' => 'pegawai',
        ]);
    }

    public function test_admin_can_update_and_search_employee_by_nip(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $employee = User::factory()->create([
            'role' => 'pegawai',
            'nip' => '198801012010011004',
        ]);

        $this->actingAs($admin)->put(route('admin.employees.update', $employee), [
            'name' => $employee->name,
            'nip' => '198801012010011005',
            'email' => $employee->email,
            'password' => '',
            'password_confirmation' => '',
        ])->assertRedirect(route('admin.employees.index'));

        $response = $this->actingAs($admin)->get(route('admin.employees.index', [
            'search' => '198801012010011005',
        ]));

        $response->assertOk();
        $response->assertSee('198801012010011005');
    }

    public function test_employee_nip_must_be_numeric_and_unique(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        User::factory()->create([
            'role' => 'pegawai',
            'nip' => '198801012010011006',
        ]);

        $this->actingAs($admin)->post(route('admin.employees.store'), [
            'name' => 'Pegawai Duplikat',
            'nip' => '198801012010011006',
            'email' => 'pegawai.duplikat@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertSessionHasErrors('nip');

        $this->actingAs($admin)->post(route('admin.employees.store'), [
            'name' => 'Pegawai NIP Tidak Valid',
            'nip' => 'NIP-123',
            'email' => 'pegawai.invalid@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertSessionHasErrors('nip');
    }
}
