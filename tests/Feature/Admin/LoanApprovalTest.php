<?php

namespace Tests\Feature\Admin;

use App\Models\Asset;
use App\Models\BeritaAcara;
use App\Models\Category;
use App\Models\Loan;
use App\Models\Location;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LoanApprovalTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_loan_for_admin_borrower(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'name' => 'Admin Pengelola',
            'email' => 'admin.pengelola@example.com',
        ]);
        $adminBorrower = User::factory()->create([
            'role' => 'admin',
            'name' => 'Admin Peminjam',
            'email' => 'admin.peminjam@example.com',
        ]);
        $asset = $this->createAsset();

        $this->actingAs($admin)
            ->get(route('admin.loans.index'))
            ->assertOk()
            ->assertSee('Admin Peminjam (admin.peminjam@example.com) - Admin');

        $response = $this->actingAs($admin)->post(route('admin.loans.store'), [
            'asset_id' => $asset->id,
            'user_id' => $adminBorrower->id,
            'loan_date' => '2026-05-01',
            'planned_return_date' => '2026-05-03',
            'quantity' => 1,
            'status' => 'Menunggu',
            'status_note' => 'Admin meminjam aset untuk operasional.',
        ]);

        $response->assertRedirect(route('admin.loans.index'));

        $this->assertDatabaseHas('loans', [
            'asset_id' => $asset->id,
            'user_id' => $adminBorrower->id,
            'status' => 'Menunggu',
            'status_note' => 'Admin meminjam aset untuk operasional.',
        ]);
    }

    public function test_admin_can_create_multiple_asset_loans_in_one_submission(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);
        $pegawai = User::factory()->create([
            'role' => 'pegawai',
        ]);
        $firstAsset = $this->createAsset([
            'name' => 'Laptop Presentasi',
            'code' => 'AST-BULK-001',
            'quantity' => 4,
        ]);
        $secondAsset = $firstAsset->replicate();
        $secondAsset->forceFill([
            'name' => 'Proyektor Rapat',
            'code' => 'AST-BULK-002',
            'quantity' => 3,
        ])->save();

        $response = $this->actingAs($admin)->post(route('admin.loans.store'), [
            '_create_modal' => 'loan',
            'items' => [
                ['asset_id' => $firstAsset->id, 'quantity' => 2],
                ['asset_id' => $secondAsset->id, 'quantity' => 1],
            ],
            'user_id' => $pegawai->id,
            'loan_date' => '2026-05-01',
            'planned_return_date' => '2026-05-04',
            'status' => 'Menunggu',
            'status_note' => 'Peminjaman beberapa aset.',
        ]);

        $response->assertRedirect(route('admin.loans.index'));
        $response->assertSessionHas('success', '2 data peminjaman berhasil disimpan sekaligus.');

        $this->assertDatabaseHas('loans', [
            'asset_id' => $firstAsset->id,
            'user_id' => $pegawai->id,
            'quantity' => 2,
            'status' => 'Menunggu',
        ]);
        $this->assertDatabaseHas('loans', [
            'asset_id' => $secondAsset->id,
            'user_id' => $pegawai->id,
            'quantity' => 1,
            'status' => 'Menunggu',
        ]);
        $this->assertDatabaseCount('loans', 2);
        $transactionUuids = Loan::query()->pluck('transaction_uuid');
        $this->assertNotNull($transactionUuids->first());
        $this->assertCount(1, $transactionUuids->unique());

        $this->actingAs($admin)
            ->get(route('admin.loans.index'))
            ->assertOk()
            ->assertViewHas('loans', fn ($loans) => $loans->total() === 1)
            ->assertSee('Laptop Presentasi')
            ->assertSee('Proyektor Rapat');

        $this->assertSame(4, $firstAsset->fresh()->quantity);
        $this->assertSame(3, $secondAsset->fresh()->quantity);
    }

    public function test_admin_approved_bulk_loan_updates_each_stock_and_creates_each_document(): void
    {
        Storage::fake('public');

        $admin = User::factory()->create([
            'role' => 'admin',
        ]);
        $pegawai = User::factory()->create([
            'role' => 'pegawai',
        ]);
        $firstAsset = $this->createAsset([
            'name' => 'Kamera Dokumentasi',
            'code' => 'AST-BULK-003',
            'quantity' => 4,
        ]);
        $secondAsset = $firstAsset->replicate();
        $secondAsset->forceFill([
            'name' => 'Tripod Kamera',
            'code' => 'AST-BULK-004',
            'quantity' => 3,
        ])->save();

        $response = $this->actingAs($admin)->post(route('admin.loans.store'), [
            'items' => [
                ['asset_id' => $firstAsset->id, 'quantity' => 2],
                ['asset_id' => $secondAsset->id, 'quantity' => 1],
            ],
            'user_id' => $pegawai->id,
            'loan_date' => '2026-05-02',
            'planned_return_date' => '2026-05-05',
            'status' => 'Disetujui',
            'status_note' => 'Peralatan dokumentasi kegiatan.',
        ]);

        $response->assertRedirect(route('admin.loans.index'));
        $this->assertSame(2, $firstAsset->fresh()->quantity);
        $this->assertSame(2, $secondAsset->fresh()->quantity);
        $this->assertSame(2, Loan::query()->whereNotNull('stock_applied_at')->count());
        $this->assertSame(2, BeritaAcara::query()->count());
        $this->assertSame(2, $pegawai->fresh()->notifications()->count());

        Loan::query()->get()->each(function (Loan $loan): void {
            $this->assertNotNull($loan->loan_letter_number);
            $this->assertNotNull($loan->beritaAcara?->pdf_path);
            Storage::disk('public')->assertExists($loan->beritaAcara->pdf_path);
        });
    }

    public function test_admin_bulk_loan_rolls_back_every_item_when_one_asset_exceeds_stock(): void
    {
        Storage::fake('public');

        $admin = User::factory()->create([
            'role' => 'admin',
        ]);
        $pegawai = User::factory()->create([
            'role' => 'pegawai',
        ]);
        $firstAsset = $this->createAsset([
            'name' => 'Laptop Lapangan',
            'code' => 'AST-BULK-005',
            'quantity' => 3,
        ]);
        $secondAsset = $firstAsset->replicate();
        $secondAsset->forceFill([
            'name' => 'Printer Portabel',
            'code' => 'AST-BULK-006',
            'quantity' => 1,
        ])->save();

        $response = $this->actingAs($admin)
            ->from(route('admin.loans.index'))
            ->post(route('admin.loans.store'), [
                '_create_modal' => 'loan',
                'items' => [
                    ['asset_id' => $firstAsset->id, 'quantity' => 2],
                    ['asset_id' => $secondAsset->id, 'quantity' => 2],
                ],
                'user_id' => $pegawai->id,
                'loan_date' => '2026-05-03',
                'planned_return_date' => '2026-05-06',
                'status' => 'Disetujui',
            ]);

        $response->assertRedirect(route('admin.loans.index'));
        $response->assertSessionHasErrors('items.1.quantity');
        $this->assertDatabaseCount('loans', 0);
        $this->assertSame(3, $firstAsset->fresh()->quantity);
        $this->assertSame(1, $secondAsset->fresh()->quantity);
        $this->assertSame([], Storage::disk('public')->allFiles());
        $this->assertSame(0, $pegawai->fresh()->notifications()->count());
    }

    public function test_admin_bulk_loan_rejects_duplicate_asset_selection(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);
        $pegawai = User::factory()->create([
            'role' => 'pegawai',
        ]);
        $asset = $this->createAsset([
            'quantity' => 3,
        ]);

        $response = $this->actingAs($admin)
            ->from(route('admin.loans.index'))
            ->post(route('admin.loans.store'), [
                '_create_modal' => 'loan',
                'items' => [
                    ['asset_id' => $asset->id, 'quantity' => 1],
                    ['asset_id' => $asset->id, 'quantity' => 1],
                ],
                'user_id' => $pegawai->id,
                'loan_date' => '2026-05-04',
                'planned_return_date' => '2026-05-06',
                'status' => 'Menunggu',
            ]);

        $response->assertRedirect(route('admin.loans.index'));
        $response->assertSessionHasErrors('items.0.asset_id');
        $this->assertDatabaseCount('loans', 0);
    }

    public function test_admin_bulk_loan_rolls_back_when_one_item_already_exists(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);
        $pegawai = User::factory()->create([
            'role' => 'pegawai',
        ]);
        $firstAsset = $this->createAsset([
            'name' => 'Televisi Informasi',
            'code' => 'AST-BULK-007',
            'quantity' => 2,
        ]);
        $secondAsset = $firstAsset->replicate();
        $secondAsset->forceFill([
            'name' => 'Pengeras Suara',
            'code' => 'AST-BULK-008',
            'quantity' => 2,
        ])->save();

        Loan::query()->create([
            'asset_id' => $secondAsset->id,
            'user_id' => $pegawai->id,
            'loan_date' => '2026-05-05',
            'planned_return_date' => '2026-05-07',
            'quantity' => 1,
            'status' => 'Menunggu',
        ]);

        $response = $this->actingAs($admin)
            ->from(route('admin.loans.index'))
            ->post(route('admin.loans.store'), [
                '_create_modal' => 'loan',
                'items' => [
                    ['asset_id' => $firstAsset->id, 'quantity' => 1],
                    ['asset_id' => $secondAsset->id, 'quantity' => 1],
                ],
                'user_id' => $pegawai->id,
                'loan_date' => '2026-05-05',
                'planned_return_date' => '2026-05-08',
                'status' => 'Menunggu',
            ]);

        $response->assertRedirect(route('admin.loans.index'));
        $response->assertSessionHasErrors(['loan_date', 'items.1.asset_id']);
        $response->assertSessionHasInput('items.0.asset_id', (string) $firstAsset->id);
        $response->assertSessionHasInput('items.1.asset_id', (string) $secondAsset->id);
        $this->assertDatabaseCount('loans', 1);
        $this->assertDatabaseMissing('loans', [
            'asset_id' => $firstAsset->id,
            'user_id' => $pegawai->id,
        ]);
    }

    public function test_admin_duplicate_loan_is_rejected_without_creating_another_record(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);
        $pegawai = User::factory()->create([
            'role' => 'pegawai',
        ]);
        $asset = $this->createAsset();

        Loan::query()->create([
            'asset_id' => $asset->id,
            'user_id' => $pegawai->id,
            'loan_date' => '2026-05-02',
            'planned_return_date' => '2026-05-04',
            'quantity' => 1,
            'status' => 'Menunggu',
            'status_note' => 'Peminjaman yang sudah tersimpan.',
        ]);

        $response = $this->actingAs($admin)
            ->from(route('admin.loans.index'))
            ->post(route('admin.loans.store'), [
                '_create_modal' => 'loan',
                'asset_id' => $asset->id,
                'user_id' => $pegawai->id,
                'loan_date' => '2026-05-02',
                'planned_return_date' => '2026-05-05',
                'quantity' => 2,
                'status' => 'Menunggu',
                'status_note' => 'Percobaan duplikat.',
            ]);

        $response->assertRedirect(route('admin.loans.index'));
        $response->assertSessionHasErrors([
            'loan_date' => 'Peminjaman untuk aset, peminjam, dan tanggal tersebut sudah ada. Tidak ada data baru yang disimpan.',
        ]);
        $response->assertSessionHasInput('_create_modal', 'loan');

        $this->assertDatabaseCount('loans', 1);
        $this->assertDatabaseMissing('loans', [
            'status_note' => 'Percobaan duplikat.',
        ]);

    }

    public function test_admin_failed_stock_validation_does_not_leave_a_loan_record(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);
        $pegawai = User::factory()->create([
            'role' => 'pegawai',
        ]);
        $asset = $this->createAsset([
            'quantity' => 1,
        ]);

        $response = $this->actingAs($admin)
            ->from(route('admin.loans.index'))
            ->post(route('admin.loans.store'), [
                '_create_modal' => 'loan',
                'asset_id' => $asset->id,
                'user_id' => $pegawai->id,
                'loan_date' => '2026-05-03',
                'planned_return_date' => '2026-05-05',
                'quantity' => 2,
                'status' => 'Disetujui',
                'status_note' => 'Jumlah melebihi stok.',
            ]);

        $response->assertRedirect(route('admin.loans.index'));
        $response->assertSessionHasErrors([
            'quantity' => 'Jumlah peminjaman melebihi stok aset yang tersedia.',
        ]);

        $this->assertDatabaseCount('loans', 0);
        $this->assertDatabaseHas('assets', [
            'id' => $asset->id,
            'quantity' => 1,
        ]);
    }

    public function test_admin_can_accept_pending_loan_request(): void
    {
        Storage::fake('public');

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
        $this->assertDatabaseHas('assets', [
            'id' => $asset->id,
            'status' => 'Dipinjam',
        ]);

        $loan->refresh();
        $beritaAcara = $loan->beritaAcara()->first();

        $this->assertNotNull($loan->loan_letter_number);
        $this->assertNotNull($loan->loan_letter_generated_at);
        $this->assertSame($admin->id, $loan->approved_by_user_id);
        $this->assertInstanceOf(BeritaAcara::class, $beritaAcara);
        $this->assertSame($loan->id, $beritaAcara->loan_id);
        $this->assertSame($asset->id, $beritaAcara->asset_id);
        $this->assertSame($admin->id, $beritaAcara->first_party_user_id);
        $this->assertSame($pegawai->id, $beritaAcara->second_party_user_id);
        $this->assertNotNull($beritaAcara->pdf_path);
        $this->assertStringStartsWith('SPA-', $beritaAcara->number);
        Storage::disk('public')->assertExists($beritaAcara->pdf_path);

        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $pegawai->id,
            'notifiable_type' => User::class,
        ]);

        $notification = $pegawai->fresh()->notifications()->latest()->first();

        $this->assertNotNull($notification);
        $this->assertSame('loan_approved', $notification->data['type_key']);
        $this->assertSame($beritaAcara->number, $notification->data['meta']['loan_letter_number']);
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
