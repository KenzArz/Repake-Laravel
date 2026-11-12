<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class BannerController extends Controller
{
    /**
     * Jumlah minimal banner yang harus tetap aktif di beranda.
     */
    private const MIN_ACTIVE = 3;

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:100'],
            'alt_text' => ['nullable', 'string', 'max:150'],
            'image' => ['required', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
        ], [
            'image.required' => 'Gambar banner wajib diunggah.',
            'image.max' => 'Ukuran gambar banner maksimal 2 MB.',
        ]);

        $path = $request->file('image')->store('banners', 'public');

        Banner::create([
            'title' => $data['title'],
            'image_path' => $path,
            'alt_text' => $data['alt_text'] ?? null,
            'is_active' => true,
            'sort_order' => ((int) Banner::max('sort_order')) + 1,
        ]);

        return back()->with('info', "Banner {$data['title']} ditambahkan.");
    }

    public function toggle(Banner $banner): RedirectResponse
    {
        if ($banner->is_active && $this->activeCount() <= self::MIN_ACTIVE) {
            return back()->with('error', 'Minimal '.self::MIN_ACTIVE.' banner harus tetap aktif di beranda.');
        }

        $banner->update(['is_active' => ! $banner->is_active]);

        return back()->with(
            'info',
            $banner->is_active ? 'Banner diaktifkan.' : 'Banner dinonaktifkan.'
        );
    }

    public function destroy(Banner $banner): RedirectResponse
    {
        if ($banner->is_active && $this->activeCount() <= self::MIN_ACTIVE) {
            return back()->with('error', 'Minimal '.self::MIN_ACTIVE.' banner harus tetap aktif di beranda.');
        }

        if (str_starts_with($banner->image_path, 'banners/')) {
            Storage::disk('public')->delete($banner->image_path);
        }

        $title = $banner->title;
        $banner->delete();

        return back()->with('info', "Banner {$title} dihapus.");
    }

    public function move(Request $request, Banner $banner): RedirectResponse
    {
        $data = $request->validate([
            'direction' => ['required', 'in:up,down'],
        ]);

        $ids = Banner::query()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->pluck('id')
            ->all();

        $index = array_search($banner->id, $ids, true);

        if ($index === false) {
            return back();
        }

        $target = $data['direction'] === 'up' ? $index - 1 : $index + 1;

        if ($target < 0 || $target >= count($ids)) {
            return back();
        }

        [$ids[$index], $ids[$target]] = [$ids[$target], $ids[$index]];

        $this->saveOrder($ids);

        return back()->with('info', 'Urutan banner diperbarui.');
    }

    /**
     * Simpan urutan banner dari hasil drag and drop.
     */
    public function reorder(Request $request): JsonResponse|RedirectResponse
    {
        $data = $request->validate([
            'order' => ['required', 'array', 'min:1'],
            'order.*' => ['integer', 'distinct', 'exists:banners,id'],
        ]);

        $ids = array_map('intval', $data['order']);
        $allIds = Banner::query()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        if (count($ids) !== count($allIds) || array_diff($allIds, $ids) !== []) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Urutan banner tidak lengkap.'], 422);
            }

            return back()->with('error', 'Urutan banner tidak lengkap.');
        }

        $this->saveOrder($ids);

        if ($request->expectsJson()) {
            return response()->json(['ok' => true]);
        }

        return back()->with('info', 'Urutan banner diperbarui.');
    }

    /**
     * Tulis ulang sort_order berdasarkan urutan ID yang diberikan.
     *
     * @param  array<int, int>  $ids
     */
    private function saveOrder(array $ids): void
    {
        DB::transaction(function () use ($ids): void {
            foreach ($ids as $position => $id) {
                Banner::whereKey($id)->update(['sort_order' => $position + 1]);
            }
        });
    }

    private function activeCount(): int
    {
        return Banner::query()->where('is_active', true)->count();
    }
}
