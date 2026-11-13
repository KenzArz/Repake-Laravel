<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProductImageStorage
{
    public const MAX_IMAGES = 3;

    private const DISK = 'public';

    private const DIRECTORY = 'products';

    /**
     * Batas piksel yang aman untuk didekode GD di bawah memory_limit 128M.
     * Gambar di atas batas ini disimpan apa adanya tanpa konversi WebP.
     */
    private const MAX_DECODE_PIXELS = 12_000_000;

    private const MAX_SIDE = 1920;

    private const WEBP_QUALITY = 80;

    /**
     * File gambar yang valid dari request, apa pun bentuknya (single file atau array).
     *
     * @return Collection<int, UploadedFile>
     */
    public function validFiles(mixed $files): Collection
    {
        $items = match (true) {
            $files instanceof UploadedFile => [$files],
            is_array($files) => $files,
            default => [],
        };

        return collect($items)
            ->filter(fn ($file): bool => $file instanceof UploadedFile && $file->isValid())
            ->values();
    }

    /**
     * Simpan gambar ke disk publik. Gambar dikonversi ke WebP bila memungkinkan.
     *
     * @param  Collection<int, UploadedFile>  $files
     * @return array<int, string> path relatif pada disk publik
     */
    public function storeAll(Collection $files): array
    {
        $paths = [];

        foreach ($files as $file) {
            $path = $this->storeFile($file);

            if ($path !== null) {
                $paths[] = $path;
            }
        }

        return $paths;
    }

    /**
     * Simpan satu gambar ke disk publik, dikonversi ke WebP bila memungkinkan.
     */
    public function storeFile(UploadedFile $file): ?string
    {
        try {
            $path = $this->toWebp($file) ?? $file->store(self::DIRECTORY, self::DISK);

            return is_string($path) && $path !== '' ? $path : null;
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Hapus gambar yang sudah tersimpan, dipakai saat transaksi dibatalkan.
     *
     * @param  array<int, string>  $paths
     */
    public function delete(array $paths): void
    {
        if ($paths !== []) {
            Storage::disk(self::DISK)->delete($paths);
        }
    }

    private function toWebp(UploadedFile $file): ?string
    {
        $size = @getimagesize($file->getRealPath());

        // Resolusi terlalu besar: jangan didekode agar tidak kehabisan memori.
        // Simpan file aslinya sebagai fallback.
        if ($size === false || $size[0] < 1 || $size[1] < 1) {
            return null;
        }

        if ($size[0] * $size[1] > self::MAX_DECODE_PIXELS) {
            return null;
        }

        $image = @imagecreatefromstring((string) file_get_contents($file->getRealPath()));

        if ($image === false) {
            return null;
        }

        $image = $this->downscale($image);
        imagealphablending($image, true);
        imagesavealpha($image, true);

        ob_start();
        $converted = imagewebp($image, null, self::WEBP_QUALITY);
        $data = ob_get_clean();
        imagedestroy($image);

        if (! $converted || ! is_string($data) || $data === '') {
            return null;
        }

        $path = self::DIRECTORY.'/'.Str::random(40).'.webp';

        if (! Storage::disk(self::DISK)->put($path, $data)) {
            return null;
        }

        return $path;
    }

    /**
     * Perkecil gambar agar sisi terpanjang tidak melebihi MAX_SIDE.
     */
    private function downscale(\GdImage $image): \GdImage
    {
        $width = imagesx($image);
        $height = imagesy($image);

        if ($width <= self::MAX_SIDE && $height <= self::MAX_SIDE) {
            return $image;
        }

        $ratio = min(self::MAX_SIDE / $width, self::MAX_SIDE / $height);
        $resized = imagescale(
            $image,
            max(1, (int) round($width * $ratio)),
            max(1, (int) round($height * $ratio)),
        );

        if ($resized === false) {
            return $image;
        }

        imagedestroy($image);

        return $resized;
    }
}
