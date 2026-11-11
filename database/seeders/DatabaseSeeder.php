<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            CategorySeeder::class,
            CodAreaSeeder::class,
            UserSeeder::class,
            AdminSeeder::class,
            ProductSeeder::class,
        ]);

        // Idempoten: aman dijalankan berkali-kali (migrate --seed tanpa fresh).
        User::firstOrCreate(
            ['email' => 'test@example.com'],
            ['name' => 'Test User', 'password' => 'password'],
        );
    }
}
