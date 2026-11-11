<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    /**
     * @var array<string, string|null>
     */
    public const NAMES = [
        'Elektronik' => 'https://api.iconify.design/lucide/laptop.svg?color=%234c8b4a',
        'Pakaian' => 'https://api.iconify.design/lucide/shirt.svg?color=%234c8b4a',
        'Perabot' => 'https://api.iconify.design/lucide/armchair.svg?color=%234c8b4a',
        'Olahraga' => 'https://api.iconify.design/lucide/dumbbell.svg?color=%234c8b4a',
        'Buku' => 'https://api.iconify.design/lucide/book-open.svg?color=%234c8b4a',
        'Hobi' => 'https://api.iconify.design/lucide/gamepad-2.svg?color=%234c8b4a',
        'Otomotif' => 'https://api.iconify.design/lucide/car.svg?color=%234c8b4a',
        'Lainnya' => 'https://api.iconify.design/lucide/grid.svg?color=%234c8b4a',
    ];

    public function run(): void
    {
        foreach (self::NAMES as $name => $icon) {
            Category::updateOrCreate(
                ['slug' => Str::slug($name)],
                ['name' => $name, 'icon' => $icon],
            );
        }
    }
}
