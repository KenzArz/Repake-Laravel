<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Services\OrderService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    private const ALLOWED_ROLES = ['pembelian', 'penjualan'];

    private const ALLOWED_VIEWS = ['aktif', 'riwayat'];

    private const ALLOWED_LIST_STATUSES = ['semua', Order::STATUS_SELESAI, Order::STATUS_BATAL];

    public function __construct(private readonly OrderService $orders) {}

    public function index(Request $request): JsonResponse
    {
        $userId = (string) $request->user()->id;
        $role = in_array((string) $request->query('role'), self::ALLOWED_ROLES, true)
            ? (string) $request->query('role')
            : 'pembelian';

        $view = in_array((string) $request->query('view'), self::ALLOWED_VIEWS, true)
            ? (string) $request->query('view')
            : 'aktif';

        $status = $view === 'riwayat'
            && in_array((string) $request->query('status'), self::ALLOWED_LIST_STATUSES, true)
            ? (string) $request->query('status')
            : 'semua';

        $roleColumn = $role === 'penjualan' ? 'seller_id' : 'buyer_id';

        $query = Order::query()
            ->with(['product.primaryImage', 'buyer', 'seller'])
            ->withExists(['reviews as has_reviewed' => fn ($q) => $q->where('reviewer_id', $userId)])
            ->where($roleColumn, $userId);

        if ($view === 'aktif') {
            $query->where('status', Order::STATUS_DISEPAKATI);
        } else {
            $query->whereIn('status', [Order::STATUS_SELESAI, Order::STATUS_BATAL]);
            if ($status !== 'semua') {
                $query->where('status', $status);
            }
        }

        $paginated = $query->latest()->paginate(10);

        $activeBuyingCount = Order::query()->where('buyer_id', $userId)->where('status', Order::STATUS_DISEPAKATI)->count();
        $activeSellingCount = Order::query()->where('seller_id', $userId)->where('status', Order::STATUS_DISEPAKATI)->count();

        return ApiResponse::success(OrderResource::collection($paginated->items()), meta: [
            'current_page' => $paginated->currentPage(),
            'per_page' => $paginated->perPage(),
            'total' => $paginated->total(),
            'last_page' => $paginated->lastPage(),
            'from' => $paginated->firstItem(),
            'to' => $paginated->lastItem(),
            'view' => $view,
            'active_buying_count' => $activeBuyingCount,
            'active_selling_count' => $activeSellingCount,
        ]);
    }

    public function updateStatus(Request $request, Order $order): JsonResponse
    {
        $userId = (string) $request->user()->id;

        $validated = $request->validate([
            'status' => ['required', 'in:'.Order::STATUS_SELESAI.','.Order::STATUS_BATAL],
            'completion_type' => ['nullable', 'in:'.Order::COMPLETION_STOCK_OUT.','.Order::COMPLETION_STOCK_AVAILABLE],
            'cancel_reason' => ['nullable', 'string', 'max:50'],
        ]);

        if ($validated['status'] === Order::STATUS_SELESAI) {
            if ((string) $order->buyer_id === $userId) {
                // Pembeli menyelesaikan dengan konfirmasi terima barang.
                $this->orders->confirmReceipt($order, $userId);
            } else {
                abort_unless((string) $order->seller_id === $userId, 403, 'Anda bukan pihak pada transaksi ini.');

                if ($order->status === Order::STATUS_SELESAI) {
                    abort_unless(
                        ($validated['completion_type'] ?? null) === Order::COMPLETION_STOCK_OUT,
                        422,
                        'Transaksi sudah selesai. Pilih "Stok habis" untuk menutup iklan.',
                    );

                    $this->orders->markStockOut($order, $userId);
                } else {
                    abort_unless(filled($validated['completion_type'] ?? null), 422, 'Pilih status stok untuk menyelesaikan transaksi.');

                    $this->orders->complete($order, $userId, $validated['completion_type']);
                }
            }
        } else {
            $this->orders->cancel($order, $userId, $validated['cancel_reason'] ?? null);
        }

        $order->refresh()->load(['product.primaryImage', 'buyer', 'seller']);

        return ApiResponse::success(new OrderResource($order), 'Status transaksi diperbarui.');
    }
}