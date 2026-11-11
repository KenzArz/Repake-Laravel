<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LocationController extends Controller
{
    /**
     * Simpan hasil deteksi lokasi ke users.kota.
     */
    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $data = $request->validate([
            'kota' => ['required', 'string', 'max:100'],
        ]);

        $request->user()->update(['kota' => $data['kota']]);

        if ($request->expectsJson()) {
            return response()->json([
                'kota' => $data['kota'],
                'message' => 'Lokasi berhasil disimpan.',
            ]);
        }

        return back()->with('info', 'Lokasi berhasil disimpan: '.$data['kota']);
    }
}
