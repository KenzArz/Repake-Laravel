<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function index(Request $request): View
    {
        // TODO: ganti dengan query Eloquent
        //   - Category::all()
        //   - Product::with(['images','user'])->where('kota', auth()->user()?->kota)->latest()->take(4)->get()
        //   - filter $request->q (search) dan $request->kategori
        return view('home.index', [
            'banners'    => [],                       // kosong = 3 placeholder abu-abu
            'categories' => $this->dummyCategories(),
            'nearby'     => $this->dummyProducts(4),
            'latest'     => $this->dummyProducts(8, offset: 4),
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
        ])->map(fn($name) => (object) [
            'name'     => $name,
            'slug'     => str($name)->slug()->toString(),
            'icon_url' => null,
        ]);
    }

    private function dummyProducts(int $count, int $offset = 0): Collection
    {
        // Taruh gambar di public/images/dummy/ (kalau belum ada, otomatis jadi kotak abu-abu)
        $samples = [
            ['judul' => 'Kemeja pria warna merah motif kotak lengan panjang', 'harga' => 170000, 'kondisi' => 'Dirawat dengan baik', 'gambar' => 'images/dummy/kemeja.jpg'],
            ['judul' => 'Sepatu adidas asli ori real warna hitam ukuran a4',  'harga' => 250000, 'kondisi' => 'Jarang Digunakan',    'gambar' => 'images/dummy/sepatu.jpg'],
            ['judul' => 'Keyboad pemda bekas reza auditore asli no hoax',     'harga' => 670000, 'kondisi' => 'Dirawat dengan baik', 'gambar' => 'images/dummy/keyboard.jpg'],
            ['judul' => 'Samsung galaxy ear buds',                            'harga' => 100000, 'kondisi' => 'Dirawat dengan baik', 'gambar' => 'images/dummy/earbuds.jpg'],
        ];

        return collect(range(0, $count - 1))->map(fn($i) => (object) array_merge(
            $samples[($offset + $i) % count($samples)],
            [
                'id'          => $offset + $i + 1,
                'penjual'     => 'Midou_kun',
                'kota'        => 'Bekasi',
                'status'      => 'Dijual',
                'is_favorite' => false,
                'created_at'  => now()->subHours(2),
            ],
        ));
    }
}
