<?php

namespace App\Http\Controllers\Pegawai;

use App\Models\AssetReturn;
use App\Models\Loan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;

class ProfileController extends BasePegawaiController
{
    public function index()
    {
        $pegawai = $this->currentPegawai();

        $recentLoans = Loan::query()
            ->with('asset')
            ->where('user_id', $pegawai->id)
            ->latest('loan_date')
            ->take(4)
            ->get();

        $recentReturns = AssetReturn::query()
            ->with('asset')
            ->where('user_id', $pegawai->id)
            ->latest('returned_at')
            ->take(4)
            ->get();

        return view('pegawai.profile.index', $this->layoutData([
            'profileStats' => [
                [
                    'label' => 'Total Peminjaman',
                    'value' => Loan::query()->where('user_id', $pegawai->id)->count(),
                    'icon' => 'journal-check',
                    'variant' => 'warning',
                ],
                [
                    'label' => 'Menunggu Persetujuan',
                    'value' => Loan::query()->where('user_id', $pegawai->id)->where('status', 'Menunggu')->count(),
                    'icon' => 'hourglass-split',
                    'variant' => 'primary',
                ],
                [
                    'label' => 'Total Pengembalian',
                    'value' => AssetReturn::query()->where('user_id', $pegawai->id)->count(),
                    'icon' => 'arrow-counterclockwise',
                    'variant' => 'info',
                ],
                [
                    'label' => 'Pengembalian Terverifikasi',
                    'value' => AssetReturn::query()->where('user_id', $pegawai->id)->where('status', 'Terverifikasi')->count(),
                    'icon' => 'check-circle',
                    'variant' => 'success',
                ],
            ],
            'recentLoans' => $recentLoans,
            'recentReturns' => $recentReturns,
        ]));
    }

    public function update(Request $request): RedirectResponse
    {
        $pegawai = $this->currentPegawai();

        if ($request->boolean('remove_profile_photo')) {
            $this->deleteProfilePhoto($pegawai->profile_photo_path);

            $pegawai->update([
                'profile_photo_path' => null,
            ]);

            return redirect()
                ->route('pegawai.profile.index')
                ->with('success', 'Foto profil berhasil dihapus.');
        }

        $validated = $request->validateWithBag('updatePhoto', [
            'profile_photo' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        $this->deleteProfilePhoto($pegawai->profile_photo_path);

        $path = $validated['profile_photo']->store('profile-photos', 'public');

        $pegawai->update([
            'profile_photo_path' => $path,
        ]);

        return redirect()
            ->route('pegawai.profile.index')
            ->with('success', 'Foto profil berhasil diperbarui.');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $pegawai = $this->currentPegawai();

        $validated = $request->validateWithBag('updatePassword', [
            'current_password' => ['required', 'current_password'],
            'password' => ['required', Password::defaults(), 'confirmed', 'different:current_password'],
        ]);

        $pegawai->update([
            'password' => Hash::make($validated['password']),
        ]);

        return redirect()
            ->route('pegawai.profile.index')
            ->with('success', 'Password berhasil diperbarui.');
    }

    private function deleteProfilePhoto(?string $path): void
    {
        if (filled($path)) {
            Storage::disk('public')->delete($path);
        }
    }
}
