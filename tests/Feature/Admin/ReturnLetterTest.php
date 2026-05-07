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

class ReturnLetterTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_return_index_shows_letter_button_without_edit_and_delete_actions(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $pegawai = User::factory()->create([
            'role' => 'pegawai',
        ]);

        $asset = $this->createAsset();
        $loan = $this->createLoan($asset, $pegawai);
        $returnRecord = $this->createReturnRecord($asset, $pegawai, $loan, [
            'status' => 'Menunggu Verifikasi',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.returns.index'));

        $response->assertOk();
        $response->assertSee('Lihat Surat');
        $response->assertDontSee('>Edit<', false);
        $response->assertDontSee('>Hapus<', false);
        $response->assertSee(route('admin.returns.letter.show', $returnRecord), false);
    }

    public function test_admin_can_preview_return_letter_from_returns_menu(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $pegawai = User::factory()->create([
            'role' => 'pegawai',
        ]);

        $asset = $this->createAsset([
            'name' => 'Laptop Pengembalian',
            'code' => 'AST-RET-001',
        ]);

        $loan = $this->createLoan($asset, $pegawai, [
            'status' => 'Selesai',
        ]);

        $returnRecord = $this->createReturnRecord($asset, $pegawai, $loan, [
            'status' => 'Menunggu Verifikasi',
            'report_number' => 'RET-20260508-0001',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.returns.letter.show', $returnRecord));

        $response->assertOk();
        $response->assertSee('BERITA ACARA SERAH TERIMA ASET');
        $response->assertSee('RET-20260508-0001');
        $response->assertSee('Kelola Verifikasi');
    }

    public function test_admin_can_download_return_letter_pdf(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $pegawai = User::factory()->create([
            'role' => 'pegawai',
        ]);

        $asset = $this->createAsset([
            'name' => 'Proyektor Operasional',
            'code' => 'AST-RET-002',
        ]);

        $loan = $this->createLoan($asset, $pegawai, [
            'status' => 'Selesai',
        ]);

        $returnRecord = $this->createReturnRecord($asset, $pegawai, $loan, [
            'status' => 'Terverifikasi',
            'report_number' => 'RET-20260508-0002',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.returns.letter.download', $returnRecord));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
        $this->assertStringContainsString(
            'berita-acara-serah-terima-aset',
            (string) $response->headers->get('content-disposition')
        );
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
            'code' => 'AST-LTP-RET',
            'note' => 'Aset untuk pengujian',
            'image_path' => null,
            'condition' => 'Baik',
            'status' => 'Tersedia',
            'acquisition_price' => 12000000,
            'acquired_at' => '2026-01-01',
        ], $overrides));
    }

    private function createLoan(Asset $asset, User $pegawai, array $overrides = []): Loan
    {
        return Loan::query()->create(array_merge([
            'asset_id' => $asset->id,
            'user_id' => $pegawai->id,
            'loan_date' => '2026-05-04',
            'planned_return_date' => '2026-05-06',
            'status' => 'Disetujui',
            'status_note' => 'Pengajuan peminjaman untuk pengujian.',
        ], $overrides));
    }

    private function createReturnRecord(Asset $asset, User $pegawai, Loan $loan, array $overrides = []): AssetReturn
    {
        return AssetReturn::query()->create(array_merge([
            'loan_id' => $loan->id,
            'asset_id' => $asset->id,
            'user_id' => $pegawai->id,
            'returned_at' => '2026-05-08',
            'verified_note' => 'Diterima kembali oleh admin.',
            'condition' => 'Baik',
            'status' => 'Terverifikasi',
            'status_note' => 'Pengembalian selesai untuk pengujian.',
            'report_number' => 'RET-DEFAULT-0001',
            'report_note' => 'Berita acara otomatis untuk pengujian.',
        ], $overrides));
    }
}
