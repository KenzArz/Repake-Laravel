<?php

namespace App\Http\Controllers\Demo;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Throwable;

/**
 * Tombol "Reset Data Demo" pada panel juri.
 *
 * Reset berjalan tanpa DDL: data demo dihapus lalu diisi ulang dalam satu
 * transaksi oleh `demo:reset`. Karena sesi tidak ikut terhapus, respons
 * tetap diarahkan ke route login persona admin agar sesi baru langsung
 * terbentuk dan juri mendarat di dashboard.
 */
class DemoResetController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        abort_unless(config('demo.enabled'), 404);

        $user = $request->user();
        $adminEmail = config('demo.personas.admin');

        abort_unless(
            $user !== null && $adminEmail !== null && $user->email === $adminEmail,
            403,
            'Hanya persona admin yang boleh mereset data demo.',
        );

        if (function_exists('set_time_limit')) {
            set_time_limit(120);
        }

        try {
            $exitCode = Artisan::call('demo:reset', ['--force' => true]);
        } catch (Throwable $e) {
            report($e);

            return back()->with('error', 'Reset data demo gagal dijalankan. Data lama tidak diubah, silakan coba lagi.');
        }

        if ($exitCode !== 0) {
            return back()->with('error', 'Reset data demo gagal dijalankan. Data lama tidak diubah, silakan coba lagi.');
        }

        return redirect()
            ->route('demo.login', ['persona' => 'admin', 'to' => '/admin/dashboard'])
            ->with('info', 'Data demo berhasil direset ke kondisi awal.');
    }
}
