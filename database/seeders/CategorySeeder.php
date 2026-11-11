<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    /**
     * @var array<int, string>
     */
    public const NAMES = [
        'Elektronik',
        'Pakaian',
        'Perabot',
        'Olahraga',
        'Buku',
        'Hobi',
        'Otomotif',
        'Lainnya',
    ];

    public function run(): void
    {
        foreach (self::NAMES as $name) {
            Category::updateOrCreate(
                ['slug' => Str::slug($name)],
                ['name' => $name, 'icon' => null],
            );
        }
    }
}
