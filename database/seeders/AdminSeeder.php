<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        // Atribut diisi manual agar tidak mass-assignable dari request.
        $admin = User::firstOrNew(['email' => 'admin@unsika.ac.id']);

        if (! $admin->exists) {
            $admin->name = 'Admin RePake';
            $admin->password = 'password';
            $admin->email_verified_at = now();
        }

        $admin->role = User::ROLE_ADMIN;
        $admin->is_banned = false;
        $admin->save();
    }
}
