<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        foreach (config('repake.admin_emails', []) as $email) {
            // Atribut diisi manual agar tidak mass-assignable dari request.
            // Jangan sentuh firebase_uid: akun yang sudah pernah login tetap utuh.
            $admin = User::firstOrNew(['email' => $email]);

            if (! $admin->exists) {
                $admin->name = 'Admin RePake';
                $admin->password = Str::random(40);
                $admin->email_verified_at = now();
            }

            $admin->role = User::ROLE_ADMIN;
            $admin->is_banned = false;
            $admin->save();
        }
    }
}
