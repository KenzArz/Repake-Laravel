<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;

/**
 * Mengubah path media lokal menjadi URL absolut.
 *
 * Aplikasi mobile membutuhkan URL penuh (bukan relay path seperti
 * "/storage/...") agar bisa dirender lewat Image.network.
 */
final class MediaUrl
{
    public static function make(?string $path): ?string
    {
        if ($path === null || trim($path) === '') {
            return null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        if (str_starts_with($path, '//')) {
            return 'https:'.$path;
        }

        if (str_starts_with($path, '/')) {
            return url(ltrim($path, '/'));
        }

        if (Storage::disk('public')->exists($path)) {
            return url((string) Storage::url($path));
        }

        if (file_exists(public_path($path))) {
            return url(ltrim($path, '/'));
        }

        // Cadangan: asumsikan berada di disk publik.
        return url('storage/'.ltrim($path, '/'));
    }
}
