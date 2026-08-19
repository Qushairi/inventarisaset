<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Menangani penayangan dan operasi CRUD kategori aset pada area admin.
 */
class CategoryController extends Controller
{
    /**
     * Menampilkan daftar kategori berdasarkan konteks edit dan filter pencarian.
     */
    public function index(Request $request)
    {
        // Parameter edit membatasi hasil ke kategori yang sedang disunting.
        $editId = $request->integer('edit');

        // Validasi kata kunci agar pencarian hanya menerima teks dengan panjang wajar.
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
        ]);

        // Susun kueri kategori, terapkan filter, lalu ubah hasil menjadi data tabel.
        $categories = Category::query()
            ->when($editId, fn ($query, int $id) => $query->whereKey($id))
            ->when($filters['search'] ?? null, function ($query, string $search) {
                // Kelompokkan pencarian pada nama, kode, deskripsi, dan catatan.
                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', '%'.$search.'%')
                        ->orWhere('code', 'like', '%'.$search.'%')
                        ->orWhere('description', 'like', '%'.$search.'%')
                        ->orWhere('note', 'like', '%'.$search.'%');
                });
            })
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString()
            ->through(fn (Category $category) => [
                'id' => $category->id,
                'name' => $category->name,
                'code' => $category->code,
                'description' => $category->description,
                'note' => $category->note ?? 'Deskripsi kategori sudah tersedia.',
                'edit_note' => $category->note,
            ]);

        // Kirim paginator, filter, dan penanda filter aktif ke tampilan.
        return view('admin.categories.index', [
            'categories' => $categories,
            'filters' => $filters,
            'hasActiveFilters' => collect($filters)->filter(fn ($value) => filled($value))->isNotEmpty(),
        ]);
    }

    /**
     * Mengarahkan halaman pembuatan ke indeks dengan penanda formulir baru.
     */
    public function create()
    {
        // Form tambah kategori dibuka dari halaman indeks melalui parameter create.
        return redirect()->route('admin.categories.index', ['create' => 1]);
    }

    /**
     * Memvalidasi dan menyimpan kategori baru.
     */
    public function store(Request $request)
    {
        // Pastikan atribut wajib terisi dan kode kategori belum pernah digunakan.
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'code' => ['required', 'string', 'max:50', 'unique:categories,code'],
            'description' => ['required', 'string', 'max:255'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        // Buat record kategori dari data yang telah tervalidasi.
        Category::query()->create($validated);

        // Kembali ke daftar kategori dengan notifikasi keberhasilan.
        return redirect()
            ->route('admin.categories.index')
            ->with('success', 'Kategori "' . $validated['name'] . '" berhasil disimpan.');
    }

    /**
     * Mengarahkan penyuntingan ke indeks untuk kategori terpilih.
     */
    public function edit(Category $category)
    {
        // ID kategori pada query string mengaktifkan mode edit di halaman indeks.
        return redirect()->route('admin.categories.index', ['edit' => $category->id]);
    }

    /**
     * Memvalidasi dan memperbarui kategori terpilih.
     */
    public function update(Request $request, Category $category)
    {
        // Izinkan kode milik kategori saat ini, tetapi tetap tolak duplikasi kategori lain.
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'code' => ['required', 'string', 'max:50', Rule::unique('categories', 'code')->ignore($category->id)],
            'description' => ['required', 'string', 'max:255'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        // Simpan seluruh perubahan yang lolos validasi ke model kategori.
        $category->update($validated);

        // Kembali ke daftar kategori dengan notifikasi pembaruan.
        return redirect()
            ->route('admin.categories.index')
            ->with('success', 'Kategori "' . $validated['name'] . '" berhasil diperbarui.');
    }

    /**
     * Menghapus kategori yang tidak sedang digunakan oleh aset.
     */
    public function destroy(Category $category)
    {
        // Cegah penghapusan apabila masih ada aset yang merujuk kategori ini.
        if ($category->assets()->exists()) {
            return redirect()
                ->route('admin.categories.index')
                ->with('error', 'Kategori tidak bisa dihapus karena masih dipakai oleh data aset.');
        }

        // Simpan nama untuk pesan sukses sebelum record kategori dihapus.
        $name = $category->name;
        $category->delete();

        // Informasikan keberhasilan setelah kategori selesai dihapus.
        return redirect()
            ->route('admin.categories.index')
            ->with('success', 'Kategori "' . $name . '" berhasil dihapus.');
    }
}
