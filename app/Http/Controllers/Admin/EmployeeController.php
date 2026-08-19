<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Menangani pengelolaan akun pegawai oleh admin.
 */
class EmployeeController extends Controller
{
    /**
     * Menampilkan daftar pegawai dengan dukungan pencarian dan mode penyuntingan.
     *
     * @param  Request  $request  Permintaan yang memuat pencarian atau ID pegawai yang diedit.
     */
    public function index(Request $request)
    {
        // Ambil ID mode edit dan validasi kata kunci pencarian dari query string.
        $editId = $request->integer('edit');
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
        ]);

        // Query hanya akun pegawai, kemudian terapkan mode edit atau pencarian bila aktif.
        $employees = User::query()
            ->where('role', 'pegawai')
            ->when($editId, fn ($query, int $id) => $query->whereKey($id))
            ->when($filters['search'] ?? null, function ($query, string $search) {
                // Cari kata kunci pada nama, NIP, maupun alamat email pegawai.
                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', '%'.$search.'%')
                        ->orWhere('nip', 'like', '%'.$search.'%')
                        ->orWhere('email', 'like', '%'.$search.'%');
                });
            })
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString()
            ->through(function (User $user) {
                // Susun setiap model menjadi data presentasi, termasuk inisial dan waktu daftar.
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'nip' => $user->nip,
                    'account_id' => '#' . $user->id,
                    'initials' => Str::upper(Str::of($user->name)->explode(' ')->take(2)->map(fn ($part) => Str::substr($part, 0, 1))->join('')),
                    'role' => Str::title($user->role),
                    'email' => $user->email,
                    'email_note' => 'Digunakan sebagai akun login ke sistem.',
                    'registered_at' => optional($user->created_at)->format('d/m/Y'),
                    'registered_time' => 'Pukul ' . optional($user->created_at)->format('H:i') . ' WIB',
                ];
            });

        // Kirim hasil paginasi, nilai filter, dan status filter aktif ke tampilan.
        return view('admin.employees.index', [
            'employees' => $employees,
            'filters' => $filters,
            'hasActiveFilters' => collect($filters)->filter(fn ($value) => filled($value))->isNotEmpty(),
        ]);
    }

    /**
     * Mengarahkan admin ke halaman daftar dalam mode formulir tambah pegawai.
     */
    public function create()
    {
        // Formulir tambah ditampilkan pada halaman indeks melalui parameter query.
        return redirect()->route('admin.employees.index', ['create' => 1]);
    }

    /**
     * Memvalidasi dan menyimpan akun pegawai baru.
     *
     * @param  Request  $request  Permintaan yang memuat identitas dan kata sandi pegawai.
     */
    public function store(Request $request)
    {
        // Pastikan identitas unik dan konfirmasi kata sandi memenuhi aturan akun.
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'nip' => ['required', 'string', 'max:30', 'regex:/^[0-9]+$/', 'unique:users,nip'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'email.unique' => 'Email sudah digunakan.',
            'nip.unique' => 'NIP sudah digunakan.',
        ]);

        // Buat akun dengan peran pegawai, kata sandi terenkripsi, dan email terverifikasi.
        User::query()->create([
            'name' => $validated['name'],
            'nip' => $validated['nip'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => 'pegawai',
            'email_verified_at' => now(),
        ]);

        // Kembali ke daftar pegawai dan tampilkan konfirmasi penyimpanan.
        return redirect()
            ->route('admin.employees.index')
            ->with('success', 'Pegawai "' . $validated['name'] . '" berhasil disimpan.');
    }

    /**
     * Membuka halaman daftar dalam mode penyuntingan pegawai tertentu.
     *
     * @param  User  $employee  Pengguna yang dipilih melalui route model binding.
     */
    public function edit(User $employee)
    {
        // Cegah akun selain pegawai diakses melalui endpoint pengelolaan pegawai.
        abort_if($employee->role !== 'pegawai', 404);

        // Formulir edit menggunakan halaman indeks dengan ID pegawai pada query string.
        return redirect()->route('admin.employees.index', ['edit' => $employee->id]);
    }

    /**
     * Memvalidasi dan memperbarui data akun pegawai.
     *
     * @param  Request  $request  Permintaan yang memuat perubahan data pegawai.
     * @param  User  $employee  Pegawai yang akan diperbarui.
     */
    public function update(Request $request, User $employee)
    {
        // Pastikan resource yang diperbarui benar-benar merupakan akun pegawai.
        abort_if($employee->role !== 'pegawai', 404);

        // Abaikan ID pegawai saat memeriksa keunikan NIP dan email miliknya sendiri.
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'nip' => ['required', 'string', 'max:30', 'regex:/^[0-9]+$/', Rule::unique('users', 'nip')->ignore($employee->id)],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($employee->id)],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ]);

        // Siapkan field identitas yang selalu diperbarui.
        $payload = [
            'name' => $validated['name'],
            'nip' => $validated['nip'],
            'email' => $validated['email'],
        ];

        // Pertahankan kata sandi lama bila pengguna tidak mengirim kata sandi baru.
        if (!empty($validated['password'])) {
            $payload['password'] = Hash::make($validated['password']);
        }

        // Terapkan seluruh perubahan yang telah lolos validasi.
        $employee->update($payload);

        // Tandai PDF berita acara agar dibuat ulang bila identitas cetaknya berubah.
        if ($employee->wasChanged(['name', 'nip'])) {
            $employee->receivedBeritaAcaras()->update([
                'pdf_generated_at' => null,
            ]);
        }

        // Kembali ke daftar pegawai dan tampilkan konfirmasi pembaruan.
        return redirect()
            ->route('admin.employees.index')
            ->with('success', 'Pegawai "' . $validated['name'] . '" berhasil diperbarui.');
    }

    /**
     * Menghapus pegawai yang belum memiliki riwayat transaksi.
     *
     * @param  User  $employee  Pegawai yang akan dihapus.
     */
    public function destroy(User $employee)
    {
        // Lindungi akun dengan peran lain dari endpoint penghapusan pegawai.
        abort_if($employee->role !== 'pegawai', 404);

        // Pertahankan pegawai yang masih direferensikan oleh peminjaman atau pengembalian.
        if ($employee->loans()->exists() || $employee->returns()->exists()) {
            return redirect()
                ->route('admin.employees.index')
                ->with('error', 'Pegawai tidak bisa dihapus karena masih memiliki riwayat transaksi.');
        }

        // Simpan nama sebelum model dihapus agar tetap tersedia untuk pesan sukses.
        $name = $employee->name;
        $employee->delete();

        // Kembali ke daftar pegawai dan tampilkan konfirmasi penghapusan.
        return redirect()
            ->route('admin.employees.index')
            ->with('success', 'Pegawai "' . $name . '" berhasil dihapus.');
    }
}
