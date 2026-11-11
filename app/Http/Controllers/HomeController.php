<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function index(Request $request): View
    {
        $kategori = (string) $request->query('kategori');

        $query = Product::with(['primaryImage', 'user']);

        if ($kategori !== '') {
            $query->whereHas('category', fn ($q) => $q->where('slug', $kategori));
        }

        $products = $query->dijual()->latest()->get()->values();

        $activeCategory = $kategori !== ''
            ? Category::where('slug', $kategori)->first()
            : null;

        return view('home.index', [
            'banners' => [],
            'categories' => $this->categories(),
            'activeCategory' => $activeCategory,
            'nearby' => $products->take(4),
            'latest' => $products->slice(4)->take(8)->values(),
        ]);
    }

    private function categories(): Collection
    {
        return Category::urut()
            ->get()
            ->map(fn ($category): object => (object) [
                'name' => $category->name,
                'slug' => $category->slug,
                'icon_url' => $category->icon,
            ]);
    }
}
