<?php

namespace App\Console\Commands;

use Database\Seeders\DemoSeeder;
use Illuminate\Console\Command;

/**
 * Kembalikan instance demo ke kondisi awal.
 *
 * Hanya berjalan saat DEMO_MODE=true supaya perintah ini tidak pernah
 * menghapus data di instance produksi.
 */
class DemoReset extends Command
{
    protected $signature = 'demo:reset {--force : Tanpa konfirmasi}';

    protected $description = 'Hapus seluruh data lalu isi ulang dengan DemoSeeder (khusus instance demo)';

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

        $this->call('migrate:fresh', [
            '--seed' => true,
            '--seeder' => DemoSeeder::class,
            '--force' => true,
        ]);

        $this->components->info('Data demo berhasil direset ke kondisi awal.');

        return self::SUCCESS;
    }
}
