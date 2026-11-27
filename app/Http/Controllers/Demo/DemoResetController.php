<?php

namespace App\Http\Controllers\Demo;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

/**
 * Tombol "Reset Data Demo" pada panel juri.
 *
 * Setelah `migrate:fresh`, seluruh sesi lama ikut terhapus (SESSION_DRIVER
 * database). Karena itu respons diarahkan ke route login persona admin agar
 * sesi baru langsung terbentuk dan juri mendarat di dashboard.
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

        Artisan::call('demo:reset', ['--force' => true]);

        return redirect()
            ->route('demo.login', ['persona' => 'admin', 'to' => '/admin/dashboard'])
            ->with('info', 'Data demo berhasil direset ke kondisi awal.');
    }
}
