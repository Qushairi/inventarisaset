<?php

namespace Tests\Feature\Pegawai;

use App\Models\Asset;
use App\Models\Category;
use App\Models\Location;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoanRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_pegawai_can_submit_loan_request(): void
    {
        $pegawai = User::factory()->create([
            'role' => 'pegawai',
        ]);

        $asset = $this->createAsset([
            'status' => 'Tersedia',
        ]);

        $response = $this->actingAs($pegawai)->post(route('pegawai.loans.store'), [
            'asset_id' => $asset->id,
            'loan_date' => '2026-05-04',
            'planned_return_date' => '2026-05-06',
            'status_note' => 'Digunakan untuk kegiatan operasional.',
        ]);

        $response->assertRedirect(route('pegawai.loans.index'));
        $response->assertSessionHas('success', 'Pengajuan peminjaman berhasil dikirim dan menunggu persetujuan admin.');

        $this->assertDatabaseHas('loans', [
            'asset_id' => $asset->id,
            'user_id' => $pegawai->id,
            'loan_date' => '2026-05-04 00:00:00',
            'planned_return_date' => '2026-05-06 00:00:00',
            'status' => 'Menunggu',
            'status_note' => 'Digunakan untuk kegiatan operasional.',
        ]);
    }

    public function test_pegawai_cannot_submit_request_for_unavailable_asset(): void
    {
        $pegawai = User::factory()->create([
            'role' => 'pegawai',
        ]);

        $asset = $this->createAsset([
            'status' => 'Dipinjam',
        ]);

        $response = $this->actingAs($pegawai)->from(route('pegawai.loans.index'))->post(route('pegawai.loans.store'), [
            'asset_id' => $asset->id,
            'loan_date' => '2026-05-04',
            'planned_return_date' => '2026-05-06',
        ]);

        $response->assertRedirect(route('pegawai.loans.index'));
        $response->assertSessionHasErrors([
            'asset_id' => 'Aset yang dipilih sedang tidak tersedia untuk dipinjam.',
        ], null, 'createLoan');

        $this->assertDatabaseMissing('loans', [
            'asset_id' => $asset->id,
            'user_id' => $pegawai->id,
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
