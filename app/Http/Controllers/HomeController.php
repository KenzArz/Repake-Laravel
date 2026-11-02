<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function index(Request $request): View
    {
        $allProducts = Product::dummyCatalog();

        return view('home.index', [
            'banners' => [],                       // kosong = placeholder
            'categories' => $this->dummyCategories(),
            'nearby' => $allProducts->take(4),
            'latest' => $allProducts->slice(4)->take(8)->values(),
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
        ])->map(fn (string $name): object => (object) [
            'name' => $name,
            'slug' => str($name)->slug()->toString(),
            'icon_url' => null,
        ]);
    }
}
