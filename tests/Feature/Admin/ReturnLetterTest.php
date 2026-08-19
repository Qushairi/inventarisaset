<?php

namespace Tests\Feature\Admin;

use App\Models\Asset;
use App\Models\AssetReturn;
use App\Models\Category;
use App\Models\Loan;
use App\Models\Location;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
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
            'verified_note' => null,
            'status' => 'Menunggu Verifikasi',
            'status_note' => 'Menunggu verifikasi admin.',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.returns.index'));

        $response->assertOk();
        $response->assertSee('Lihat Surat');
        $response->assertDontSee('>Edit<', false);
        $response->assertDontSee('>Hapus<', false);
        $response->assertSee(route('admin.returns.letter.show', $returnRecord), false);
        $response->assertSee(route('admin.returns.verify', $returnRecord), false);
        $response->assertSee('data-bs-target="#adminReturnVerifyModal-'.$returnRecord->id.'"', false);
        $response->assertSee('Kondisi Setelah Diperiksa');

        $returnRecord->update([
            'verified_note' => 'Sudah diterima admin.',
            'status' => 'Terverifikasi',
            'status_note' => 'Pengembalian telah diverifikasi admin.',
        ]);

        $verifiedResponse = $this->actingAs($admin)->get(route('admin.returns.index'));

        $verifiedResponse->assertOk();
        $verifiedResponse->assertDontSee(route('admin.returns.verify', $returnRecord), false);
    }

    public function test_returned_loan_moves_from_admin_loans_to_return_history(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $pegawai = User::factory()->create([
            'name' => 'Pegawai Riwayat',
            'role' => 'pegawai',
        ]);

        $asset = $this->createAsset([
            'name' => 'Laptop Riwayat Pengembalian',
            'code' => 'AST-HISTORY-001',
        ]);
        $loan = $this->createLoan($asset, $pegawai, [
            'quantity' => 2,
            'status' => 'Selesai',
        ]);
        $this->createReturnRecord($asset, $pegawai, $loan);

        $loanResponse = $this->actingAs($admin)->get(route('admin.loans.index'));
        $returnResponse = $this->actingAs($admin)->get(route('admin.returns.index'));

        $loanResponse->assertOk();
        $this->assertSame(0, $loanResponse->viewData('loans')->total());

        $returnResponse->assertOk();
        $returnResponse->assertSee('Riwayat Pengembalian');
        $returnResponse->assertSee('Laptop Riwayat Pengembalian');
        $returnResponse->assertSee('Pegawai Riwayat');
        $returnResponse->assertSee('Pinjam: 04/05/2026');
        $returnResponse->assertSee('Kembali: 08/05/2026');
        $returnResponse->assertSee('Jumlah: 2');
    }

    public function test_admin_can_preview_return_letter_from_returns_menu(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $pegawai = User::factory()->create([
            'role' => 'pegawai',
            'nip' => '198801012010011002',
        ]);

        $asset = $this->createAsset([
            'name' => 'Laptop Pengembalian',
            'code' => 'AST-RET-001',
            'brand_model' => 'Lenovo ThinkPad T14',
            'note' => 'Keterangan asli aset',
            'acquisition_year' => 2024,
        ]);

        $loan = $this->createLoan($asset, $pegawai, [
            'status' => 'Selesai',
        ]);

        $returnRecord = $this->createReturnRecord($asset, $pegawai, $loan, [
            'status' => 'Menunggu Verifikasi',
            'report_number' => 'BA-20260508000001',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.returns.letter.show', $returnRecord));

        $response->assertOk();
        $response->assertSee('BERITA ACARA SERAH TERIMA ASET');
        $response->assertSee('BA-20260508000001');
        $response->assertSee('198801012010011002');
        $response->assertSee('Tanggal Peminjaman');
        $response->assertSee('Tanggal Pengembalian');
        $response->assertSee('Lenovo ThinkPad T14');
        $response->assertDontSee('Keterangan asli aset');
        $response->assertSee('04/05/2026');
        $response->assertSee('08/05/2026');
        $response->assertDontSee('Edit Data');
    }

    public function test_one_return_letter_lists_all_assets_from_the_same_loan_transaction(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $pegawai = User::factory()->create(['role' => 'pegawai']);
        $firstAsset = $this->createAsset(['name' => 'Meja Biro Surat', 'code' => 'AST-LETTER-001']);
        $secondAsset = $firstAsset->replicate();
        $secondAsset->forceFill(['name' => 'Kursi Besi Surat', 'code' => 'AST-LETTER-002'])->save();
        $transactionUuid = (string) Str::uuid();

        $firstLoan = $this->createLoan($firstAsset, $pegawai, [
            'transaction_uuid' => $transactionUuid,
            'status' => 'Selesai',
        ]);
        $secondLoan = $this->createLoan($secondAsset, $pegawai, [
            'transaction_uuid' => $transactionUuid,
            'status' => 'Selesai',
        ]);
        $firstReturn = $this->createReturnRecord($firstAsset, $pegawai, $firstLoan, [
            'report_number' => 'BA-20260508000011',
        ]);
        $this->createReturnRecord($secondAsset, $pegawai, $secondLoan, [
            'report_number' => 'BA-20260508000012',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.returns.letter.show', $firstReturn))
            ->assertOk()
            ->assertSee('MEJA BIRO SURAT')
            ->assertSee('KURSI BESI SURAT')
            ->assertSee('Tanggal Peminjaman')
            ->assertSee('Tanggal Pengembalian')
            ->assertSee('04/05/2026')
            ->assertSee('08/05/2026')
            ->assertDontSee('Asal Pengadaan')
            ->assertDontSee('Harga')
            ->assertSeeInOrder(['class="center">1', 'class="center">2'], false);
    }

    public function test_admin_can_download_return_letter_pdf(): void
    {
        $admin = User::factory()->create([
            'name' => 'Administrator Pengelola Barang Dinas Pendidikan',
            'role' => 'admin',
        ]);

        $pegawai = User::factory()->create([
            'name' => 'Muhammad Fadhil Pratama Saputra',
            'role' => 'pegawai',
        ]);

        $asset = $this->createAsset([
            'name' => 'Proyektor Multimedia Ruang Pertemuan Utama',
            'code' => 'AST-RET-002',
            'note' => 'Epson Full HD Wireless Presentation Projector',
        ]);

        $loan = $this->createLoan($asset, $pegawai, [
            'status' => 'Selesai',
        ]);

        $returnRecord = $this->createReturnRecord($asset, $pegawai, $loan, [
            'status' => 'Terverifikasi',
            'report_number' => 'BA-20260508000002',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.returns.letter.download', $returnRecord));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
        $this->assertSame(
            1,
            preg_match_all('/\/Type\s*\/Page\b/', $response->getContent()),
            'Surat berita acara pengembalian harus dimuat dalam satu halaman PDF.'
        );
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
            'report_number' => 'BA-20260508000003',
            'report_note' => 'Berita acara otomatis untuk pengujian.',
        ], $overrides));
    }
}
