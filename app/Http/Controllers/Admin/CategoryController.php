<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class CategoryController extends Controller
{
    /**
     * Tampilkan tabel kategori beserta form tambah/ubah.
     */
    public function index(): View
    {
        $categories = Category::query()
            ->withCount('products')
            ->urut()
            ->get();

        return view('admin.categories.index', ['categories' => $categories]);
    }

    /**
     * Simpan kategori baru.
     */
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100', 'unique:categories,name'],
            'icon' => ['nullable', 'string', 'max:100'],
        ]);

        Category::create([
            'name' => $data['name'],
            'slug' => Str::slug($data['name']),
            'icon' => $data['icon'] ?? null,
            'sort_order' => ((int) Category::max('sort_order')) + 1,
        ]);

        return back()->with('info', "Kategori {$data['name']} ditambahkan.");
    }

    /**
     * Perbarui kategori.
     */
    public function update(Request $request, Category $category): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100', 'unique:categories,name,'.$category->id],
            'icon' => ['nullable', 'string', 'max:100'],
        ]);

        $category->update([
            'name' => $data['name'],
            'slug' => Str::slug($data['name']),
            'icon' => $data['icon'] ?? null,
        ]);

        return back()->with('info', "Kategori {$data['name']} diperbarui.");
    }

    /**
     * Hapus kategori. Produk terkait tetap ada (category_id jadi null).
     */
    public function destroy(Category $category): RedirectResponse
    {
        $name = $category->name;
        $category->delete();

        return back()->with('info', "Kategori {$name} dihapus.");
    }
}
