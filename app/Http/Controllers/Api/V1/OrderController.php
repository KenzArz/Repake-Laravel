<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Models\Product;
use App\Notifications\OrderCancelled;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    private const ALLOWED_ROLES = ['pembelian', 'penjualan'];

    public function index(Request $request): JsonResponse
    {
        $userId = (string) $request->user()->id;
        $role = in_array((string) $request->query('role'), self::ALLOWED_ROLES, true)
            ? (string) $request->query('role')
            : 'pembelian';

        $allowedStatuses = ['semua', Order::STATUS_DISEPAKATI, Order::STATUS_SELESAI, Order::STATUS_BATAL];
        $status = in_array((string) $request->query('status'), $allowedStatuses, true)
            ? (string) $request->query('status')
            : 'semua';
        $roleColumn = $role === 'penjualan' ? 'seller_id' : 'buyer_id';

        $query = Order::query()
            ->with(['product.primaryImage', 'buyer', 'seller'])
            ->withExists(['reviews as has_reviewed' => fn ($q) => $q->where('reviewer_id', $userId)])
            ->where($roleColumn, $userId)
            ->when($status !== 'semua', fn ($q) => $q->where('status', $status))
            ->latest();

        $paginated = $query->paginate(10);

        $activeBuyingCount = Order::query()->where('buyer_id', $userId)->where('status', Order::STATUS_DISEPAKATI)->count();
        $activeSellingCount = Order::query()->where('seller_id', $userId)->where('status', Order::STATUS_DISEPAKATI)->count();

        return ApiResponse::success(OrderResource::collection($paginated->items()), meta: [
            'current_page' => $paginated->currentPage(),
            'per_page' => $paginated->perPage(),
            'total' => $paginated->total(),
            'last_page' => $paginated->lastPage(),
            'from' => $paginated->firstItem(),
            'to' => $paginated->lastItem(),
            'active_buying_count' => $activeBuyingCount,
            'active_selling_count' => $activeSellingCount,
        ]);
    }

    public function updateStatus(Request $request, Order $order): JsonResponse
    {
        $userId = (string) $request->user()->id;
        $isBuyer = (string) $order->buyer_id === $userId;
        $isSeller = (string) $order->seller_id === $userId;

        abort_unless($isBuyer || $isSeller, 403);

        $validated = $request->validate([
            'status' => ['required', 'in:'.Order::STATUS_SELESAI.','.Order::STATUS_BATAL],
        ]);

        $nextStatus = $validated['status'];

        abort_unless($order->status === Order::STATUS_DISEPAKATI, 409, 'Status transaksi ini sudah tidak dapat diubah.');

        if ($nextStatus === Order::STATUS_SELESAI) {
            abort_unless($isSeller, 403, 'Hanya penjual yang bisa menandai produk terjual.');

            $product = $order->product;
            abort_if($product === null, 404, 'Produk tidak ditemukan.');
            abort_if($product->status !== Product::STATUS_DIJUAL, 409, 'Produk ini sudah tidak dijual.');

            DB::transaction(function () use ($order, $product): void {
                $product->update(['status' => Product::STATUS_TERJUAL]);
                $order->update(['status' => Order::STATUS_SELESAI, 'completed_at' => now()]);

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

            $order->load(['product.primaryImage', 'buyer', 'seller']);

            return ApiResponse::success(new OrderResource($order), 'Produk ditandai terjual dan transaksi selesai.');
        }

        $order->update(['status' => Order::STATUS_BATAL]);

        $counterpart = $isSeller ? $order->buyer : $order->seller;
        if ($order->product !== null) {
            $counterpart?->notify(new OrderCancelled($order->product->judul, $order->conversation_id));
        }

        $order->load(['product.primaryImage', 'buyer', 'seller']);

        return ApiResponse::success(new OrderResource($order), 'Kesepakatan transaksi telah dibatalkan.');
    }
}
