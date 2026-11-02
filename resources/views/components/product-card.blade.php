@props(['product'])

@php
    $detailUrl = Route::has('products.show') ? route('products.show', $product->id) : '#';
    $favUrl    = Route::has('favorites.toggle') ? route('favorites.toggle', $product->id) : '';
    $badge     = $product->status === 'Dijual'
        ? 'bg-lime-500/90 text-neutral-900'
        : 'bg-neutral-200/90 text-neutral-700';
    $ago = is_string($product->created_at)
        ? $product->created_at
        : ($product->created_at?->locale('id')->diffForHumans(['syntax' => \Carbon\CarbonInterface::DIFF_ABSOLUTE]) . ' lalu' ?? 'Baru saja');
@endphp

<article class="relative flex h-full flex-col rounded-xl bg-white p-2.5">
    {{-- Header penjual --}}
    <div class="flex items-center gap-2 pb-2">
        <span class="size-8 shrink-0 rounded-full bg-neutral-200"></span>
        <div class="min-w-0 flex-1 leading-tight">
            <p class="truncate text-label font-semibold">{{ $product->penjual }}</p>
            <p class="truncate text-caption text-neutral-500">{{ $product->kota }} · {{ $ago }}</p>
        </div>
        {{-- TODO: dropdown menu "Laporkan" --}}
        <button type="button" aria-label="Opsi produk"
                class="relative z-10 grid size-8 shrink-0 place-items-center rounded-full text-neutral-700 transition hover:bg-neutral-100">
            <svg viewBox="0 0 24 24" class="size-[18px] fill-current" aria-hidden="true">
                <circle cx="12" cy="5" r="1.6"/><circle cx="12" cy="12" r="1.6"/><circle cx="12" cy="19" r="1.6"/>
            </svg>
        </button>
    </div>

    {{-- Gambar + badge + favorit --}}
    <div class="relative aspect-[19/20] overflow-hidden rounded-lg bg-neutral-200">
        @if ($product->gambar)
            <img src="{{ asset($product->gambar) }}" alt="{{ $product->judul }}" loading="lazy"
                 class="size-full object-cover" onerror="this.remove()">
        @endif

        <span class="absolute left-2 top-2 rounded-md px-2.5 py-0.5 text-label {{ $badge }}">{{ $product->status }}</span>

        <button type="button"
                aria-label="Favorit" aria-pressed="{{ !empty($product->is_favorite) ? 'true' : 'false' }}"
                data-favorite-toggle data-url="{{ $favUrl }}"
                class="absolute right-2 top-2 z-10 grid size-8 place-items-center rounded-full bg-neutral-900/55 text-white backdrop-blur-sm transition hover:bg-neutral-900/75 aria-pressed:text-lime-500 aria-pressed:[&>svg]:fill-lime-500 aria-pressed:[&>svg]:stroke-lime-500">
            <svg viewBox="0 0 24 24" class="size-[18px] fill-none stroke-current stroke-2 [stroke-linecap:round] [stroke-linejoin:round]" aria-hidden="true">
                <path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z"/>
            </svg>
        </button>
    </div>

    {{-- Info produk --}}
    <div class="mt-3 flex flex-1 flex-col">
        <h3 class="text-h5 line-clamp-2 text-base leading-snug">
            {{-- stretched link: seluruh kartu bisa diklik, tombol di atas punya z-10 --}}
            <a href="{{ $detailUrl }}" class="after:absolute after:inset-0 after:rounded-xl focus-visible:outline-none focus-visible:after:ring-2 focus-visible:after:ring-neutral-900">
                {{ $product->judul }}
            </a>
        </h3>
        <p class="text-h4 mt-1 font-bold">Rp {{ number_format($product->harga, 0, ',', '.') }}</p>

        <span class="mt-2 self-start rounded-md border border-neutral-200 bg-neutral-100 px-2.5 py-1 text-caption text-neutral-600">
            {{ $product->kondisi }}
        </span>
    </div>
</article>