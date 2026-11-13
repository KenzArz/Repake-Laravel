<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * Hanya mengisi data inti: kategori (dengan icon), area COD, dan akun admin.
     * Data dummy (produk, banner, report, user demo) tidak dijalankan lagi.
     */
    public function run(): void
    {
        $this->call([
            CategorySeeder::class,
            CodAreaSeeder::class,
            AdminSeeder::class,
            BannerSeeder::class,
        ]);
    }
}