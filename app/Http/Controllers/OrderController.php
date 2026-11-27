<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class OrderController extends Controller
{
    private const ALLOWED_ROLES = ['pembelian', 'penjualan'];

    private const ALLOWED_VIEWS = ['aktif', 'riwayat'];

    private const ALLOWED_LIST_STATUSES = ['semua', Order::STATUS_SELESAI, Order::STATUS_BATAL];

    public function __construct(private readonly OrderService $orders) {}

    public function index(Request $request): View
    {
        session(['orders_last_seen_at' => now()]);

        $userId = (string) Auth::id();
        $role = in_array($request->query('role'), self::ALLOWED_ROLES, true)
            ? (string) $request->query('role')
            : 'pembelian';

        $view = in_array((string) $request->query('view'), self::ALLOWED_VIEWS, true)
            ? (string) $request->query('view')
            : 'aktif';

        // Filter status hanya berarti pada bagian Riwayat (aktif selalu menunggu COD).
        $status = $view === 'riwayat'
            && in_array((string) $request->query('status'), self::ALLOWED_LIST_STATUSES, true)
            ? (string) $request->query('status')
            : 'semua';

        $roleColumn = $role === 'penjualan' ? 'seller_id' : 'buyer_id';

        $query = Order::query()
            ->with(['product.primaryImage', 'buyer', 'seller'])
            ->withExists([
                'reviews as has_reviewed' => fn ($q) => $q->where('reviewer_id', $userId),
            ])
            ->where($roleColumn, $userId);

        if ($view === 'aktif') {
            $query->where('status', Order::STATUS_DISEPAKATI);
        } else {
            $query->whereIn('status', [Order::STATUS_SELESAI, Order::STATUS_BATAL]);
            if ($status !== 'semua') {
                $query->where('status', $status);
            }
        }

        $orders = $query->latest()->paginate(10)->withQueryString();

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
            'view' => $view,
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
            'status' => ['required', 'in:'.Order::STATUS_SELESAI.','.Order::STATUS_BATAL],
            'completion_type' => ['nullable', 'in:'.Order::COMPLETION_STOCK_OUT.','.Order::COMPLETION_STOCK_AVAILABLE],
            'cancel_reason' => ['nullable', 'string', 'max:50'],
        ]);

        if ($validated['status'] === Order::STATUS_SELESAI) {
            // Pembeli boleh menyelesaikan sendiri dengan mengonfirmasi barang
            // diterima, supaya ulasan tidak bergantung pada penjual.
            if ($isBuyer) {
                $this->orders->confirmReceipt($order, $userId);

                return back()->with('success', 'Terima kasih! Transaksi selesai dan Anda dapat memberi ulasan.');
            }

            abort_unless($isSeller, 403, 'Hanya pembeli atau penjual yang bisa menyelesaikan transaksi.');

            // Transaksi sudah selesai (mis. dikonfirmasi pembeli) dan penjual
            // ingin menutup iklan karena stok benar-benar habis.
            if ($order->status === Order::STATUS_SELESAI) {
                abort_unless(
                    ($validated['completion_type'] ?? null) === Order::COMPLETION_STOCK_OUT,
                    422,
                    'Transaksi sudah selesai. Pilih "Stok habis" untuk menutup iklan.',
                );

                $this->orders->markStockOut($order, $userId);

                return back()->with('success', 'Produk ditandai habis. Order lain yang masih menunggu COD dibatalkan.');
            }

            abort_unless(filled($validated['completion_type'] ?? null), 422, 'Pilih status stok untuk menyelesaikan transaksi.');

            $this->orders->complete($order, $userId, $validated['completion_type']);

            return back()->with(
                'success',
                $validated['completion_type'] === Order::COMPLETION_STOCK_OUT
                    ? 'Transaksi selesai. Produk sudah ditandai habis dan Anda dapat memberi ulasan.'
                    : 'Transaksi selesai. Produk masih tersedia dan tetap ditayangkan. Anda dapat memberi ulasan.',
            );
        }

        $this->orders->cancel($order, $userId, $validated['cancel_reason'] ?? null);

        return back()->with('success', 'Kesepakatan transaksi telah dibatalkan.');
    }
}