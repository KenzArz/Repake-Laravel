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
            $path = $this->store($file);

            if ($path !== null) {
                $paths[] = $path;
            }
        }

        return $paths;
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

    private function store(UploadedFile $file): ?string
    {
        try {
            return $this->toWebp($file) ?? $file->store(self::DIRECTORY, self::DISK);
        } catch (\Throwable) {
            return null;
        }
    }

    private function toWebp(UploadedFile $file): ?string
    {
        $image = @imagecreatefromstring((string) file_get_contents($file->getRealPath()));

        if ($image === false) {
            return null;
        }

        $path = self::DIRECTORY.'/'.Str::random(40).'.webp';

        imagealphablending($image, true);
        imagesavealpha($image, true);

        ob_start();
        imagewebp($image, null, 80);
        $data = ob_get_clean();
        imagedestroy($image);

        Storage::disk(self::DISK)->put($path, $data);

        return $path;
    }
}
