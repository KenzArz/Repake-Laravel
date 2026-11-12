<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\Report;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Contoh laporan agar tab Laporan di dashboard admin tidak kosong.
 */
class ReportSeeder extends Seeder
{
    public function run(): void
    {
        $reports = [
            [
                'product' => 'Keyboard Mechanical RGB Gaming Bekas Mulus',
                'reporter' => 'booklover@student.unsika.ac.id',
                'alasan' => Report::REASON_BARANG_PALSU,
                'keterangan' => 'Barang yang dikirim tidak sesuai foto dan terlihat seperti tiruan.',
                'status' => Report::STATUS_PENDING,
            ],
            [
                'product' => 'Kamera DSLR Canon EOS 70D Fullset Mulus Siap Pakai',
                'reporter' => 'melodymusic@student.unsika.ac.id',
                'alasan' => Report::REASON_HARGA_TIDAK_WAJAR,
                'keterangan' => 'Harga jauh di bawah pasaran dan penjual meminta transfer di luar platform.',
                'status' => Report::STATUS_PENDING,
            ],
            [
                'product' => 'Sepatu Sneakers Nike Air Force 1 Original Second',
                'reporter' => 'urbanoutfit@student.unsika.ac.id',
                'alasan' => Report::REASON_PENIPUAN,
                'keterangan' => 'Penjual tidak mengirim barang setelah pembayaran dan sulit dihubungi.',
                'status' => Report::STATUS_PENDING,
            ],
        ];

        foreach ($reports as $report) {
            $product = Product::where('judul', $report['product'])->first();
            $reporter = User::where('email', $report['reporter'])->first();

            if (! $product || ! $reporter || $product->user_id === $reporter->id) {
                continue;
            }

            Report::firstOrCreate(
                [
                    'product_id' => $product->id,
                    'reporter_id' => $reporter->id,
                ],
                [
                    'alasan' => $report['alasan'],
                    'keterangan' => $report['keterangan'],
                    'status' => $report['status'],
                ],
            );
        }
    }
}
