<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Notifications\Notification;

/**
 * Notifikasi kepada penjual bahwa pembeli sudah mengonfirmasi barang diterima.
 * Dengan begitu transaksi selesai tanpa harus menunggu penjual menekan tombol
 * "Selesaikan", dan pembeli bisa langsung memberi ulasan.
 */
class OrderReceiptConfirmed extends Notification
{
    public function __construct(
        public Order $order,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $buyerName = $this->order->buyer?->name ?? 'Pembeli';
        $productTitle = $this->order->product?->judul ?? 'produk Anda';

        return [
            'kind' => 'order_receipt_confirmed',
            'title' => 'Pembeli mengonfirmasi terima barang',
            'body' => "{$buyerName} menyatakan sudah menerima \"{$productTitle}\" dan transaksi dinyatakan selesai.",
            'url' => route('orders.index', ['role' => 'penjualan', 'view' => 'riwayat']),
            'conversation_id' => $this->order->conversation_id,
        ];
    }
}
