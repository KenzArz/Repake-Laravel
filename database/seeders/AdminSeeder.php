<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AdminSeeder extends Seeder
{
    /**
     * Buat akun admin berdasarkan email dari environment.
     * Jika tidak ada (sepeda test), gunakan nilai default.
     *
     * @return void
     */
    public function run(): void
    {
        $adminEmail = env('ADMIN_EMAIL', '2410631170004@student.unsika.ac.id');

        $admin = User::firstOrNew(['email' => $adminEmail]);

        if (! $admin->exists) {
            $admin->name = 'Admin RePake';
            $admin->password = $admin->password ?? bcrypt('password');
            $admin->email_verified_at = $admin->email_verified_at ?? now();
        }

        $admin->role = User::ROLE_ADMIN;
        $admin->is_banned = false;
        $admin->save();
    }
}
