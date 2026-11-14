@props(['product'])

@php
    $detailUrl = Route::has('products.show') ? $product->detailUrl() : '#';
    $favUrl = Route::has('favorites.toggle') ? route('favorites.toggle', $product->id) : '';
    $isDijual = strcasecmp(trim((string) $product->status_label), 'Dijual') === 0;
    $badge = $product->status_label === 'Dijual'
        ? 'bg-lime-500/90 text-neutral-900'
        : 'bg-neutral-200/90 text-neutral-700';
    $ago = is_string($product->created_at)
        ? $product->created_at
        : ($product->created_at?->locale('id')->diffForHumans(['syntax' => \Carbon\CarbonInterface::DIFF_ABSOLUTE]) . ' lalu' ?? 'Baru saja');

    $sellerName = $product->user?->name ?? $product->penjual ?? 'Penjual';
    $sellerUrl = $product->user?->profileUrl();
    $sellerAvatar = $product->user?->avatar ?? $product->avatar ?? null;
    $defaultAvatarUrl = asset('images/default-avatar.png');
    $fallbackUiAvatar = 'https://ui-avatars.com/api/?name=' . urlencode($sellerName) . '&background=B8E639&color=1c1917';
    $avatarSrc = !empty($sellerAvatar)
        ? (str_starts_with($sellerAvatar, 'http') ? $sellerAvatar : asset($sellerAvatar))
        : $defaultAvatarUrl;
@endphp

<article
    class="group relative flex h-full flex-col rounded-xl border border-neutral-200/80 bg-white p-2.5 shadow-2xs transition-all duration-300 ease-out hover:-translate-y-1 hover:border-lime-400/90 hover:shadow-lg hover:shadow-neutral-900/8">
    {{-- Header penjual: avatar & nama menuju profil publik penjual (z-10 agar di atas stretched link) --}}
    <div class="flex items-center gap-2 pb-2">
        @if ($sellerUrl)
            <a href="{{ $sellerUrl }}" title="Lihat profil {{ $sellerName }}"
                class="relative z-10 block size-7 shrink-0 overflow-hidden rounded-full border border-neutral-300 bg-neutral-200 transition-transform duration-200 hover:scale-105 hover:ring-2 hover:ring-lime-400">
                <img src="{{ $product->penjual_avatar }}" alt="{{ $product->penjual }}" referrerpolicy="no-referrer"
                    class="size-full object-cover"
                    onerror="this.onerror=null; this.src='{{ asset('images/default-avatar.png') }}';">
            </a>
        @else
            <div class="size-7 shrink-0 overflow-hidden rounded-full border border-neutral-300 bg-neutral-200">
                <img src="{{ $product->penjual_avatar }}" alt="{{ $product->penjual }}" referrerpolicy="no-referrer"
                    class="size-full object-cover"
                    onerror="this.onerror=null; this.src='{{ asset('images/default-avatar.png') }}';">
            </div>
        @endif
        <div class="min-w-0 flex-1 leading-tight">
            <p class="truncate text-xs font-bold text-neutral-900">
                @if ($sellerUrl)
                    <a href="{{ $sellerUrl }}"
                        class="relative z-10 transition-colors hover:text-lime-700 hover:underline ">{{ $product->penjual }}</a>
                @else
                    {{ $product->penjual }}
                @endif
            </p>
            <p class="truncate text-[11px] text-neutral-500">{{ $product->kota }} · {{ $ago }}</p>
        </div>
        <x-product-options-menu :product="$product" />
    </div>

    {{-- Gambar + badge + favorit + quick preview overlay --}}
    <div class="relative aspect-square overflow-hidden rounded-lg bg-neutral-200">
        @if ($product->gambar)
            <img src="{{ asset($product->gambar) }}" alt="{{ $product->judul }}" loading="lazy"
                class="size-full object-cover transition-transform duration-500 ease-out group-hover:scale-105"
                onerror="this.remove()">
        @endif

        {{-- Gradient halus & label "Lihat Detail" saat hover di desktop --}}
        <div
            class="pointer-events-none absolute inset-x-0 bottom-0 flex items-end justify-between bg-linear-to-t from-black/65 via-black/20 to-transparent p-2.5 opacity-0 transition-opacity duration-300 group-hover:opacity-100">
            <span
                class="inline-flex translate-y-1.5 items-center gap-1 rounded-full bg-white/95 px-2.5 py-1 text-[10px] font-bold text-neutral-900 shadow-xs transition-transform duration-300 ease-out group-hover:translate-y-0">
                Lihat Detail
                <svg viewBox="0 0 24 24" class="size-3 fill-none stroke-current stroke-[2.5]" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m9 18 6-6-6-6" />
                </svg>
            </span>
        </div>

        <span
            class="absolute left-2 top-2 rounded bg-lime-400 px-2 py-0.5 text-[10px] font-bold text-neutral-900 shadow-2xs {{ $badge }}">{{ $product->status_label }}</span>

        <button type="button" aria-label="Favorit" aria-pressed="{{ !empty($product->is_favorite) ? 'true' : 'false' }}"
            @guest data-open-login @endguest data-favorite-toggle data-url="{{ $favUrl }}"
            class="absolute right-2 top-2 z-10 grid size-7.5 place-items-center rounded-full bg-neutral-900/50 text-white backdrop-blur-xs transition-all duration-200 hover:scale-110 hover:bg-neutral-900/80 active:scale-95 aria-pressed:text-lime-400 aria-pressed:[&>svg]:fill-lime-400 aria-pressed:[&>svg]:stroke-lime-400 cursor-pointer">
            <svg viewBox="0 0 24 24"
                class="size-4 fill-none stroke-current stroke-2 [stroke-linecap:round] [stroke-linejoin:round]"
                aria-hidden="true">
                <path
                    d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z" />
            </svg>
        </button>
    </div>

    {{-- Info produk --}}
    <div class="mt-2.5 flex flex-1 flex-col">
        <h3
            class="line-clamp-2 text-xs font-bold leading-snug text-neutral-900 transition-colors duration-200 group-hover:text-lime-700">
            {{-- stretched link: seluruh kartu bisa diklik, tombol di atas punya z-10 --}}
            <a href="{{ $detailUrl }}"
                class="after:absolute after:inset-0 after:rounded-xl focus-visible:outline-none focus-visible:after:ring-2 focus-visible:after:ring-neutral-900">
                {{ $product->judul }}
            </a>
        </h3>
        <p class="mt-1 text-sm font-extrabold text-neutral-900">Rp {{ number_format($product->harga, 0, ',', '.') }}</p>

        <span
            class="mt-2 self-start rounded bg-neutral-100 px-2 py-0.5 text-[10px] font-medium text-neutral-600 transition-colors duration-200 group-hover:bg-lime-100 group-hover:text-neutral-900">
            {{ $product->kondisi }}
        </span>
    </div>
</article>