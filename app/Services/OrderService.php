<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Product;
use App\Notifications\OrderCancelled;
use App\Notifications\OrderCompleted;
use Illuminate\Support\Facades\DB;

/**
 * Finalisasi siklus hidup order (penyelesaian & pembatalan) agar aturan
 * transaksional identik antara web dan API tanpa duplikasi logika.
 */
class OrderService
{
    /**
     * Penjual menyelesaikan transaksi setelah COD / serah-terima barang.
     *
     * - `stok_habis`     : order selesai, produk ditandai terjual, order lain dibatalkan.
     * - `stok_tersedia`  : order selesai, produk tetap dijual, order lain tetap berjalan.
     */
    public function complete(Order $order, string $sellerId, string $completionType): void
    {
        abort_unless(in_array($completionType, [Order::COMPLETION_STOCK_OUT, Order::COMPLETION_STOCK_AVAILABLE], true), 422, 'Jenis penyelesaian tidak valid.');

        DB::transaction(function () use ($order, $sellerId, $completionType): void {
            /** @var Order $locked */
            $locked = Order::query()->lockForUpdate()->findOrFail($order->getKey());

            abort_unless($locked->seller_id === $sellerId, 403, 'Hanya penjual yang bisa menyelesaikan transaksi.');
            abort_unless($locked->status === Order::STATUS_DISEPAKATI, 409, 'Transaksi ini sudah tidak dapat diubah.');

            $product = $locked->product;
            abort_if($product === null, 404, 'Produk tidak ditemukan.');

            if ($completionType === Order::COMPLETION_STOCK_OUT) {
                abort_if($product->status !== Product::STATUS_DIJUAL, 409, 'Produk ini sudah tidak dijual.');
            }

            $locked->update([
                'status' => Order::STATUS_SELESAI,
                'completed_at' => now(),
                'completion_type' => $completionType,
            ]);

            if ($completionType === Order::COMPLETION_STOCK_OUT) {
                $product->update(['status' => Product::STATUS_TERJUAL]);

                $others = Order::query()
                    ->where('product_id', $product->id)
                    ->whereKeyNot($locked->getKey())
                    ->where('status', Order::STATUS_DISEPAKATI)
                    ->get();

                foreach ($others as $other) {
                    $other->update([
                        'status' => Order::STATUS_BATAL,
                        'cancelled_by' => 'system',
                        'cancel_reason' => Order::CANCEL_REASON_SOLD_ELSEWHERE,
                    ]);

                    $other->buyer?->notify(new OrderCancelled(
                        $product->judul,
                        $other->conversation_id,
                        Order::CANCEL_REASON_SOLD_ELSEWHERE,
                    ));
                }
            }

            $locked->buyer?->notify(new OrderCompleted($locked, $completionType));
        });
    }

    /**
     * Pembatalan kesepakatan oleh pembeli atau penjual sebelum selesai.
     */
    public function cancel(Order $order, string $actorId, ?string $reason = null): void
    {
        DB::transaction(function () use ($order, $actorId, $reason): void {
            /** @var Order $locked */
            $locked = Order::query()->lockForUpdate()->findOrFail($order->getKey());

            $isSeller = $locked->seller_id === $actorId;
            $isBuyer = $locked->buyer_id === $actorId;

            abort_unless($isSeller || $isBuyer, 403, 'Anda bukan pihak pada transaksi ini.');
            abort_unless($locked->status === Order::STATUS_DISEPAKATI, 409, 'Transaksi ini sudah tidak dapat diubah.');

            $locked->update([
                'status' => Order::STATUS_BATAL,
                'cancelled_by' => $isSeller ? 'penjual' : 'pembeli',
                'cancel_reason' => $reason,
            ]);

            $counterpart = $isSeller ? $locked->buyer : $locked->seller;

            if ($locked->product !== null) {
                $counterpart?->notify(new OrderCancelled(
                    $locked->product->judul,
                    $locked->conversation_id,
                    $reason,
                ));
            }
        });
    }
}