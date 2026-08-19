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
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $pegawai = User::factory()->create([
            'role' => 'pegawai',
        ]);

        $asset = $this->createAsset([
            'status' => 'Dipinjam',
        ]);
        $returnedAt = now()->toDateString();

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
            'returned_at' => $returnedAt,
            'condition' => 'Baik',
            'report_note' => 'Aset sudah selesai dipakai.',
        ]);

        $response->assertRedirect(route('pegawai.returns.index'));
        $response->assertSessionHas('success', 'Pengajuan pengembalian berhasil dikirim dan menunggu verifikasi admin.');

        $this->assertDatabaseHas('asset_returns', [
            'loan_id' => $loan->id,
            'asset_id' => $asset->id,
            'user_id' => $pegawai->id,
            'returned_at' => $returnedAt.' 00:00:00',
            'condition' => 'Baik',
            'verified_note' => null,
            'status' => 'Menunggu Verifikasi',
            'status_note' => 'Menunggu verifikasi admin.',
            'stock_asset_id' => null,
            'stock_applied_at' => null,
            'report_note' => 'Aset sudah selesai dipakai.',
        ]);

        $this->assertMatchesRegularExpression(
            '/^BA-\d{14}$/',
            AssetReturn::query()->first()?->report_number
        );
        $this->assertDatabaseHas('loans', [
            'id' => $loan->id,
            'status' => 'Disetujui',
        ]);
        $this->assertDatabaseHas('assets', [
            'id' => $asset->id,
            'status' => 'Dipinjam',
        ]);

        $adminNotification = $admin->fresh()->notifications()->latest()->first();

        $this->assertNotNull($adminNotification);
        $this->assertSame('admin_return_request', $adminNotification->data['type_key']);
        $this->assertSame(0, $pegawai->fresh()->notifications()->count());

        $duplicateResponse = $this->actingAs($pegawai)
            ->from(route('pegawai.returns.index'))
            ->post(route('pegawai.returns.store'), [
                'loan_id' => $loan->id,
                'returned_at' => $returnedAt,
                'condition' => 'Baik',
            ]);

        $duplicateResponse->assertRedirect(route('pegawai.returns.index'));
        $duplicateResponse->assertSessionHasErrors([
            'loan_id' => 'Peminjaman yang dipilih belum dapat diajukan untuk pengembalian.',
        ], null, 'createReturn');
        $this->assertDatabaseCount('asset_returns', 1);
        $this->assertSame(1, $admin->fresh()->notifications()->count());
    }

    public function test_pegawai_cannot_submit_return_request_for_unreturnable_loan(): void
    {
        $pegawai = User::factory()->create([
            'role' => 'pegawai',
        ]);

        $asset = $this->createAsset([
            'status' => 'Dipinjam',
        ]);
        $returnedAt = now()->toDateString();

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
                'returned_at' => $returnedAt,
                'condition' => 'Baik',
            ]);

        $response->assertRedirect(route('pegawai.returns.index'));
        $response->assertSessionHasErrors([
            'loan_id' => 'Peminjaman yang dipilih belum dapat diajukan untuk pengembalian.',
        ], null, 'createReturn');

        $this->assertDatabaseCount('asset_returns', 0);
    }

    public function test_pegawai_cannot_submit_return_request_with_past_return_date(): void
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
            'loan_date' => now()->subDays(3)->toDateString(),
            'planned_return_date' => now()->addDay()->toDateString(),
            'status' => 'Disetujui',
            'status_note' => 'Disetujui admin.',
        ]);

        $response = $this->actingAs($pegawai)
            ->from(route('pegawai.returns.index'))
            ->post(route('pegawai.returns.store'), [
                'loan_id' => $loan->id,
                'returned_at' => now()->subDay()->toDateString(),
                'condition' => 'Baik',
            ]);

        $response->assertRedirect(route('pegawai.returns.index'));
        $response->assertSessionHasErrors(['returned_at'], null, 'createReturn');

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
