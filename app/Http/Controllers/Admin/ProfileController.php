<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Models\AssetReturn;
use App\Models\Loan;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;

class ProfileController extends Controller
{
    public function index()
    {
        $admin = $this->currentAdmin();

        return view('admin.profile.index', [
            'adminUser' => $admin,
            'adminStats' => [
                [
                    'label' => 'Total Aset',
                    'value' => Asset::query()->count(),
                    'icon' => 'box-seam',
                    'variant' => 'primary',
                ],
                [
                    'label' => 'Peminjaman Menunggu',
                    'value' => Loan::query()->where('status', 'Menunggu')->count(),
                    'icon' => 'hourglass-split',
                    'variant' => 'warning',
                ],
                [
                    'label' => 'Pengembalian Menunggu',
                    'value' => AssetReturn::query()->where('status', 'Menunggu Verifikasi')->count(),
                    'icon' => 'arrow-counterclockwise',
                    'variant' => 'info',
                ],
                [
                    'label' => 'Total Pegawai',
                    'value' => User::query()->where('role', 'pegawai')->count(),
                    'icon' => 'people',
                    'variant' => 'success',
                ],
            ],
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $admin = $this->currentAdmin();

        if ($request->boolean('remove_profile_photo')) {
            $this->deleteStoredFile($admin->profile_photo_path);

            $admin->update([
                'profile_photo_path' => null,
            ]);

            return redirect()
                ->route('admin.profile.index')
                ->with('success', 'Foto profil admin berhasil dihapus.');
        }

        if ($request->boolean('remove_signature')) {
            $this->deleteStoredFile($admin->signature_path);

            $admin->update([
                'signature_path' => null,
                'signature_updated_at' => now(),
            ]);

            return redirect()
                ->route('admin.profile.index')
                ->with('success', 'Tanda tangan admin berhasil dihapus.');
        }

        if ($request->hasFile('signature_file')) {
            $validated = $request->validateWithBag('updateSignature', [
                'signature_file' => ['required', 'image', 'mimes:png', 'max:2048'],
            ]);

            $this->deleteStoredFile($admin->signature_path);

            $path = $validated['signature_file']->store('signatures', 'public');

            $admin->update([
                'signature_path' => $path,
                'signature_updated_at' => now(),
            ]);

            return redirect()
                ->route('admin.profile.index')
                ->with('success', 'Tanda tangan admin berhasil diperbarui.');
        }

        $validated = $request->validateWithBag('updatePhoto', [
            'profile_photo' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        $this->deleteStoredFile($admin->profile_photo_path);

        $path = $validated['profile_photo']->store('profile-photos', 'public');

        $admin->update([
            'profile_photo_path' => $path,
        ]);

        return redirect()
            ->route('admin.profile.index')
            ->with('success', 'Foto profil admin berhasil diperbarui.');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $admin = $this->currentAdmin();

        $validated = $request->validateWithBag('updatePassword', [
            'current_password' => ['required', 'current_password'],
            'password' => ['required', Password::defaults(), 'confirmed', 'different:current_password'],
        ]);

        $admin->update([
            'password' => Hash::make($validated['password']),
        ]);

        return redirect()
            ->route('admin.profile.index')
            ->with('success', 'Password admin berhasil diperbarui.');
    }

    private function currentAdmin(): User
    {
        $user = auth()->user();

        if ($user instanceof User && $user->role === 'admin') {
            return $user;
        }

        abort(404);
    }

    private function deleteStoredFile(?string $path): void
    {
        if (filled($path)) {
            Storage::disk('public')->delete($path);
        }
    }
}
