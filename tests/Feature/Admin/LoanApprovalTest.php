<?php

namespace Tests\Feature\Admin;

use App\Models\Asset;
use App\Models\Category;
use App\Models\Loan;
use App\Models\Location;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoanApprovalTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_accept_pending_loan_request(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $pegawai = User::factory()->create([
            'role' => 'pegawai',
        ]);

        $asset = $this->createAsset();

        $loan = Loan::query()->create([
            'asset_id' => $asset->id,
            'user_id' => $pegawai->id,
            'loan_date' => '2026-05-04',
            'planned_return_date' => '2026-05-06',
            'status' => 'Menunggu',
            'status_note' => 'Butuh laptop untuk presentasi.',
        ]);

        $response = $this->actingAs($admin)->put(route('admin.loans.status', $loan), [
            'status' => 'Disetujui',
        ]);

        $response->assertRedirect(route('admin.loans.index'));
        $response->assertSessionHas('success', 'Pengajuan peminjaman berhasil diterima.');

        $this->assertDatabaseHas('loans', [
            'id' => $loan->id,
            'status' => 'Disetujui',
            'status_note' => 'Butuh laptop untuk presentasi.',
        ]);

        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $pegawai->id,
            'notifiable_type' => User::class,
        ]);

        $notification = $pegawai->fresh()->notifications()->latest()->first();

        $this->assertNotNull($notification);
        $this->assertSame('loan_approved', $notification->data['type_key']);
    }

    public function test_admin_can_reject_pending_loan_request(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $pegawai = User::factory()->create([
            'role' => 'pegawai',
        ]);

        $asset = $this->createAsset([
            'code' => 'AST-LTP-002',
        ]);

        $loan = Loan::query()->create([
            'asset_id' => $asset->id,
            'user_id' => $pegawai->id,
            'loan_date' => '2026-05-07',
            'planned_return_date' => '2026-05-09',
            'status' => 'Menunggu',
            'status_note' => 'Butuh proyektor untuk rapat.',
        ]);

        $response = $this->actingAs($admin)->put(route('admin.loans.status', $loan), [
            'status' => 'Ditolak',
        ]);

        $response->assertRedirect(route('admin.loans.index'));
        $response->assertSessionHas('success', 'Pengajuan peminjaman berhasil ditolak.');

        $this->assertDatabaseHas('loans', [
            'id' => $loan->id,
            'status' => 'Ditolak',
            'status_note' => 'Butuh proyektor untuk rapat.',
        ]);

        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $pegawai->id,
            'notifiable_type' => User::class,
        ]);

        $notification = $pegawai->fresh()->notifications()->latest()->first();

        $this->assertNotNull($notification);
        $this->assertSame('loan_rejected', $notification->data['type_key']);
    }

    public function test_admin_cannot_reprocess_non_pending_loan_request(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $pegawai = User::factory()->create([
            'role' => 'pegawai',
        ]);

        $asset = $this->createAsset([
            'code' => 'AST-LTP-003',
        ]);

        $loan = Loan::query()->create([
            'asset_id' => $asset->id,
            'user_id' => $pegawai->id,
            'loan_date' => '2026-05-10',
            'planned_return_date' => '2026-05-12',
            'status' => 'Disetujui',
            'status_note' => 'Pengajuan lama yang sudah diproses.',
        ]);

        $response = $this->actingAs($admin)->from(route('admin.loans.index'))->put(route('admin.loans.status', $loan), [
            'status' => 'Ditolak',
        ]);

        $response->assertRedirect(route('admin.loans.index'));
        $response->assertSessionHas('error', 'Pengajuan peminjaman ini sudah diproses sebelumnya.');

        $this->assertDatabaseHas('loans', [
            'id' => $loan->id,
            'status' => 'Disetujui',
        ]);
    }

    private function createAsset(array $overrides = []): Asset
    {
        $category = Category::query()->create([
            'name' => 'Elektronik',
            'code' => 'KTG-ELK',
            'description' => 'Kategori elektronik',
            'note' => 'Untuk pengujian',
        ]);

        $location = Location::query()->create([
            'name' => 'Gudang Utama',
            'code' => 'LOC-GDG',
            'address' => 'Jl. Pengujian No. 1',
            'address_note' => 'Dekat ruang admin',
            'description' => 'Lokasi penyimpanan utama',
            'note' => 'Untuk pengujian',
        ]);

        return Asset::query()->create(array_merge([
            'category_id' => $category->id,
            'location_id' => $location->id,
            'name' => 'Laptop Operasional',
            'code' => 'AST-LTP-001',
            'note' => 'Aset untuk pengujian',
            'image_path' => null,
            'condition' => 'Baik',
            'status' => 'Tersedia',
            'acquisition_price' => 12000000,
            'acquired_at' => '2026-01-01',
        ], $overrides));
    }
}
