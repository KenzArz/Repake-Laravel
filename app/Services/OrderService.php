<?php

namespace App\Services;

use App\Models\Message;
use App\Models\Order;
use App\Models\Product;
use App\Notifications\OrderCancelled;
use App\Notifications\OrderCompleted;
use App\Notifications\OrderReceiptConfirmed;
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
                'completed_by' => Order::COMPLETED_BY_SELLER,
            ]);

            $this->closePendingOffers($locked);

            if ($completionType === Order::COMPLETION_STOCK_OUT) {
                $this->markProductSold($locked, $product);
            }

            $locked->buyer?->notify(new OrderCompleted($locked, $completionType));
        });
    }

    /**
     * Pembeli mengonfirmasi barang sudah diterima. Ini jalur penyelesaian
     * kedua supaya pembeli tidak bergantung pada penjual untuk bisa memberi
     * ulasan. Status produk tidak diubah di sini; keputusan stok tetap milik
     * penjual lewat {@see markStockOut()}.
     */
    public function confirmReceipt(Order $order, string $buyerId): void
    {
        DB::transaction(function () use ($order, $buyerId): void {
            /** @var Order $locked */
            $locked = Order::query()->lockForUpdate()->findOrFail($order->getKey());

            abort_unless($locked->buyer_id === $buyerId, 403, 'Hanya pembeli yang bisa mengonfirmasi penerimaan barang.');
            abort_unless($locked->status === Order::STATUS_DISEPAKATI, 409, 'Transaksi ini sudah tidak dapat diubah.');

            $locked->update([
                'status' => Order::STATUS_SELESAI,
                'completed_at' => now(),
                'completed_by' => Order::COMPLETED_BY_BUYER,
            ]);

            $this->closePendingOffers($locked);

            $locked->seller?->notify(new OrderReceiptConfirmed($locked));
        });
    }

    /**
     * Penjual menutup iklan setelah transaksi selesai (mis. pembeli lebih dulu
     * mengonfirmasi terima, ternyata unit terakhir habis). Order lain yang
     * masih menunggu COD otomatis dibatalkan.
     */
    public function markStockOut(Order $order, string $sellerId): void
    {
        DB::transaction(function () use ($order, $sellerId): void {
            /** @var Order $locked */
            $locked = Order::query()->lockForUpdate()->findOrFail($order->getKey());

            abort_unless($locked->seller_id === $sellerId, 403, 'Hanya penjual yang bisa menandai stok habis.');
            abort_unless($locked->status === Order::STATUS_SELESAI, 409, 'Selesaikan transaksi terlebih dahulu.');
            abort_if($locked->completion_type === Order::COMPLETION_STOCK_OUT, 409, 'Produk sudah ditandai habis.');

            $product = $locked->product;
            abort_if($product === null, 404, 'Produk tidak ditemukan.');
            abort_if($product->status !== Product::STATUS_DIJUAL, 409, 'Produk ini sudah tidak dijual.');

            $locked->update(['completion_type' => Order::COMPLETION_STOCK_OUT]);

            $this->markProductSold($locked, $product);
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

            $this->closePendingOffers($locked);

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

    /**
     * Tandai produk terjual dan batalkan order lain yang masih menunggu COD.
     */
    private function markProductSold(Order $order, Product $product): void
    {
        $product->update(['status' => Product::STATUS_TERJUAL]);

        $others = Order::query()
            ->where('product_id', $product->id)
            ->whereKeyNot($order->getKey())
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

    /**
     * Tawaran yang masih menggantung tidak boleh bisa diterima setelah
     * transaksi selesai/batal, karena akan membuat order baru tanpa sengaja.
     */
    private function closePendingOffers(Order $order): void
    {
        if ($order->conversation_id === null) {
            return;
        }

        Message::query()
            ->where('conversation_id', $order->conversation_id)
            ->where('type', Message::TYPE_OFFER)
            ->where('offer_status', Message::OFFER_PENDING)
            ->update(['offer_status' => Message::OFFER_CANCELLED]);
    }
}
