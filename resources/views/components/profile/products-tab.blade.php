@props([
    'products',
    'tab' => 'dijual',
    'profileUrl',
    'status' => 'semua',
    'sort' => 'terbaru',
])

@php
    $statusLabels = [
        'semua' => 'Semua Produk',
        'dijual' => 'Produk Dijual',
        'terjual' => 'Produk Terjual',
    ];
    $currentStatusLabel = $statusLabels[$status] ?? 'Semua Produk';

    $sortLabels = [
        'terbaru' => 'Terbaru',
        'terlama' => 'Terlama',
        'diminati' => 'Paling Diminati',
    ];
    $currentSortLabel = $sortLabels[$sort] ?? 'Terbaru';

    $filterUrl = function (array $params) use ($profileUrl, $tab, $status, $sort): string {
        $activeTab = $params['tab'] ?? $tab;
        $query = ['tab' => $activeTab];

        if ($activeTab !== 'favorit') {
            $query['status'] = $params['status'] ?? $status;
        }

        $query['sort'] = $params['sort'] ?? $sort;

        return $profileUrl
            . (str_contains($profileUrl, '?') ? '&' : '?')
            . http_build_query($query);
    };
@endphp

<section class="mt-6">
    <div class="flex items-center justify-between gap-3">
        @if ($tab === 'favorit')
            <h2 class="text-base font-extrabold text-neutral-900">Barang Favorit</h2>
        @else
            <div class="relative">
                <button type="button" id="product-status-toggle" aria-expanded="false"
                    class="group inline-flex items-center gap-1.5 text-base font-extrabold text-neutral-900 transition hover:text-lime-700 cursor-pointer">
                    <span>{{ $currentStatusLabel }}</span>
                    <svg viewBox="0 0 24 24"
                        class="size-4 fill-none stroke-current stroke-[2.5] text-neutral-700 transition-transform duration-200 group-aria-expanded:rotate-180 group-hover:text-lime-700">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m6 9 6 6 6-6" />
                    </svg>
                </button>

                <div id="product-status-menu"
                    class="absolute left-0 top-full z-30 mt-1.5 hidden w-44 overflow-hidden rounded-xl border border-neutral-200 bg-white py-1 shadow-xl ring-1 ring-black/5">
                    @foreach ($statusLabels as $statusKey => $statusLabel)
                        <a href="{{ $filterUrl(['status' => $statusKey]) }}"
                            class="flex items-center justify-between px-3.5 py-2 text-xs font-semibold transition {{ $status === $statusKey ? 'bg-lime-50 text-lime-700' : 'text-neutral-700 hover:bg-neutral-100' }}">
                            <span>{{ $statusLabel }}</span>
                            @if ($status === $statusKey)
                                <svg viewBox="0 0 24 24" class="size-3.5 fill-none stroke-current stroke-[2.5]">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m5 13 4 4L19 7" />
                                </svg>
                            @endif
                        </a>
                    @endforeach
                </div>
            </div>
        @endif

        <div class="relative">
            <button type="button" id="product-sort-toggle" aria-expanded="false"
                class="group flex items-center gap-1.5 rounded-lg border border-neutral-300 bg-white px-3 py-1.5 text-xs font-semibold text-neutral-800 transition hover:bg-neutral-100 cursor-pointer">
                <span>Urutkan: <strong>{{ $currentSortLabel }}</strong></span>
                <svg viewBox="0 0 24 24"
                    class="size-3.5 fill-none stroke-current stroke-2 transition-transform duration-200 group-aria-expanded:rotate-180">
                    <path d="m6 9 6 6 6-6" />
                </svg>
            </button>

            <div id="product-sort-menu"
                class="absolute right-0 top-full z-30 mt-1.5 hidden w-44 overflow-hidden rounded-xl border border-neutral-200 bg-white py-1 shadow-xl ring-1 ring-black/5">
                @foreach ($sortLabels as $sortKey => $sortLabel)
                    <a href="{{ $filterUrl(['sort' => $sortKey]) }}"
                        class="flex items-center justify-between px-3.5 py-2 text-xs font-semibold transition {{ $sort === $sortKey ? 'bg-lime-50 text-lime-700' : 'text-neutral-700 hover:bg-neutral-100' }}">
                        <span>{{ $sortLabel }}</span>
                        @if ($sort === $sortKey)
                            <svg viewBox="0 0 24 24" class="size-3.5 fill-none stroke-current stroke-[2.5]">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m5 13 4 4L19 7" />
                            </svg>
                        @endif
                    </a>
                @endforeach
            </div>
        </div>
    </div>

    <div class="mt-4 grid grid-cols-2 gap-4 md:grid-cols-4" @if ($tab === 'favorit') data-favorites-grid @endif>
        @forelse ($products as $product)
            @if ($tab === 'favorit')
                <div data-favorite-card="{{ $product->id }}">
                    <x-product-card :product="$product" />
                </div>
            @else
                <x-product-card :product="$product" />
            @endif
        @empty
            <div class="col-span-full my-8 text-center text-xs text-neutral-500">
                Tidak ada produk dalam daftar ini.
            </div>
        @endforelse
    </div>
</section>