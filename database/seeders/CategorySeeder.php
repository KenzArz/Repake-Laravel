<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Elektronik',
                'slug' => 'elektronik',
                'icon' => 'https://api.iconify.design/lucide/laptop.svg?color=%234c8b4a'
            ],
            [
                'name' => 'Pakaian',
                'slug' => 'pakaian',
                'icon' => 'https://api.iconify.design/lucide/shirt.svg?color=%234c8b4a'
            ],
            [
                'name' => 'Perabot',
                'slug' => 'perabot',
                'icon' => 'https://api.iconify.design/lucide/armchair.svg?color=%234c8b4a'
            ],
            [
                'name' => 'Olahraga',
                'slug' => 'olahraga',
                'icon' => 'https://api.iconify.design/lucide/dumbbell.svg?color=%234c8b4a'
            ],
            [
                'name' => 'Buku',
                'slug' => 'buku',
                'icon' => 'https://api.iconify.design/lucide/book-open.svg?color=%234c8b4a'
            ],
            [
                'name' => 'Hobi',
                'slug' => 'hobi',
                'icon' => 'https://api.iconify.design/lucide/gamepad-2.svg?color=%234c8b4a'
            ],
            [
                'name' => 'Otomotif',
                'slug' => 'otomotif',
                'icon' => 'https://api.iconify.design/lucide/car.svg?color=%234c8b4a'
            ],
            [
                'name' => 'Lainnya',
                'slug' => 'lainnya',
                'icon' => 'https://api.iconify.design/lucide/grid.svg?color=%234c8b4a'
            ],
        ];

        foreach ($categories as $category) {
            Category::updateOrCreate(
                ['slug' => $category['slug']],
                [
                    'name' => $category['name'],
                    'icon' => $category['icon']
                ]
            );
        }
    }
}
