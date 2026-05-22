<?php

namespace Tests\Feature\Admin;

use App\Models\Asset;
use App\Models\AssetReturn;
use App\Models\Category;
use App\Models\Loan;
use App\Models\Location;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReturnAssetSyncTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_created_return_is_verified_without_status_input(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $pegawai = User::factory()->create([
            'role' => 'pegawai',
        ]);

        $asset = $this->createAsset([
            'status' => 'Dipinjam',
        ]);

        $loan = Loan::query()->create([
            'asset_id' => $asset->id,
            'user_id' => $pegawai->id,
            'loan_date' => '2026-05-01',
            'planned_return_date' => '2026-05-03',
            'status' => 'Disetujui',
            'status_note' => 'Peminjaman aktif untuk pengujian.',
        ]);

        $response = $this->actingAs($admin)->post(route('admin.returns.store'), [
            'loan_id' => $loan->id,
            'asset_id' => $asset->id,
            'user_id' => $pegawai->id,
            'returned_at' => '2026-05-04',
            'verified_note' => 'Sudah dicek admin.',
            'condition' => 'Baik',
            'status_note' => 'Diterima admin.',
            'report_number' => 'RET-20260504-0002',
            'report_note' => 'Pengembalian langsung oleh admin.',
        ]);

        $response->assertRedirect(route('admin.returns.index'));
        $response->assertSessionHas('success', 'Data pengembalian berhasil disimpan.');

        $this->assertDatabaseHas('asset_returns', [
            'loan_id' => $loan->id,
            'asset_id' => $asset->id,
            'user_id' => $pegawai->id,
            'status' => 'Terverifikasi',
        ]);
        $this->assertDatabaseHas('loans', [
            'id' => $loan->id,
            'status' => 'Selesai',
        ]);
    }

    public function test_verified_return_updates_asset_condition_and_marks_loan_as_complete(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $pegawai = User::factory()->create([
            'role' => 'pegawai',
        ]);

        $asset = $this->createAsset([
            'status' => 'Dipinjam',
        ]);

        $loan = Loan::query()->create([
            'asset_id' => $asset->id,
            'user_id' => $pegawai->id,
            'loan_date' => '2026-05-01',
            'planned_return_date' => '2026-05-03',
            'status' => 'Disetujui',
            'status_note' => 'Peminjaman aktif untuk pengujian.',
        ]);

        $returnRecord = AssetReturn::query()->create([
            'loan_id' => $loan->id,
            'asset_id' => $asset->id,
            'user_id' => $pegawai->id,
            'returned_at' => '2026-05-04',
            'verified_note' => null,
            'condition' => 'Baik',
            'status' => 'Menunggu Verifikasi',
            'status_note' => 'Menunggu pengecekan admin.',
            'report_number' => 'RET-20260504-0001',
            'report_note' => 'Catatan pengembalian awal.',
        ]);

        $response = $this->actingAs($admin)->put(route('admin.returns.update', $returnRecord), [
            'loan_id' => $loan->id,
            'asset_id' => $asset->id,
            'user_id' => $pegawai->id,
            'returned_at' => '2026-05-04',
            'verified_note' => 'Sudah dicek admin.',
            'condition' => 'Rusak Berat',
            'status' => 'Terverifikasi',
            'status_note' => 'Perlu masuk proses perbaikan.',
            'report_number' => 'RET-20260504-0001',
            'report_note' => 'Monitor mengalami kerusakan saat pengembalian.',
        ]);

        $response->assertRedirect(route('admin.returns.index'));
        $response->assertSessionHas('success', 'Data pengembalian berhasil diperbarui.');

        $this->assertDatabaseHas('asset_returns', [
            'id' => $returnRecord->id,
            'condition' => 'Rusak Berat',
            'status' => 'Terverifikasi',
        ]);
        $this->assertDatabaseHas('assets', [
            'id' => $asset->id,
            'condition' => 'Rusak Berat',
            'status' => 'Perbaikan',
        ]);
        $this->assertDatabaseHas('loans', [
            'id' => $loan->id,
            'status' => 'Selesai',
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
            'code' => 'AST-RET-SYNC-001',
            'note' => 'Aset untuk pengujian',
            'image_path' => null,
            'condition' => 'Baik',
            'status' => 'Tersedia',
            'acquisition_price' => 12000000,
            'acquired_at' => '2026-01-01',
        ], $overrides));
    }
}
