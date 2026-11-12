<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use App\Models\Category;
use App\Models\Report;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Tab yang tersedia di dashboard admin.
     */
    private const TABS = ['laporan', 'kategori', 'banner'];

    public function index(Request $request): View
    {
        $tab = (string) $request->query('tab', 'laporan');

        if (! in_array($tab, self::TABS, true)) {
            $tab = 'laporan';
        }

        // Membuka kembali detail laporan setelah aksi moderasi.
        if ($request->filled('report')) {
            $tab = 'laporan';
        }

        $reports = Report::query()
            ->with([
                'product' => fn ($query) => $query
                    ->withTrashed()
                    ->with(['primaryImage', 'seller']),
                'reporter',
                'reviewer',
            ])
            ->latest()
            ->paginate(10)
            ->withQueryString();

        $categories = Category::query()
            ->withCount('products')
            ->urut()
            ->get();

        $banners = Banner::query()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        return view('admin.dashboard', [
            'tab' => $tab,
            'reports' => $reports,
            'categories' => $categories,
            'banners' => $banners,
            'openReportId' => $request->integer('report') ?: null,
        ]);
    }
}
