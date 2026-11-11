<?php

namespace Database\Seeders;

use App\Models\CodArea;
use Illuminate\Database\Seeder;

class CodAreaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $areas = [
            [
                'name' => 'Kampus 1 UNSIKA Telukjambe',
                'city' => 'Karawang',
                'province' => 'Jawa Barat',
                'boundary' => ['type' => 'Point', 'coordinates' => [107.3069, -6.3249]],
            ],
            [
                'name' => 'Kampus 2 UNSIKA Klari',
                'city' => 'Karawang',
                'province' => 'Jawa Barat',
                'boundary' => ['type' => 'Point', 'coordinates' => [107.3512, -6.3524]],
            ],
            [
                'name' => 'Galuh Mas & Festive Walk',
                'city' => 'Karawang',
                'province' => 'Jawa Barat',
                'boundary' => ['type' => 'Point', 'coordinates' => [107.3001, -6.3155]],
            ],
            [
                'name' => 'Grand Metropolitan Bekasi',
                'city' => 'Bekasi',
                'province' => 'Jawa Barat',
                'boundary' => ['type' => 'Point', 'coordinates' => [106.9850, -6.2469]],
            ],
            [
                'name' => 'Summarecon Mall Bekasi',
                'city' => 'Bekasi',
                'province' => 'Jawa Barat',
                'boundary' => ['type' => 'Point', 'coordinates' => [106.9996, -6.2255]],
            ],
            [
                'name' => 'Dago & Simpang ITB',
                'city' => 'Bandung',
                'province' => 'Jawa Barat',
                'boundary' => ['type' => 'Point', 'coordinates' => [107.6111, -6.8906]],
            ],
            [
                'name' => 'Monas & Thamrin',
                'city' => 'Jakarta Pusat',
                'province' => 'DKI Jakarta',
                'boundary' => ['type' => 'Point', 'coordinates' => [106.8272, -6.1754]],
            ],
            [
                'name' => 'Blok M Hub',
                'city' => 'Jakarta Selatan',
                'province' => 'DKI Jakarta',
                'boundary' => ['type' => 'Point', 'coordinates' => [106.7982, -6.2443]],
            ],
            [
                'name' => 'BSD City & Gading Serpong',
                'city' => 'Tangerang',
                'province' => 'Banten',
                'boundary' => ['type' => 'Point', 'coordinates' => [106.6319, -6.3016]],
            ],
            [
                'name' => 'Central Park & Neo Soho',
                'city' => 'Jakarta Barat',
                'province' => 'DKI Jakarta',
                'boundary' => ['type' => 'Point', 'coordinates' => [106.7907, -6.1774]],
            ],
        ];

        foreach ($areas as $area) {
            CodArea::updateOrCreate(
                ['name' => $area['name'], 'city' => $area['city']],
                $area
            );
        }
    }
}
