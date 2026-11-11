<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Akun yang diblokir admin tidak boleh memakai fitur apa pun,
 * meskipun session-nya masih aktif.
 */
class EnsureUserIsNotBanned
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->is_banned) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()
                ->route('home')
                ->with('info', 'Akun Anda diblokir. Hubungi admin RePake untuk informasi lebih lanjut.');
        }

        return $next($request);
    }
}
