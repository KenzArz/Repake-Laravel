<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Notifications\Notification;

/**
 * Notifikasi kepada pembeli bahwa transaksi telah diselesaikan penjual
 * setelah COD.
 */
class OrderCompleted extends Notification
{
    public function __construct(
        public Order $order,
        public string $completionType,
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
        $stockText = $this->completionType === Order::COMPLETION_STOCK_OUT
            ? 'Produk sudah ditandai habis dan iklan ditutup.'
            : 'Produk ini masih tersedia dan tetap ditayangkan penjual.';

        return [
            'kind' => 'order_completed',
            'title' => 'Transaksi selesai 🎉',
            'body' => "Penjual telah menyelesaikan transaksi \"{$this->order->product?->judul}\" setelah COD. {$stockText}",
            'url' => route('orders.index', ['view' => 'riwayat']),
            'conversation_id' => $this->order->conversation_id,
        ];
    }
}