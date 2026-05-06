<?php

namespace Tests\Feature\Pegawai;

use App\Models\Asset;
use App\Models\BeritaAcara;
use App\Models\Category;
use App\Models\Loan;
use App\Models\Location;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
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

    public function test_pegawai_can_view_approved_loan_letter_from_loan_history(): void
    {
        Storage::fake('public');

        $pegawai = User::factory()->create([
            'role' => 'pegawai',
        ]);

        $asset = $this->createAsset([
            'status' => 'Tersedia',
        ]);

        $loan = Loan::query()->create([
            'asset_id' => $asset->id,
            'user_id' => $pegawai->id,
            'loan_date' => '2026-05-04',
            'planned_return_date' => '2026-05-06',
            'status' => 'Disetujui',
            'status_note' => 'Dipakai untuk kegiatan lapangan.',
        ]);

        $response = $this->actingAs($pegawai)->get(route('pegawai.loans.letter.show', $loan));

        $response->assertOk();
        $response->assertSee('SURAT PEMINJAMAN ASET');
        $response->assertSee('Download PDF');

        $loan->refresh();
        $beritaAcara = $loan->beritaAcara()->first();

        $this->assertNotNull($loan->loan_letter_number);
        $this->assertInstanceOf(BeritaAcara::class, $beritaAcara);
        $this->assertNotNull($beritaAcara->pdf_path);
        Storage::disk('public')->assertExists($beritaAcara->pdf_path);
    }

    public function test_pegawai_can_download_approved_loan_letter(): void
    {
        Storage::fake('public');

        $pegawai = User::factory()->create([
            'role' => 'pegawai',
        ]);

        $asset = $this->createAsset([
            'status' => 'Tersedia',
            'code' => 'AST-LTP-003',
        ]);

        $loan = Loan::query()->create([
            'asset_id' => $asset->id,
            'user_id' => $pegawai->id,
            'loan_date' => '2026-05-11',
            'planned_return_date' => '2026-05-13',
            'status' => 'Disetujui',
            'status_note' => 'Dipakai untuk rapat koordinasi.',
        ]);

        $response = $this->actingAs($pegawai)->get(route('pegawai.loans.letter.download', $loan));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
        $this->assertStringContainsString('attachment;', (string) $response->headers->get('content-disposition'));
        $this->assertStringContainsString('surat-peminjaman-aset', (string) $response->headers->get('content-disposition'));
        $this->assertStringStartsWith('%PDF', $response->getContent());
    }

    public function test_pegawai_cannot_view_other_users_loan_letter(): void
    {
        $pegawai = User::factory()->create([
            'role' => 'pegawai',
        ]);

        $otherPegawai = User::factory()->create([
            'role' => 'pegawai',
        ]);

        $asset = $this->createAsset([
            'status' => 'Tersedia',
            'code' => 'AST-LTP-002',
        ]);

        $loan = Loan::query()->create([
            'asset_id' => $asset->id,
            'user_id' => $otherPegawai->id,
            'loan_date' => '2026-05-08',
            'planned_return_date' => '2026-05-09',
            'status' => 'Disetujui',
            'status_note' => 'Peminjaman pegawai lain.',
        ]);

        $this->actingAs($pegawai)
            ->get(route('pegawai.loans.letter.show', $loan))
            ->assertNotFound();
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
