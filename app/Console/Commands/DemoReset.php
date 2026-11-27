<?php

namespace App\Console\Commands;

use Database\Seeders\DemoSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * Kembalikan instance demo ke kondisi awal.
 *
 * Hanya berjalan saat DEMO_MODE=true supaya perintah ini tidak pernah
 * menghapus data di instance produksi.
 *
 * Secara default reset dilakukan tanpa DDL: data demo dihapus lalu diisi
 * ulang dalam satu transaksi, sehingga kegagalan di tengah proses tidak
 * meninggalkan database kosong dan user DB tidak perlu hak DROP/CREATE.
 * Opsi --fresh dipakai hanya saat skema memang berubah (migrasi baru).
 */
class DemoReset extends Command
{
    protected $signature = 'demo:reset
                            {--force : Tanpa konfirmasi}
                            {--fresh : Bangun ulang skema dengan migrate:fresh}';

    protected $description = 'Kosongkan data demo lalu isi ulang dengan DemoSeeder (khusus instance demo)';

    /**
     * Tabel yang dikosongkan sebelum DemoSeeder dijalankan, urut dari anak
     * ke induk agar tetap aman bila foreign key check aktif. Tabel
     * infrastruktur (migrations, sessions, cache, jobs) sengaja tidak
     * disentuh supaya sesi juri dan antrean tidak ikut terhapus.
     *
     * @var list<string>
     */
    private const TABEL_DEMO = [
        'message_images',
        'messages',
        'reviews',
        'favorites',
        'reports',
        'notifications',
        'orders',
        'conversations',
        'product_cod_areas',
        'product_images',
        'products',
        'cod_areas',
        'banners',
        'categories',
        'personal_access_tokens',
        'password_reset_tokens',
        'users',
    ];

    public function handle(): int
    {
        if (! config('demo.enabled')) {
            $this->components->error('Mode demo nonaktif (DEMO_MODE=false). Reset dibatalkan.');

            return self::FAILURE;
        }

        if (! $this->option('force') && ! $this->confirm('Seluruh data demo akan dihapus. Lanjutkan?', false)) {
            $this->components->info('Reset dibatalkan.');

            return self::SUCCESS;
        }

        if ($this->option('fresh')) {
            $this->call('migrate:fresh', [
                '--seed' => true,
                '--seeder' => DemoSeeder::class,
                '--force' => true,
            ]);
        } elseif (! $this->kosongkanDanIsiUlang()) {
            return self::FAILURE;
        }

        $this->components->info('Data demo berhasil direset ke kondisi awal.');

        return self::SUCCESS;
    }

    /**
     * Hapus seluruh data demo lalu seed ulang dalam satu transaksi. Bila
     * seeder gagal, seluruh penghapusan ikut dibatalkan.
     */
    private function kosongkanDanIsiUlang(): bool
    {
        try {
            DB::transaction(function (): void {
                foreach (self::TABEL_DEMO as $tabel) {
                    if (Schema::hasTable($tabel)) {
                        DB::table($tabel)->delete();
                    }
                }

                $this->call('db:seed', [
                    '--class' => DemoSeeder::class,
                    '--force' => true,
                ]);
            }, 3);
        } catch (Throwable $e) {
            report($e);

            $this->components->error('Reset gagal, data lama dipertahankan: '.$e->getMessage());

            return false;
        }

        return true;
    }
}
