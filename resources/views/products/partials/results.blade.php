<div data-total="{{ $products->total() }}">
    @if ($products->count() > 0)
        <div class="grid grid-cols-2 gap-3 sm:gap-4 md:grid-cols-3 lg:grid-cols-4">
            @foreach ($products as $product)
                <x-product-card :product="$product" />
            @endforeach
        </div>

        <div class="mt-8">
            {{ $products->links() }}
        </div>
    @else
        <div class="my-8 rounded-3xl border border-dashed border-neutral-300 bg-neutral-50/70 px-6 py-16 text-center">
            <div class="mx-auto mb-3 flex size-14 items-center justify-center rounded-full bg-neutral-100 text-neutral-400">
                <svg class="size-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                </svg>
            </div>
            <h3 class="text-sm sm:text-base font-extrabold text-neutral-900">Tidak ada produk yang cocok</h3>
            <p class="mx-auto mt-1 max-w-sm text-xs text-neutral-500">
                Coba ubah kata kunci pencarian atau hapus beberapa filter chip yang aktif.
            </p>
        </div>
    @endif
</div>