<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CategoryController extends Controller
{
    public function index()
    {
        $categories = Category::query()
            ->orderBy('name')
            ->paginate(10)
            ->through(fn (Category $category) => [
                'name' => $category->name,
                'code' => $category->code,
                'description' => $category->description,
                'note' => $category->note ?? 'Deskripsi kategori sudah tersedia.',
            ]);

        return view('admin.categories.index', [
            'categories' => $categories,
        ]);
    }

    public function create()
    {
        return view('admin.categories.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'code' => ['required', 'string', 'max:50', 'unique:categories,code'],
            'description' => ['required', 'string', 'max:255'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        Category::query()->create($validated);

        return redirect()
            ->route('admin.categories.index')
            ->with('success', 'Kategori "' . $validated['name'] . '" berhasil disimpan.');
    }

    public function edit(Category $category)
    {
        return view('admin.categories.edit', [
            'category' => $category,
        ]);
    }

    public function update(Request $request, Category $category)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'code' => ['required', 'string', 'max:50', Rule::unique('categories', 'code')->ignore($category->id)],
            'description' => ['required', 'string', 'max:255'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        $category->update($validated);

        return redirect()
            ->route('admin.categories.index')
            ->with('success', 'Kategori "' . $validated['name'] . '" berhasil diperbarui.');
    }

    public function destroy(Category $category)
    {
        if ($category->assets()->exists()) {
            return redirect()
                ->route('admin.categories.index')
                ->with('error', 'Kategori tidak bisa dihapus karena masih dipakai oleh data aset.');
        }

        $name = $category->name;
        $category->delete();

        return redirect()
            ->route('admin.categories.index')
            ->with('success', 'Kategori "' . $name . '" berhasil dihapus.');
    }
}
