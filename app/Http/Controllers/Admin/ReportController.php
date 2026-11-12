<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Report;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ReportController extends Controller
{
    /**
     * Detail laporan untuk modal card di dashboard.
     */
    public function show(Report $report): View
    {
        $report->load([
            'product' => fn ($query) => $query
                ->withTrashed()
                ->with(['primaryImage', 'seller']),
            'reporter',
            'reviewer',
        ]);

        return view('admin.partials.report-detail', ['report' => $report]);
    }

    /**
     * Tindak lanjut laporan: ubah status, suspend produk, atau ban penjual.
     */
    public function moderate(Request $request, Report $report): RedirectResponse
    {
        $data = $request->validate([
            'action' => ['required', 'in:review,reject,resolve,suspend_product,restore_product,ban_seller,unban_seller'],
        ]);

        $report->loadMissing([
            'product' => fn ($query) => $query->withTrashed(),
            'product.seller',
        ]);

        $product = $report->product;
        $seller = $product?->seller;

        $message = match ($data['action']) {
            'review' => $this->setReportStatus($report, Report::STATUS_REVIEWED, 'Laporan ditandai sudah ditinjau.'),
            'reject' => $this->setReportStatus($report, Report::STATUS_REJECTED, 'Laporan ditolak.'),
            'resolve' => $this->setReportStatus($report, Report::STATUS_RESOLVED, 'Laporan diselesaikan.'),
            'suspend_product' => $this->suspendProduct($report, $product),
            'restore_product' => $this->restoreProduct($report, $product),
            'ban_seller' => $this->banSeller($report, $seller),
            'unban_seller' => $this->unbanSeller($seller),
        };

        return redirect()
            ->route('admin.dashboard', ['tab' => 'laporan', 'report' => $report->id])
            ->with('info', $message);
    }

    /**
     * Tandai laporan dengan status tertentu beserta jejak reviewer.
     */
    private function setReportStatus(Report $report, string $status, string $message): string
    {
        $report->forceFill([
            'status' => $status,
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
        ])->save();

        return $message;
    }

    private function suspendProduct(Report $report, ?Product $product): string
    {
        if (! $product || $product->trashed()) {
            return 'Produk sudah dihapus, tidak bisa disuspend.';
        }

        if ($product->status === Product::STATUS_DITANGGUIHKAN) {
            return 'Produk ini sudah dalam status ditangguhkan.';
        }

        $product->update(['status' => Product::STATUS_DITANGGUIHKAN]);
        $this->setReportStatus($report, Report::STATUS_RESOLVED, '');

        return 'Produk berhasil disuspend dan tidak lagi tampil di katalog.';
    }

    private function restoreProduct(Report $report, ?Product $product): string
    {
        if (! $product || $product->trashed()) {
            return 'Produk sudah dihapus, tidak bisa diaktifkan kembali.';
        }

        if ($product->seller?->is_banned) {
            return 'Penjual masih dibanned. Aktifkan akun penjual terlebih dahulu.';
        }

        $product->update(['status' => Product::STATUS_DIJUAL]);
        $this->setReportStatus($report, Report::STATUS_RESOLVED, '');

        return 'Produk diaktifkan kembali dan tampil di katalog.';
    }

    private function banSeller(Report $report, ?User $seller): string
    {
        if (! $seller) {
            return 'Penjual tidak ditemukan.';
        }

        if ($seller->isAdmin()) {
            return 'Akun admin tidak bisa dibanned.';
        }

        if ((int) $seller->id === (int) auth()->id()) {
            return 'Anda tidak bisa membanned akun sendiri.';
        }

        DB::transaction(function () use ($report, $seller): void {
            $seller->forceFill(['is_banned' => true])->save();
            $this->setReportStatus($report, Report::STATUS_RESOLVED, '');
        });

        return "Akun {$seller->name} berhasil dibanned dan produknya disembunyikan.";
    }

    private function unbanSeller(?User $seller): string
    {
        if (! $seller) {
            return 'Penjual tidak ditemukan.';
        }

        $seller->forceFill(['is_banned' => false])->save();

        return "Akun {$seller->name} diaktifkan kembali.";
    }
}
