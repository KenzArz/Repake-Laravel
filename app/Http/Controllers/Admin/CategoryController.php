<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    /**
     * Kategori sekarang menjadi salah satu tab di /admin/dashboard.
     */
    public function index(): RedirectResponse
    {
        return redirect()->route('admin.dashboard', ['tab' => 'kategori']);
    }

    /**
     * Simpan kategori baru.
     */
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100', 'unique:categories,name'],
            'icon' => ['nullable', 'string', 'max:100'],
            'icon_file' => ['nullable', 'image', 'mimes:png,webp', 'max:2048'],
        ], [
            'icon_file.image' => 'Ikon harus berupa gambar.',
            'icon_file.mimes' => 'Ikon harus berformat PNG.',
            'icon_file.max' => 'Ukuran ikon maksimal 2 MB.',
        ]);

        $icon = $data['icon'] ?? null;

        if ($request->hasFile('icon_file')) {
            $icon = $this->storeIcon($request->file('icon_file'));
        }

        Category::create([
            'name' => $data['name'],
            'slug' => Str::slug($data['name']),
            'icon' => $icon,
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
            'name' => ['required', 'string', 'max:100', 'unique:categories,name,' . $category->id],
            'icon' => ['nullable', 'string', 'max:100'],
            'icon_file' => ['nullable', 'image', 'mimes:png,webp', 'max:2048'],
        ], [
            'icon_file.image' => 'Ikon harus berupa gambar.',
            'icon_file.mimes' => 'Ikon harus berformat PNG atau WEBP.',
            'icon_file.max' => 'Ukuran ikon maksimal 2 MB.',
        ]);

        $icon = $category->icon;

        if ($request->hasFile('icon_file')) {
            $this->deleteIcon($category->icon);
            $icon = $this->storeIcon($request->file('icon_file'));
        } elseif (array_key_exists('icon', $data)) {
            $icon = $data['icon'];
        }

        $category->update([
            'name' => $data['name'],
            'slug' => Str::slug($data['name']),
            'icon' => $icon,
        ]);

        return back()->with('info', "Kategori {$data['name']} diperbarui.");
    }

    /**
     * Hapus kategori. Produk terkait tetap ada (category_id jadi null).
     */
    public function destroy(Category $category): RedirectResponse
    {
        $name = $category->name;

        $this->deleteIcon($category->icon);
        $category->delete();

        return back()->with('info', "Kategori {$name} dihapus.");
    }

    /**
     * Simpan ikon unggahan ke disk publik dan kembalikan URL publiknya.
     */
    private function storeIcon(UploadedFile $file): string
    {
        return Storage::url($file->store('categories', 'public'));
    }

    /**
     * Hapus file ikon lama jika memang berasal dari unggahan admin.
     */
    private function deleteIcon(?string $icon): void
    {
        if (! $icon) {
            return;
        }

        $path = match (true) {
            str_starts_with($icon, '/storage/') => Str::after($icon, '/storage/'),
            str_starts_with($icon, 'categories/') => $icon,
            default => null,
        };

        if ($path !== null) {
            Storage::disk('public')->delete($path);
        }
    }
}
