<?php

namespace Tests\Feature\Pegawai;

use App\Models\Asset;
use App\Models\AssetReturn;
use App\Models\Category;
use App\Models\Loan;
use App\Models\Location;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReturnRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_pegawai_can_submit_return_request_for_approved_loan(): void
    {
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
            'planned_return_date' => '2026-05-06',
            'status' => 'Disetujui',
            'status_note' => 'Disetujui admin.',
        ]);

        $response = $this->actingAs($pegawai)->post(route('pegawai.returns.store'), [
            'loan_id' => $loan->id,
            'returned_at' => '2026-05-04',
            'condition' => 'Baik',
            'report_note' => 'Aset sudah selesai dipakai.',
        ]);

        $response->assertRedirect(route('pegawai.returns.index'));
        $response->assertSessionHas('success', 'Pengembalian berhasil dikirim dan otomatis terverifikasi.');

        $this->assertDatabaseHas('asset_returns', [
            'loan_id' => $loan->id,
            'asset_id' => $asset->id,
            'user_id' => $pegawai->id,
            'returned_at' => '2026-05-04 00:00:00',
            'condition' => 'Baik',
            'verified_note' => 'Terverifikasi otomatis oleh sistem.',
            'status' => 'Terverifikasi',
            'status_note' => 'Pengembalian otomatis terverifikasi.',
            'report_note' => 'Aset sudah selesai dipakai.',
        ]);

        $this->assertNotNull(AssetReturn::query()->first()?->report_number);
        $this->assertDatabaseHas('loans', [
            'id' => $loan->id,
            'status' => 'Selesai',
        ]);
        $this->assertDatabaseHas('assets', [
            'id' => $asset->id,
            'status' => 'Tersedia',
        ]);
    }

    public function test_pegawai_cannot_submit_return_request_for_unreturnable_loan(): void
    {
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
            'planned_return_date' => '2026-05-06',
            'status' => 'Menunggu',
            'status_note' => 'Masih menunggu persetujuan.',
        ]);

        $response = $this->actingAs($pegawai)
            ->from(route('pegawai.returns.index'))
            ->post(route('pegawai.returns.store'), [
                'loan_id' => $loan->id,
                'returned_at' => '2026-05-04',
                'condition' => 'Baik',
            ]);

        $response->assertRedirect(route('pegawai.returns.index'));
        $response->assertSessionHasErrors([
            'loan_id' => 'Peminjaman yang dipilih belum dapat diajukan untuk pengembalian.',
        ], null, 'createReturn');

        $this->assertDatabaseCount('asset_returns', 0);
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
