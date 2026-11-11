<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Admin RePake
        User::updateOrCreate(
            ['email' => 'admin@unsika.ac.id'],
            [
                'name' => 'Admin RePake',
                'username' => 'admin_repake',
                'email_verified_at' => now(),
                'password' => Hash::make('password123'),
                'whatsapp_number' => '081234567890',
                'city' => 'Karawang',
                'province' => 'Jawa Barat',
                'bio' => 'Administrator resmi RePake UNSIKA',
                'role' => 'admin',
                'status' => 'active',
            ]
        );

        // 2. Akun Mahasiswa / Penjual Dummy
        $sellers = [
            [
                'name' => 'Midou Wijaya',
                'username' => 'midou_kun',
                'email' => 'midou@student.unsika.ac.id',
                'city' => 'Bekasi',
                'province' => 'Jawa Barat',
                'bio' => 'Jual apa aja demi bisa nikahin hiura',
                'whatsapp_number' => '081299887766',
            ],
            [
                'name' => 'TechStore ID',
                'username' => 'techstore_id',
                'email' => 'techstore@student.unsika.ac.id',
                'city' => 'Jakarta Pusat',
                'province' => 'DKI Jakarta',
                'bio' => 'Gadget, peripheral, & aksesoris gaming second original terpercaya',
                'whatsapp_number' => '081311223344',
            ],
            [
                'name' => 'Vintage Kuy',
                'username' => 'vintagekuy',
                'email' => 'vintagekuy@student.unsika.ac.id',
                'city' => 'Bandung',
                'province' => 'Jawa Barat',
                'bio' => 'Thrift & fashion 90s vintage look pilihan terbaik',
                'whatsapp_number' => '081555667788',
            ],
            [
                'name' => 'Camera Bekas Pro',
                'username' => 'camerabekas_pro',
                'email' => 'camerabekas@student.unsika.ac.id',
                'city' => 'Tangerang',
                'province' => 'Banten',
                'bio' => 'Jual beli kamera DSLR, mirrorless, dan lensa second bergaransi',
                'whatsapp_number' => '081744332211',
            ],
            [
                'name' => 'Urban Outfit',
                'username' => 'urbanoutfit',
                'email' => 'urbanoutfit@student.unsika.ac.id',
                'city' => 'Jakarta Selatan',
                'province' => 'DKI Jakarta',
                'bio' => 'Pakaian pria & wanita casual streetwear',
                'whatsapp_number' => '081822334455',
            ],
            [
                'name' => 'Book Lover BDG',
                'username' => 'booklover_bdg',
                'email' => 'booklover@student.unsika.ac.id',
                'city' => 'Bandung',
                'province' => 'Jawa Barat',
                'bio' => 'Buku kuliah, novel, dan buku pengembangan diri bekas mulus',
                'whatsapp_number' => '081900112233',
            ],
            [
                'name' => 'Furniture Kuy',
                'username' => 'furniturekuy',
                'email' => 'furniturekuy@student.unsika.ac.id',
                'city' => 'Karawang',
                'province' => 'Jawa Barat',
                'bio' => 'Perabot kos & meja kerja kayu aesthetic daerah Karawang',
                'whatsapp_number' => '081277665544',
            ],
            [
                'name' => 'Gadget Second BDG',
                'username' => 'gadgetsecond_bdg',
                'email' => 'gadgetsecond@student.unsika.ac.id',
                'city' => 'Bandung',
                'province' => 'Jawa Barat',
                'bio' => 'Smartwatch, TWS, & smart device second terawat',
                'whatsapp_number' => '081388776655',
            ],
            [
                'name' => 'Melody Music',
                'username' => 'melodymusic',
                'email' => 'melodymusic@student.unsika.ac.id',
                'city' => 'Jakarta Barat',
                'province' => 'DKI Jakarta',
                'bio' => 'Alat musik gitar akustik & aksesoris second berkualitas',
                'whatsapp_number' => '081699887766',
            ],
        ];

        foreach ($sellers as $seller) {
            User::updateOrCreate(
                ['email' => $seller['email']],
                [
                    'name' => $seller['name'],
                    'username' => $seller['username'],
                    'email_verified_at' => now(),
                    'password' => Hash::make('password123'),
                    'whatsapp_number' => $seller['whatsapp_number'],
                    'city' => $seller['city'],
                    'province' => $seller['province'],
                    'bio' => $seller['bio'],
                    'role' => 'user',
                    'status' => 'active',
                ]
            );
        }
    }
}

