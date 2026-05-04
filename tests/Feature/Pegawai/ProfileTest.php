<?php

namespace Tests\Feature\Pegawai;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_pegawai_can_upload_profile_photo(): void
    {
        Storage::fake('public');

        $user = User::factory()->create([
            'role' => 'pegawai',
        ]);

        $response = $this->actingAs($user)->patch(route('pegawai.profile.update'), [
            'profile_photo' => UploadedFile::fake()->image('avatar.jpg'),
        ]);

        $response->assertRedirect(route('pegawai.profile.index'));
        $response->assertSessionHas('success', 'Foto profil berhasil diperbarui.');

        $user->refresh();

        $this->assertNotNull($user->profile_photo_path);
        Storage::disk('public')->assertExists($user->profile_photo_path);
    }

    public function test_uploading_new_profile_photo_replaces_the_old_file(): void
    {
        Storage::fake('public');

        $oldPath = UploadedFile::fake()->image('old-avatar.jpg')->store('profile-photos', 'public');

        $user = User::factory()->create([
            'role' => 'pegawai',
            'profile_photo_path' => $oldPath,
        ]);

        $response = $this->actingAs($user)->patch(route('pegawai.profile.update'), [
            'profile_photo' => UploadedFile::fake()->image('new-avatar.jpg'),
        ]);

        $response->assertRedirect(route('pegawai.profile.index'));

        $user->refresh();

        $this->assertNotSame($oldPath, $user->profile_photo_path);
        Storage::disk('public')->assertMissing($oldPath);
        Storage::disk('public')->assertExists($user->profile_photo_path);
    }

    public function test_pegawai_can_remove_profile_photo(): void
    {
        Storage::fake('public');

        $photoPath = UploadedFile::fake()->image('avatar.jpg')->store('profile-photos', 'public');

        $user = User::factory()->create([
            'role' => 'pegawai',
            'profile_photo_path' => $photoPath,
        ]);

        $response = $this->actingAs($user)->patch(route('pegawai.profile.update'), [
            'remove_profile_photo' => '1',
        ]);

        $response->assertRedirect(route('pegawai.profile.index'));
        $response->assertSessionHas('success', 'Foto profil berhasil dihapus.');

        $user->refresh();

        $this->assertNull($user->profile_photo_path);
        Storage::disk('public')->assertMissing($photoPath);
    }

    public function test_pegawai_can_update_own_password(): void
    {
        $user = User::factory()->create([
            'role' => 'pegawai',
            'password' => 'password-lama',
        ]);

        $response = $this->actingAs($user)->put(route('pegawai.profile.password.update'), [
            'current_password' => 'password-lama',
            'password' => 'PasswordBaru123!',
            'password_confirmation' => 'PasswordBaru123!',
        ]);

        $response->assertRedirect(route('pegawai.profile.index'));
        $response->assertSessionHas('success', 'Password berhasil diperbarui.');

        $user->refresh();

        $this->assertTrue(Hash::check('PasswordBaru123!', $user->password));
    }
}
