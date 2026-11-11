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
                'phone' => '081234567890',
                'kota' => 'Karawang',
                'bio' => 'Administrator resmi RePake UNSIKA',
                'role' => 'admin',
            ]
        );

        // 2. Akun Mahasiswa / Penjual Dummy
        $sellers = [
            [
                'name' => 'Midou Wijaya',
                'username' => 'midou_kun',
                'email' => 'midou@student.unsika.ac.id',
                'kota' => 'Bekasi',
                'bio' => 'Jual apa aja demi bisa nikahin hiura',
                'phone' => '081299887766',
            ],
            [
                'name' => 'TechStore ID',
                'username' => 'techstore_id',
                'email' => 'techstore@student.unsika.ac.id',
                'kota' => 'Jakarta Pusat',
                'bio' => 'Gadget, peripheral, & aksesoris gaming second original terpercaya',
                'phone' => '081311223344',
            ],
            [
                'name' => 'Vintage Kuy',
                'username' => 'vintagekuy',
                'email' => 'vintagekuy@student.unsika.ac.id',
                'kota' => 'Bandung',
                'bio' => 'Thrift & fashion 90s vintage look pilihan terbaik',
                'phone' => '081555667788',
            ],
            [
                'name' => 'Camera Bekas Pro',
                'username' => 'camerabekas_pro',
                'email' => 'camerabekas@student.unsika.ac.id',
                'kota' => 'Tangerang',
                'bio' => 'Jual beli kamera DSLR, mirrorless, dan lensa second bergaransi',
                'phone' => '081744332211',
            ],
            [
                'name' => 'Urban Outfit',
                'username' => 'urbanoutfit',
                'email' => 'urbanoutfit@student.unsika.ac.id',
                'kota' => 'Jakarta Selatan',
                'bio' => 'Pakaian pria & wanita casual streetwear',
                'phone' => '081822334455',
            ],
            [
                'name' => 'Book Lover BDG',
                'username' => 'booklover_bdg',
                'email' => 'booklover@student.unsika.ac.id',
                'kota' => 'Bandung',
                'bio' => 'Buku kuliah, novel, dan buku pengembangan diri bekas mulus',
                'phone' => '081900112233',
            ],
            [
                'name' => 'Furniture Kuy',
                'username' => 'furniturekuy',
                'email' => 'furniturekuy@student.unsika.ac.id',
                'kota' => 'Karawang',
                'bio' => 'Perabot kos & meja kerja kayu aesthetic daerah Karawang',
                'phone' => '081277665544',
            ],
            [
                'name' => 'Gadget Second BDG',
                'username' => 'gadgetsecond_bdg',
                'email' => 'gadgetsecond@student.unsika.ac.id',
                'kota' => 'Bandung',
                'bio' => 'Smartwatch, TWS, & smart device second terawat',
                'phone' => '081388776655',
            ],
            [
                'name' => 'Melody Music',
                'username' => 'melodymusic',
                'email' => 'melodymusic@student.unsika.ac.id',
                'kota' => 'Jakarta Barat',
                'bio' => 'Alat musik gitar akustik & aksesoris second berkualitas',
                'phone' => '081699887766',
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
                    'phone' => $seller['phone'],
                    'kota' => $seller['kota'],
                    'bio' => $seller['bio'],
                    'role' => User::ROLE_MAHASISWA,
                ]
            );
        }
    }
}
