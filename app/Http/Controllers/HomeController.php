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
        $categories = Category::all();

        $query = Product::with(['user', 'primaryImage', 'images', 'codAreas', 'category'])
            ->where('moderation_status', 'active');

        if ($request->filled('kategori')) {
            $query->whereHas('category', fn($q) => $q->where('slug', $request->query('kategori')));
        }

        $products = $query->latest()->get();

        return view('home.index', [
            'banners' => [],
            'categories' => $categories,
            'nearby' => $products->take(4),
            'latest' => $products->slice(4)->take(8)->values(),
        ]);
    }

    private function dummyCategories(): Collection
    {
        return collect([
            'Elektronik',
            'Pakaian',
            'Perabot',
            'Olahraga',
            'Buku',
            'Hobi',
            'Otomotif',
            'Lainnya',
        ])->map(fn(string $name): object => (object) [
            'name' => $name,
            'slug' => str($name)->slug()->toString(),
            'icon_url' => null,
        ]);
    }
}
