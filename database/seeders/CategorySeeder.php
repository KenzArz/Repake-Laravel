<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    /**
     * Urutan kategori sesuai tampilan landing page (dari kiri ke kanan).
     *
     * @var array<string, string>
     */
    public const NAMES = [
        'Elektronik' => '/images/categories/elektronik.svg',
        'Pakaian' => '/images/categories/pakaian.svg',
        'Perabot' => '/images/categories/perabot.svg',
        'Olahraga' => '/images/categories/olahraga.svg',
        'Buku' => '/images/categories/buku.svg',
        'Hobi' => '/images/categories/hobi.svg',
        'Otomotif' => '/images/categories/otomotif.svg',
        'Lainnya' => '/images/categories/lainnya.svg',
    ];

    public function run(): void
    {
        $urut = 1;

        foreach (self::NAMES as $name => $icon) {
            Category::updateOrCreate(
                ['slug' => Str::slug($name)],
                ['name' => $name, 'icon' => $icon, 'sort_order' => $urut],
            );

            $urut++;
        }
    }
}
