<?php

namespace Tests\Feature\Pegawai;

use App\Models\Asset;
use App\Models\Category;
use App\Models\Loan;
use App\Models\Location;
use App\Models\User;
use App\Notifications\PegawaiDatabaseNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class NotificationCenterTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $compiledPath = storage_path('framework/testing/views');

        if (! is_dir($compiledPath)) {
            mkdir($compiledPath, 0777, true);
        }

        config()->set('view.compiled', $compiledPath);
        app()->forgetInstance('blade.compiler');
    }

    public function test_notification_routes_exist_and_pegawai_can_mark_notification_as_read(): void
    {
        $pegawai = User::factory()->create([
            'role' => 'pegawai',
        ]);

        $pegawai->notify(new PegawaiDatabaseNotification([
            'dedupe_key' => 'test-loan-approved',
            'type_key' => 'loan_approved',
            'title' => 'Peminjaman disetujui',
            'message' => 'Pengajuan peminjaman Anda telah disetujui admin.',
            'action_label' => 'Lihat peminjaman',
            'action_url' => route('pegawai.loans.index', absolute: false),
            'icon' => 'check-circle',
            'variant' => 'success',
            'reference_type' => 'loan',
            'reference_id' => 99,
            'occurred_at' => now()->toIso8601String(),
            'meta' => [
                'asset_name' => 'Laptop Operasional',
                'asset_code' => 'AST-LTP-001',
            ],
        ]));

        $this->assertTrue(Route::has('pegawai.notifications.index'));
        $this->assertTrue(Route::has('pegawai.notifications.show'));
        $this->assertTrue(Route::has('pegawai.notifications.read-all'));

        $notification = $pegawai->fresh()->notifications()->latest()->first();

        $openResponse = $this->actingAs($pegawai)->get(route('pegawai.notifications.show', $notification));

        $openResponse->assertRedirect(route('pegawai.loans.index'));

        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function test_reminder_command_sends_due_and_overdue_notifications_to_pegawai(): void
    {
        Carbon::setTestNow('2026-05-06 07:00:00');

        $pegawai = User::factory()->create([
            'role' => 'pegawai',
        ]);

        $asset = $this->createAsset();

        Loan::query()->create([
            'asset_id' => $asset->id,
            'user_id' => $pegawai->id,
            'loan_date' => '2026-05-04',
            'planned_return_date' => '2026-05-07',
            'status' => 'Disetujui',
            'status_note' => 'Laptop untuk operasional.',
        ]);

        Loan::query()->create([
            'asset_id' => $asset->id,
            'user_id' => $pegawai->id,
            'loan_date' => '2026-05-03',
            'planned_return_date' => '2026-05-06',
            'status' => 'Disetujui',
            'status_note' => 'Laptop untuk rapat.',
        ]);

        Loan::query()->create([
            'asset_id' => $asset->id,
            'user_id' => $pegawai->id,
            'loan_date' => '2026-05-01',
            'planned_return_date' => '2026-05-05',
            'status' => 'Disetujui',
            'status_note' => 'Laptop untuk presentasi.',
        ]);

        $this->artisan('notifications:pegawai-reminders')
            ->assertExitCode(0);

        $notificationTypes = $pegawai->fresh()->notifications()
            ->latest()
            ->get()
            ->pluck('data.type_key')
            ->all();

        $this->assertContains('loan_due_soon', $notificationTypes);
        $this->assertContains('loan_due_today', $notificationTypes);
        $this->assertContains('loan_overdue', $notificationTypes);

        Carbon::setTestNow();
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
