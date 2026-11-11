<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ProductPublisher
{
    public function __construct(private readonly ProductImageStorage $images) {}

    /**
     * Buat produk beserta gambarnya dalam satu transaksi. Gambar yang sudah
     * tersimpan akan dihapus bila transaksi gagal.
     *
     * @param  array<string, mixed>  $attributes
     * @param  Collection<int, UploadedFile>  $files
     *
     * @throws RuntimeException bila tidak ada gambar yang berhasil disimpan
     */
    public function publish(int $userId, array $attributes, Collection $files): Product
    {
        $stored = [];

        try {
            return DB::transaction(function () use ($userId, $attributes, $files, &$stored): Product {
                $product = Product::create([
                    'user_id' => $userId,
                    ...$attributes,
                ]);

                $paths = $this->images->storeAll($files);
                $stored = $paths;

                if ($paths === []) {
                    throw new RuntimeException('Gagal menyimpan gambar produk.');
                }

                foreach ($paths as $urutan => $path) {
                    ProductImage::create([
                        'product_id' => $product->id,
                        'path' => $path,
                        'is_cover' => $urutan === 0,
                        'urutan' => $urutan,
                    ]);
                }

                return $product;
            });
        } catch (\Throwable $e) {
            $this->images->delete($stored);

            throw $e;
        }
    }
}
