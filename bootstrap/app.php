<?php

use App\Http\Middleware\EnsureUserIsNotBanned;
use App\Http\Middleware\IsAdmin;
use App\Http\Middleware\TouchLastSeen;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\PostTooLargeException;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->redirectGuestsTo(function (Request $request): string {
            $request->session()->flash('info', 'Silakan masuk dengan akun Google UNSIKA untuk melanjutkan.');

            return route('home');
        });

        $middleware->alias([
            'admin' => IsAdmin::class,
            'banned' => EnsureUserIsNotBanned::class,
        ]);

        $middleware->web(append: [
            TouchLastSeen::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        // Unggahan melebihi batas PHP (upload_max_filesize/post_max_size) ditolak
        // sebelum validasi berjalan. Beri pesan yang jelas, bukan halaman 413.
        $exceptions->render(function (PostTooLargeException $exception, Request $request) {
            $limit = ini_get('upload_max_filesize') ?: '2M';
            $message = "Ukuran berkas melebihi batas server ({$limit}). Pilih gambar yang lebih kecil.";

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => $message,
                    'errors' => ['image' => [$message]],
                ], 413);
            }

            return back()->withInput()->withErrors(['image' => $message]);
        });
    })->create();
