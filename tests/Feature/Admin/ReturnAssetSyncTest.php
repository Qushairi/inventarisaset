<?php

namespace Tests\Feature\Admin;

use App\Models\Asset;
use App\Models\AssetReturn;
use App\Models\Category;
use App\Models\Loan;
use App\Models\Location;
use App\Models\User;
use App\Support\AssetStateService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class ReturnAssetSyncTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_from_one_multi_asset_loan_are_shown_and_verified_as_one_transaction(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $pegawai = User::factory()->create(['role' => 'pegawai']);
        $firstAsset = $this->createAsset(['name' => 'Laptop Paket', 'status' => 'Dipinjam']);
        $secondAsset = $firstAsset->replicate();
        $secondAsset->forceFill(['name' => 'Proyektor Paket', 'code' => 'AST-RET-SYNC-002'])->save();
        $transactionUuid = (string) Str::uuid();

        $loans = collect([$firstAsset, $secondAsset])->map(fn (Asset $asset) => Loan::query()->create([
            'transaction_uuid' => $transactionUuid,
            'asset_id' => $asset->id,
            'user_id' => $pegawai->id,
            'loan_date' => '2026-05-01',
            'planned_return_date' => '2026-05-03',
            'quantity' => 1,
            'status' => 'Disetujui',
        ]));

        $this->actingAs($admin)
            ->get(route('admin.returns.index'))
            ->assertOk()
            ->assertSee('Laptop Paket (AST-RET-SYNC-001), Proyektor Paket (AST-RET-SYNC-002)');

        $this->actingAs($admin)
            ->post(route('admin.returns.store'), [
                'loan_id' => $loans->first()->id,
                'returned_at' => now()->toDateString(),
                'condition' => 'Baik',
            ])
            ->assertRedirect(route('admin.returns.index'))
            ->assertSessionHas('success', '2 aset berhasil dicatat dalam satu pengembalian dan menunggu verifikasi.');

        $returns = AssetReturn::query()->get();
        $this->assertCount(2, $returns);

        $this->actingAs($admin)
            ->get(route('admin.returns.index'))
            ->assertOk()
            ->assertViewHas('returns', fn ($paginator) => $paginator->total() === 1)
            ->assertSee('Laptop Paket')
            ->assertSee('Proyektor Paket');

        $this->actingAs($admin)
            ->put(route('admin.returns.verify', $returns->first()), [
                'condition' => 'Baik',
                'verified_note' => 'Seluruh aset paket sudah diterima.',
            ])
            ->assertRedirect(route('admin.returns.index'));

        $this->assertSame(2, AssetReturn::query()->where('status', 'Terverifikasi')->count());
        $this->assertSame(2, Loan::query()->where('status', 'Selesai')->count());
    }

    public function test_admin_created_return_waits_for_verification_without_changing_stock_or_loan(): void
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
        $originalQuantity = $asset->fresh()->quantity;
        $returnedAt = now()->toDateString();

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
            'returned_at' => $returnedAt,
            'verified_note' => 'Sudah dicek admin.',
            'condition' => 'Baik',
            'status' => 'Terverifikasi',
            'status_note' => 'Diterima admin.',
            'report_note' => 'Pengembalian langsung oleh admin.',
        ]);

        $response->assertRedirect(route('admin.returns.index'));
        $response->assertSessionHas('success', 'Data pengembalian berhasil dicatat dan menunggu verifikasi.');

        $this->assertDatabaseHas('asset_returns', [
            'loan_id' => $loan->id,
            'asset_id' => $asset->id,
            'user_id' => $pegawai->id,
            'verified_note' => null,
            'status' => 'Menunggu Verifikasi',
            'stock_applied_at' => null,
        ]);

        $returnRecord = AssetReturn::query()->where('loan_id', $loan->id)->first();

        $this->assertNotNull($returnRecord);
        $this->assertMatchesRegularExpression('/^BA-\d{14}$/', $returnRecord->report_number);

        $this->assertDatabaseHas('loans', [
            'id' => $loan->id,
            'status' => 'Disetujui',
            'stock_applied_at' => null,
        ]);
        $this->assertDatabaseHas('assets', [
            'id' => $asset->id,
            'status' => 'Dipinjam',
            'quantity' => $originalQuantity,
        ]);
    }

    public function test_verified_return_moves_stock_to_asset_with_same_code_and_marks_loan_as_complete(): void
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
            'planned_return_date' => '2026-05-03',
            'status' => 'Disetujui',
            'status_note' => 'Peminjaman aktif untuk pengujian.',
        ]);

        $returnRecord = AssetReturn::query()->create([
            'loan_id' => $loan->id,
            'asset_id' => $asset->id,
            'user_id' => $pegawai->id,
            'returned_at' => $returnedAt,
            'verified_note' => null,
            'condition' => 'Baik',
            'status' => 'Menunggu Verifikasi',
            'status_note' => 'Menunggu pengecekan admin.',
            'report_number' => 'BA-20260504000001',
            'report_note' => 'Catatan pengembalian awal.',
        ]);

        $invalidResponse = $this->actingAs($admin)
            ->from(route('admin.returns.index'))
            ->put(route('admin.returns.verify', $returnRecord), [
                'verified_note' => 'Belum memilih kondisi.',
            ]);

        $invalidResponse->assertRedirect(route('admin.returns.index'));
        $invalidResponse->assertSessionHasErrors('condition');
        $this->assertSame('Menunggu Verifikasi', $returnRecord->fresh()->status);
        $this->assertNull($returnRecord->fresh()->stock_applied_at);
        $this->assertSame('Disetujui', $loan->fresh()->status);

        $response = $this->actingAs($admin)->put(route('admin.returns.verify', $returnRecord), [
            'verified_note' => 'Sudah dicek admin.',
            'condition' => 'Rusak Berat',
        ]);

        $response->assertRedirect(route('admin.returns.index'));
        $response->assertSessionHas('success', 'Pengembalian berhasil diverifikasi.');

        $this->assertDatabaseHas('asset_returns', [
            'id' => $returnRecord->id,
            'verified_note' => 'Sudah dicek admin.',
            'condition' => 'Rusak Berat',
            'status' => 'Terverifikasi',
            'status_note' => 'Pengembalian telah diverifikasi dan aset masuk proses perbaikan.',
        ]);
        $this->assertDatabaseHas('assets', [
            'code' => $asset->code,
            'condition' => 'Rusak Berat',
            'status' => 'Perbaikan',
            'quantity' => 1,
        ]);
        $this->assertDatabaseMissing('assets', [
            'code' => $asset->code.'-RB1',
        ]);
        $this->assertDatabaseHas('loans', [
            'id' => $loan->id,
            'status' => 'Selesai',
        ]);

        $returnRecord->refresh();
        $loan->refresh();

        $this->assertNotNull($returnRecord->stock_applied_at);
        $this->assertNotNull($loan->stock_applied_at);
        $this->assertSame('return_verified', $pegawai->fresh()->notifications()->latest()->first()?->data['type_key']);

        $stockAsset = Asset::query()->findOrFail($returnRecord->stock_asset_id);
        $stockQuantity = $stockAsset->quantity;

        $secondResponse = $this->actingAs($admin)->put(route('admin.returns.verify', $returnRecord), [
            'verified_note' => 'Verifikasi kedua.',
            'condition' => 'Rusak Berat',
        ]);

        $secondResponse->assertRedirect(route('admin.returns.index'));
        $secondResponse->assertSessionHas('error', 'Pengembalian sudah diverifikasi atau peminjamannya tidak lagi aktif.');
        $this->assertSame($stockQuantity, $stockAsset->fresh()->quantity);
        $this->assertSame(1, $pegawai->fresh()->notifications()
            ->get()
            ->filter(fn ($notification) => data_get($notification->data, 'type_key') === 'return_verified')
            ->count());
    }

    public function test_regular_edit_cannot_bypass_pending_return_verification(): void
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
            'planned_return_date' => '2026-05-03',
            'status' => 'Disetujui',
        ]);
        $returnRecord = AssetReturn::query()->create([
            'loan_id' => $loan->id,
            'asset_id' => $asset->id,
            'user_id' => $pegawai->id,
            'returned_at' => $returnedAt,
            'condition' => 'Baik',
            'status' => 'Menunggu Verifikasi',
            'status_note' => 'Menunggu verifikasi admin.',
            'report_number' => 'BA-20260504000002',
        ]);

        $response = $this->actingAs($admin)->put(route('admin.returns.update', $returnRecord), [
            'loan_id' => $loan->id,
            'asset_id' => $asset->id,
            'user_id' => $pegawai->id,
            'returned_at' => $returnedAt,
            'verified_note' => 'Mencoba melewati verifikasi.',
            'condition' => 'Baik',
            'status' => 'Terverifikasi',
            'status_note' => 'Mencoba selesai lewat edit.',
            'report_number' => 'BA-20260504000002',
        ]);

        $response->assertRedirect(route('admin.returns.index'));
        $this->assertDatabaseHas('asset_returns', [
            'id' => $returnRecord->id,
            'verified_note' => null,
            'status' => 'Menunggu Verifikasi',
            'stock_applied_at' => null,
        ]);
        $this->assertDatabaseHas('loans', [
            'id' => $loan->id,
            'status' => 'Disetujui',
        ]);
    }

    public function test_old_automatic_report_numbers_and_notification_copies_are_normalized(): void
    {
        $pegawai = User::factory()->create([
            'role' => 'pegawai',
        ]);
        $asset = $this->createAsset();

        $firstReturn = AssetReturn::query()->create([
            'asset_id' => $asset->id,
            'user_id' => $pegawai->id,
            'returned_at' => '2026-07-13',
            'condition' => 'Baik',
            'status' => 'Terverifikasi',
            'report_number' => 'RET-20260713034418-UH7G',
        ]);
        $secondReturn = AssetReturn::query()->create([
            'asset_id' => $asset->id,
            'user_id' => $pegawai->id,
            'returned_at' => '2026-07-13',
            'condition' => 'Baik',
            'status' => 'Terverifikasi',
            'report_number' => 'RET-20260713034418-AB12',
        ]);
        $notificationId = (string) Str::uuid();

        DB::table('notifications')->insert([
            'id' => $notificationId,
            'type' => 'return-test',
            'notifiable_type' => User::class,
            'notifiable_id' => $pegawai->id,
            'data' => json_encode([
                'meta' => [
                    'return_id' => $firstReturn->id,
                    'report_number' => $firstReturn->report_number,
                ],
            ]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $migration = require database_path('migrations/2026_07_13_000003_normalize_asset_return_report_numbers.php');
        $migration->up();

        $this->assertSame('BA-20260713034418', $firstReturn->fresh()->report_number);
        $this->assertSame('BA-20260713034419', $secondReturn->fresh()->report_number);

        $notificationData = json_decode((string) DB::table('notifications')->where('id', $notificationId)->value('data'), true);

        $this->assertSame('BA-20260713034418', data_get($notificationData, 'meta.report_number'));
    }

    public function test_admin_asset_edit_can_override_latest_return_condition(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $pegawai = User::factory()->create([
            'role' => 'pegawai',
        ]);

        $asset = $this->createAsset([
            'condition' => 'Rusak Berat',
            'status' => 'Perbaikan',
        ]);

        AssetReturn::query()->create([
            'loan_id' => null,
            'asset_id' => $asset->id,
            'user_id' => $pegawai->id,
            'returned_at' => '2026-05-04',
            'verified_note' => 'Sudah dicek admin.',
            'condition' => 'Rusak Berat',
            'status' => 'Terverifikasi',
            'status_note' => 'Perlu perbaikan.',
            'report_number' => 'BA-20260504000003',
            'report_note' => 'Kondisi awal rusak berat.',
        ]);

        $response = $this->actingAs($admin)->put(route('admin.assets.update', $asset), [
            'category_id' => $asset->category_id,
            'location_id' => $asset->location_id,
            'name' => $asset->name,
            'code' => $asset->code,
            'note' => $asset->note,
            'condition' => 'Rusak Ringan',
            'status' => 'Perbaikan',
            'quantity' => $asset->quantity ?: 1,
            'acquisition_price' => $asset->acquisition_price,
            'acquired_at' => optional($asset->acquired_at)->format('Y-m-d'),
        ]);

        $response->assertRedirect(route('admin.assets.index'));

        $asset->refresh();
        $resolvedState = app(AssetStateService::class)->resolveState($asset);

        $this->assertSame('Rusak Ringan', $asset->condition);
        $this->assertSame('Rusak Ringan', $resolvedState['condition']);
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
