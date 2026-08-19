<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Location;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Menangani penayangan dan operasi CRUD lokasi penyimpanan aset pada area admin.
 */
class LocationController extends Controller
{
    /**
     * Menampilkan daftar lokasi berdasarkan konteks edit dan filter pencarian.
     */
    public function index(Request $request)
    {
        // Parameter edit membatasi hasil ke lokasi yang sedang disunting.
        $editId = $request->integer('edit');

        // Validasi kata kunci agar pencarian hanya menerima teks dengan panjang wajar.
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
        ]);

        // Susun kueri lokasi, terapkan filter, lalu ubah hasil menjadi data tabel.
        $locations = Location::query()
            ->when($editId, fn ($query, int $id) => $query->whereKey($id))
            ->when($filters['search'] ?? null, function ($query, string $search) {
                // Kelompokkan pencarian pada seluruh informasi utama lokasi.
                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', '%'.$search.'%')
                        ->orWhere('code', 'like', '%'.$search.'%')
                        ->orWhere('address', 'like', '%'.$search.'%')
                        ->orWhere('address_note', 'like', '%'.$search.'%')
                        ->orWhere('description', 'like', '%'.$search.'%')
                        ->orWhere('note', 'like', '%'.$search.'%');
                });
            })
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString()
            ->through(fn (Location $location) => [
                'id' => $location->id,
                'name' => $location->name,
                'code' => $location->code,
                'address' => $location->address,
                'address_note' => $location->address_note ?? 'Alamat lokasi tersimpan pada sistem.',
                'description' => $location->description,
                'note' => $location->note ?? 'Catatan lokasi tersedia.',
                'edit_address_note' => $location->address_note,
                'edit_note' => $location->note,
            ]);

        // Kirim paginator, filter, dan penanda filter aktif ke tampilan.
        return view('admin.locations.index', [
            'locations' => $locations,
            'filters' => $filters,
            'hasActiveFilters' => collect($filters)->filter(fn ($value) => filled($value))->isNotEmpty(),
        ]);
    }

    /**
     * Mengarahkan halaman pembuatan ke indeks dengan penanda formulir baru.
     */
    public function create()
    {
        // Form tambah lokasi dibuka dari halaman indeks melalui parameter create.
        return redirect()->route('admin.locations.index', ['create' => 1]);
    }

    /**
     * Memvalidasi dan menyimpan lokasi baru.
     */
    public function store(Request $request)
    {
        // Pastikan atribut wajib terisi dan kode lokasi belum pernah digunakan.
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'code' => ['required', 'string', 'max:50', 'unique:locations,code'],
            'address' => ['required', 'string', 'max:255'],
            'address_note' => ['nullable', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        // Buat record lokasi dari data yang telah tervalidasi.
        Location::query()->create($validated);

        // Kembali ke daftar lokasi dengan notifikasi keberhasilan.
        return redirect()
            ->route('admin.locations.index')
            ->with('success', 'Lokasi "' . $validated['name'] . '" berhasil disimpan.');
    }

    /**
     * Mengarahkan penyuntingan ke indeks untuk lokasi terpilih.
     */
    public function edit(Location $location)
    {
        // ID lokasi pada query string mengaktifkan mode edit di halaman indeks.
        return redirect()->route('admin.locations.index', ['edit' => $location->id]);
    }

    /**
     * Memvalidasi dan memperbarui lokasi terpilih.
     */
    public function update(Request $request, Location $location)
    {
        // Izinkan kode milik lokasi saat ini, tetapi tetap tolak duplikasi lokasi lain.
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'code' => ['required', 'string', 'max:50', Rule::unique('locations', 'code')->ignore($location->id)],
            'address' => ['required', 'string', 'max:255'],
            'address_note' => ['nullable', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        // Simpan seluruh perubahan yang lolos validasi ke model lokasi.
        $location->update($validated);

        // Kembali ke daftar lokasi dengan notifikasi pembaruan.
        return redirect()
            ->route('admin.locations.index')
            ->with('success', 'Lokasi "' . $validated['name'] . '" berhasil diperbarui.');
    }

    /**
     * Menghapus lokasi yang tidak sedang digunakan oleh aset.
     */
    public function destroy(Location $location)
    {
        // Cegah penghapusan apabila masih ada aset yang merujuk lokasi ini.
        if ($location->assets()->exists()) {
            return redirect()
                ->route('admin.locations.index')
                ->with('error', 'Lokasi tidak bisa dihapus karena masih dipakai oleh data aset.');
        }

        // Simpan nama untuk pesan sukses sebelum record lokasi dihapus.
        $name = $location->name;
        $location->delete();

        // Informasikan keberhasilan setelah lokasi selesai dihapus.
        return redirect()
            ->route('admin.locations.index')
            ->with('success', 'Lokasi "' . $name . '" berhasil dihapus.');
    }
}
