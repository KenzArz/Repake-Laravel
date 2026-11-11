<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class NotificationController extends Controller
{
    /**
     * Daftar notifikasi user (terbaru dulu).
     */
    public function index(): View
    {
        $user = auth()->user();

        return view('notifications.index', [
            'notifications' => $user->notifications()->latest()->limit(20)->get(),
            'unread' => $user->unreadNotifications()->count(),
        ]);
    }

    /**
     * Tandai semua notifikasi sudah dibaca.
     */
    public function markAllRead(): RedirectResponse
    {
        auth()->user()->unreadNotifications->each->markAsRead();

        return back()->with('info', 'Semua notifikasi ditandai sudah dibaca.');
    }
}
