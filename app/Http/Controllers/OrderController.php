<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use App\Notifications\OrderCancelled;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class OrderController extends Controller
{
    private const ALLOWED_ROLES = ['pembelian', 'penjualan'];

    public function index(Request $request): View
    {
        session(['orders_last_seen_at' => now()]);

        $userId = (string) Auth::id();
        $role = in_array($request->query('role'), self::ALLOWED_ROLES, true)
            ? (string) $request->query('role')
            : 'pembelian';

        $allowedStatuses = [
            'semua',
            Order::STATUS_DISEPAKATI,
            Order::STATUS_SELESAI,
            Order::STATUS_BATAL,
        ];

        $status = in_array($request->query('status'), $allowedStatuses, true)
            ? (string) $request->query('status')
            : 'semua';

        $roleColumn = $role === 'penjualan' ? 'seller_id' : 'buyer_id';

        $orders = Order::query()
            ->with(['product.primaryImage', 'buyer', 'seller'])
            ->withExists([
                'reviews as has_reviewed' => fn($query) => $query->where('reviewer_id', $userId),
            ])
            ->where($roleColumn, $userId)
            ->when($status !== 'semua', fn($query) => $query->where('status', $status))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        $activeBuyingCount = Order::query()
            ->where('buyer_id', $userId)
            ->where('status', Order::STATUS_DISEPAKATI)
            ->count();

        $activeSellingCount = Order::query()
            ->where('seller_id', $userId)
            ->where('status', Order::STATUS_DISEPAKATI)
            ->count();

        return view('orders.index', [
            'orders' => $orders,
            'role' => $role,
            'status' => $status,
            'activeBuyingCount' => $activeBuyingCount,
            'activeSellingCount' => $activeSellingCount,
        ]);
    }

    public function updateStatus(Request $request, Order $order): RedirectResponse
    {
        $userId = (string) Auth::id();
        $isBuyer = (string) $order->buyer_id === $userId;
        $isSeller = (string) $order->seller_id === $userId;

        abort_unless($isBuyer || $isSeller, 403);

        $validated = $request->validate([
            'status' => ['required', 'in:' . Order::STATUS_SELESAI . ',' . Order::STATUS_BATAL],
        ]);

        $nextStatus = $validated['status'];

        abort_unless($order->status === Order::STATUS_DISEPAKATI, 409, 'Status transaksi ini sudah tidak dapat diubah.');

        if ($nextStatus === Order::STATUS_SELESAI) {
            // Samakan persis dengan ChatController::markSold()
            abort_unless($isSeller, 403, 'Hanya penjual yang bisa menandai produk terjual.');

            $product = $order->product;
            abort_if($product === null, 404, 'Produk tidak ditemukan.');
            abort_if($product->status !== Product::STATUS_DIJUAL, 409, 'Produk ini sudah tidak dijual.');

            DB::transaction(function () use ($order, $product): void {
                $product->update(['status' => Product::STATUS_TERJUAL]);

                $order->update([
                    'status' => Order::STATUS_SELESAI,
                    'completed_at' => now(),
                ]);

                $others = Order::query()
                    ->where('product_id', $product->id)
                    ->whereKeyNot($order->getKey())
                    ->where('status', Order::STATUS_DISEPAKATI)
                    ->get();

                foreach ($others as $other) {
                    $other->update(['status' => Order::STATUS_BATAL]);
                    $other->buyer?->notify(new OrderCancelled($product->judul, $other->conversation_id));
                }
            });

            return back()->with('success', 'Produk ditandai terjual dan transaksi selesai. Anda sekarang dapat memberi ulasan.');
        }

        // Pembatalan kesepakatan oleh pembeli atau penjual
        $order->update(['status' => Order::STATUS_BATAL]);

        $counterpart = $isSeller ? $order->buyer : $order->seller;
        if ($order->product !== null) {
            $counterpart?->notify(new OrderCancelled($order->product->judul, $order->conversation_id));
        }

        return back()->with('success', 'Kesepakatan transaksi telah dibatalkan.');
    }
}
