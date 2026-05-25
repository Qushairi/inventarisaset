<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_upload_and_view_profile_photo(): void
    {
        Storage::fake('public');

        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $response = $this->actingAs($admin)->patch(route('admin.profile.update'), [
            'profile_photo' => UploadedFile::fake()->image('avatar.jpg'),
        ]);

        $response->assertRedirect(route('admin.profile.index'));
        $response->assertSessionHas('success', 'Foto profil admin berhasil diperbarui.');

        $admin->refresh();

        $this->assertNotNull($admin->profile_photo_path);
        Storage::disk('public')->assertExists($admin->profile_photo_path);

        $this->actingAs($admin)
            ->get($admin->profilePhotoUrl())
            ->assertOk();
    }

    public function test_admin_can_upload_and_view_signature(): void
    {
        Storage::fake('public');

        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $response = $this->actingAs($admin)->patch(route('admin.profile.update'), [
            'signature_file' => UploadedFile::fake()->image('signature.png'),
        ]);

        $response->assertRedirect(route('admin.profile.index'));
        $response->assertSessionHas('success', 'Tanda tangan admin berhasil diperbarui.');

        $admin->refresh();

        $this->assertNotNull($admin->signature_path);
        $this->assertNotNull($admin->signature_updated_at);
        Storage::disk('public')->assertExists($admin->signature_path);

        $this->actingAs($admin)
            ->get($admin->signatureUrl())
            ->assertOk();
    }

    public function test_admin_can_upload_jpg_signature(): void
    {
        Storage::fake('public');

        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $response = $this->actingAs($admin)->patch(route('admin.profile.update'), [
            'signature_file' => UploadedFile::fake()->image('signature.jpg'),
        ]);

        $response->assertRedirect(route('admin.profile.index'));
        $response->assertSessionHasNoErrors();

        $admin->refresh();

        $this->assertNotNull($admin->signature_path);
        Storage::disk('public')->assertExists($admin->signature_path);
    }
}
