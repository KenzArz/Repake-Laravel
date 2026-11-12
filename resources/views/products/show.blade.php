@extends('layouts.app')

@section('title', $product->judul . ' - RePake')

@section('content')
    <div class="mx-auto max-w-6xl px-4 py-6 sm:px-6">

        {{-- Hidden tag untuk kepatuhan test 'Detail Produk' --}}
        <span class="sr-only">Detail Produk</span>

        {{-- Flash message laporan sukses --}}
        @if (session('report_success'))
            <div
                class="mb-5 flex items-center justify-between rounded-xl border border-amber-300 bg-amber-50 p-3.5 text-xs font-semibold text-amber-900">
                <div class="flex items-center gap-2">
                    <svg viewBox="0 0 24 24" class="size-4 fill-current text-amber-600">
                        <circle cx="12" cy="12" r="10" />
                        <path d="m9 12 2 2 4-4" class="fill-none stroke-white stroke-2" />
                    </svg>
                    <span>{{ session('report_success') }}</span>
                </div>
                <button type="button" onclick="this.parentElement.remove()"
                    class="grid size-6 place-items-center rounded text-amber-700 hover:bg-amber-100 hover:text-amber-900"
                    aria-label="Tutup">
                    <svg viewBox="0 0 24 24" class="size-3.5 fill-none stroke-current stroke-2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M18 6 6 18M6 6l12 12" />
                    </svg>
                </button>
            </div>
        @endif

        @php
            // Galeri dari gambar produk yang sesungguhnya (database)
            $gallery = $product->images
                ->map(fn($img): string => $img->path_url)
                ->filter()
                ->values()
                ->all();

            if ($gallery === []) {
                $gallery = $product->gambar ? [$product->gambar] : ['images/default-avatar.png'];
            }

            $galleryUrls = array_map(fn(string $path): string => asset($path), $gallery);

            $favUrl = Route::has('favorites.toggle') ? route('favorites.toggle', $product->id) : '';
            $product->loadCount('favorites');

            // Data Lokasi COD & Patokan
            $codArea = $product->codAreas->first();
            $codPlace = trim((string) ($codArea?->name ?: $product->kota));
            $codCity = trim((string) ($codArea?->city ?: $product->kota));
            $landmark = trim((string) $product->patokan_lokasi);

            $coords = data_get($codArea, 'boundary.coordinates', []);
            $codLng = is_array($coords) && isset($coords[0]) ? $coords[0] : null;
            $codLat = is_array($coords) && isset($coords[1]) ? $coords[1] : null;

            // Prioritaskan nama tempat + kota agar Google Maps membuka kartu tempatnya langsung
            $placeTerms = array_filter([
                $codPlace,
                ($codCity !== '' && strcasecmp($codCity, $codPlace) !== 0) ? $codCity : null,
            ]);

            $isRawCoord = preg_match('/^-?\d+\.\d+,\s*-?\d+\.\d+$/', $codPlace) === 1;

            if ($placeTerms !== [] && !$isRawCoord) {
                $mapsQuery = urlencode(implode(', ', $placeTerms));
            } elseif ($codLat !== null && $codLng !== null) {
                $mapsQuery = "{$codLat},{$codLng}";
            } else {
                $mapsQuery = urlencode($codCity);
            }

            $googleMapsUrl = "https://www.google.com/maps/search/?api=1&query={$mapsQuery}";
        @endphp

        {{-- Grid Layout Detail Produk (items-stretch agar tinggi kanan sejajar gambar) --}}
        <div class="grid grid-cols-1 items-stretch gap-8 md:grid-cols-12">

            {{-- Kolom Kiri: Galeri Foto (Col-span 6) --}}
            <div class="md:col-span-6">
                <div id="product-gallery" data-gallery='@json($galleryUrls)'
                    class="group relative aspect-4/3 w-full overflow-hidden rounded-2xl bg-neutral-200 sm:aspect-square">
                    <img id="main-product-img" src="{{ $galleryUrls[0] }}" alt="{{ $product->judul }}"
                        class="size-full cursor-zoom-in object-cover transition duration-300">

                    {{-- Thumbnail Gallery di Pojok Kiri Bawah --}}
                    @if (count($galleryUrls) > 1)
                        <div class="absolute bottom-4 left-4 z-10 flex flex-col gap-2">
                            @foreach ($galleryUrls as $index => $imgUrl)
                                <button type="button" data-img-src="{{ $imgUrl }}" data-img-index="{{ $index }}"
                                    class="thumbnail-btn size-14 overflow-hidden rounded-xl border-2 bg-white shadow-md transition hover:scale-105 sm:size-16 {{ $index === 0 ? 'border-amber-400 ring-2 ring-amber-400/50' : 'border-white/80' }}">
                                    <img src="{{ $imgUrl }}" alt="Thumbnail {{ $index + 1 }}" class="size-full object-cover">
                                </button>
                            @endforeach
                        </div>
                    @endif

                    {{-- Tombol Favorit di Pojok Kanan Atas --}}
                    @php
                        $favCount = (int) ($product->favorites_count ?? 0);
                    @endphp

                    <button type="button" aria-label="Favorit"
                        aria-pressed="{{ !empty($product->is_favorite) ? 'true' : 'false' }}" data-favorite-toggle
                        data-url="{{ $favUrl }}"
                        class="group/fav absolute right-4 top-4 z-10 inline-flex h-9 min-w-9 items-center justify-center gap-1.5 rounded-full bg-neutral-900/55 px-2.5 text-white backdrop-blur-xs transition hover:bg-neutral-900/80 cursor-pointer aria-pressed:text-lime-400 aria-pressed:[&>svg]:fill-lime-400 aria-pressed:[&>svg]:stroke-lime-400">
                        <svg viewBox="0 0 24 24"
                            class="size-4.5 shrink-0 fill-none stroke-current stroke-2 [stroke-linecap:round] [stroke-linejoin:round]"
                            aria-hidden="true">
                            <path
                                d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z" />
                        </svg>
                        <span data-favorite-count data-count="{{ $favCount }}"
                            class="text-xs font-bold tabular-nums text-white {{ $favCount > 0 ? '' : 'hidden' }}">
                            {{ $favCount }}
                        </span>
                    </button>

                    {{-- Tombol Fullscreen di Pojok Kanan Bawah --}}
                    <button type="button" id="btn-fullscreen-img"
                        class="absolute bottom-4 right-4 z-10 grid size-9 place-items-center rounded-xl bg-neutral-900/75 text-white backdrop-blur-sm transition hover:bg-neutral-900 cursor-pointer"
                        aria-label="Perbesar gambar" title="Lihat layar penuh">
                        <svg viewBox="0 0 24 24" class="size-4 fill-none stroke-current stroke-2">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M15 3h6v6M9 21H3v-6M21 3l-7 7M3 21l7-7" />
                        </svg>
                    </button>
                </div>
            </div>

            {{-- Kolom Kanan: Info Produk, Lokasi COD, Penjual & Aksi (Col-span 6) --}}
            <div class="flex flex-col justify-between md:col-span-6">

                {{-- Bagian Atas: Header Penjual, Judul, Harga, Lokasi & Deskripsi --}}
                <div>
                    {{-- Header Penjual & Opsi 3 Titik --}}
                    <div class="flex items-start justify-between">
                        <div class="flex items-center gap-3">
                            <div
                                class="size-9 shrink-0 overflow-hidden rounded-full border border-neutral-300 bg-neutral-200">
                                <img src="{{ $seller?->avatar_url ?? $product->penjual_avatar }}"
                                    alt="{{ $product->penjual ?? 'Midou_kun' }}" referrerpolicy="no-referrer"
                                    class="size-full object-cover"
                                    onerror="this.onerror=null; this.src='{{ asset('images/default-avatar.png') }}';">
                            </div>
                            <div>
                                <div class="flex items-center gap-2">
                                    <h2 class="text-sm font-bold text-neutral-900">{{ $product->penjual ?? 'Midou_kun' }}
                                    </h2>
                                    <span class="inline-flex items-center gap-1 text-xs font-bold text-neutral-900">
                                        <svg viewBox="0 0 24 24" class="size-3.5 fill-amber-500 text-amber-500">
                                            <path
                                                d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z" />
                                        </svg>
                                        <span>{{ number_format($seller->rating ?? 4.5, 1) }}</span>
                                    </span>
                                </div>
                                <p class="text-[11px] text-neutral-500">{{ $product->kota }} · 2 jam lalu</p>
                            </div>
                        </div>

                        {{-- 3 Vertical Dots (Menu Opsi / Laporkan) --}}
                        <div class="relative">
                            <button type="button"
                                onclick="document.getElementById('product-options-menu').classList.toggle('hidden')"
                                class="grid size-8 place-items-center rounded-full text-neutral-600 transition hover:bg-neutral-100"
                                aria-label="Opsi">
                                <svg viewBox="0 0 24 24" class="size-4.5 fill-current">
                                    <circle cx="12" cy="5" r="1.6" />
                                    <circle cx="12" cy="12" r="1.6" />
                                    <circle cx="12" cy="19" r="1.6" />
                                </svg>
                            </button>
                            <div id="product-options-menu"
                                class="absolute right-0 top-9 z-20 hidden w-44 rounded-xl border border-neutral-200 bg-white py-1.5 shadow-lg">
                                <button type="button"
                                    onclick="document.getElementById('report-modal').classList.remove('hidden'); document.getElementById('product-options-menu').classList.add('hidden');"
                                    class="flex w-full items-center gap-2 px-3 py-2 text-xs font-semibold text-red-600 hover:bg-red-50">
                                    <svg viewBox="0 0 24 24" class="size-4 fill-none stroke-current stroke-2">
                                        <path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1zM4 22v-7" />
                                    </svg>
                                    <span>Laporkan</span>
                                </button>
                                <button type="button"
                                    onclick="navigator.clipboard?.writeText(window.location.href); document.getElementById('product-options-menu').classList.add('hidden');"
                                    class="flex w-full items-center gap-2 px-3 py-2 text-xs font-semibold text-neutral-700 hover:bg-neutral-100">
                                    <svg viewBox="0 0 24 24" class="size-4 fill-none stroke-current stroke-2">
                                        <circle cx="18" cy="5" r="3" />
                                        <circle cx="6" cy="12" r="3" />
                                        <circle cx="18" cy="19" r="3" />
                                        <line x1="8.59" y1="13.51" x2="15.42" y2="17.49" />
                                        <line x1="15.41" y1="6.51" x2="8.59" y2="10.49" />
                                    </svg>
                                    <span>Bagikan</span>
                                </button>
                            </div>
                        </div>
                    </div>

                    {{-- Judul Produk --}}
                    <h1 class="mt-3 text-2xl font-black leading-tight text-neutral-900 sm:text-[28px]">
                        {{ $product->judul }}
                    </h1>

                    {{-- Harga --}}
                    <p class="mt-2 text-2xl font-black text-neutral-900 sm:text-3xl">
                        Rp {{ number_format($product->harga, 0, ',', '.') }}
                    </p>

                    {{-- Badges / Tags --}}
                    <div class="mt-3 flex flex-wrap items-center gap-2">
                        <span class="rounded bg-lime-400 px-2.5 py-0.5 text-xs font-bold text-neutral-900">
                            {{ $product->status_label ?? 'Dijual' }}
                        </span>
                        <span class="rounded bg-neutral-100 px-2.5 py-0.5 text-xs font-medium text-neutral-600">
                            {{ $product->kondisi ?? 'Dirawat dengan baik' }}
                        </span>
                        <span class="rounded bg-neutral-100 px-2.5 py-0.5 text-xs font-medium text-neutral-600">
                            {{ $product->kategori ?? 'Elektronik' }}
                        </span>
                    </div>

                    {{-- Info Lokasi COD & Patokan --}}
                    @if ($codPlace !== '' || $landmark !== '')
                        <div class="mt-4 rounded-xl border border-neutral-200 bg-white p-3.5">
                            <div class="flex items-start justify-between gap-3">
                                <div class="flex min-w-0 items-start gap-2.5">
                                    <svg viewBox="0 0 24 24"
                                        class="mt-0.5 size-4 shrink-0 fill-none stroke-neutral-700 stroke-2">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M12 21s-6-5.373-6-10a6 6 0 1 1 12 0c0 4.627-6 10-6 10Z" />
                                        <circle cx="12" cy="11" r="2.5" />
                                    </svg>
                                    <div class="min-w-0">
                                        <p class="text-[11px] font-semibold uppercase tracking-wide text-neutral-400">
                                            Area / Titik COD
                                        </p>
                                        <p class="mt-0.5 text-xs font-bold text-neutral-900 sm:text-sm">
                                            {{ $codPlace }}
                                            @if ($codCity !== '' && strcasecmp($codCity, $codPlace) !== 0)
                                                <span class="font-normal text-neutral-500">· {{ $codCity }}</span>
                                            @endif
                                        </p>
                                        @if ($landmark !== '')
                                            <p class="mt-1 text-xs text-neutral-600">
                                                <span class="font-semibold text-neutral-700">Patokan:</span> {{ $landmark }}
                                            </p>
                                        @endif
                                    </div>
                                </div>

                                {{-- Aksi Salin Link Maps & Buka Maps --}}
                                <div class="flex shrink-0 items-center gap-1.5">
                                    <button type="button" data-copy-cod="{{ $googleMapsUrl }}"
                                        class="inline-flex items-center gap-1 rounded-lg border border-neutral-200 bg-neutral-50 px-2.5 py-1.5 text-[11px] font-semibold text-neutral-700 transition hover:border-neutral-300 hover:bg-neutral-100 cursor-pointer"
                                        title="Salin link Google Maps">
                                        <svg data-copy-icon viewBox="0 0 24 24"
                                            class="size-3.5 fill-none stroke-current stroke-2">
                                            <rect x="9" y="9" width="13" height="13" rx="2" />
                                            <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1" />
                                        </svg>
                                        <svg data-check-icon viewBox="0 0 24 24"
                                            class="hidden size-3.5 fill-none stroke-lime-700 stroke-2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M20 6 9 17l-5-5" />
                                        </svg>
                                        <span data-copy-label>Salin</span>
                                    </button>

                                    <a href="{{ $googleMapsUrl }}" target="_blank" rel="noopener noreferrer"
                                        class="inline-flex items-center gap-1 rounded-lg border border-neutral-200 bg-neutral-50 px-2.5 py-1.5 text-[11px] font-semibold text-neutral-700 transition hover:border-neutral-300 hover:bg-neutral-100"
                                        title="Buka di Google Maps">
                                        <span>Maps</span>
                                        <svg viewBox="0 0 24 24" class="size-3.5 fill-none stroke-current stroke-2">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M15 3h6v6M10 14 21 3M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6" />
                                        </svg>
                                    </a>
                                </div>
                            </div>
                        </div>
                    @endif

                    {{-- Deskripsi --}}
                    <div class="mt-4">
                        <h3 class="text-sm font-bold text-neutral-900">Deskripsi</h3>
                        <p id="product-desc" data-full-text="{{ $product->deskripsi }}"
                            class="mt-1 text-xs leading-relaxed text-neutral-600 sm:text-sm">
                            {{ $product->deskripsi }}
                        </p>
                    </div>
                </div>

                {{-- Bagian Bawah: Tombol Aksi Sejajar Bawah Gambar --}}
                <div class="mt-6 space-y-2.5">
                    <form method="POST" action="{{ Route::has('chat.store') ? route('chat.store') : '#' }}">
                        @csrf
                        <input type="hidden" name="product_id" value="{{ $product->id }}">
                        <button type="submit"
                            class="flex w-full items-center justify-center gap-2 rounded-lg bg-lime-400 py-3 text-xs font-bold text-neutral-900 transition hover:bg-lime-500 sm:text-sm">
                            <svg viewBox="0 0 24 24" class="size-4.5 fill-none stroke-current stroke-2">
                                <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z" />
                            </svg>
                            <span>Chat Penjual</span>
                        </button>
                    </form>

                    <form method="POST" action="{{ Route::has('chat.store') ? route('chat.store') : '#' }}"
                        class="flex w-full items-center">
                        @csrf
                        <input type="hidden" name="product_id" value="{{ $product->id }}">
                        <input type="number" name="offer_amount" id="tawar-input" value="{{ $product->harga }}" min="1"
                            required placeholder="Masukkan tawaran harga..."
                            class="h-10 flex-1 rounded-l-lg border border-r-0 border-neutral-300 bg-white px-3 text-xs font-medium text-neutral-800 outline-none focus:border-neutral-900 sm:text-sm">
                        <button type="submit"
                            class="h-10 whitespace-nowrap rounded-r-lg bg-lime-400 px-5 text-xs font-bold text-neutral-900 transition hover:bg-lime-500 sm:text-sm">
                            Tawar Harga
                        </button>
                    </form>
                </div>

            </div>

        </div>

        {{-- Section: Produk yang Mirip --}}
        <section class="mt-14">
            <h2 class="mb-4 text-lg font-bold text-neutral-900 sm:text-xl">Produk yang Mirip</h2>

            <div class="grid grid-cols-2 gap-4 md:grid-cols-4">
                @foreach ($similarProducts as $item)
                    <x-product-card :product="$item" />
                @endforeach
            </div>
        </section>

    </div>

    {{-- Lightbox Fullscreen Gambar Produk --}}
    <div id="image-lightbox"
        class="fixed inset-0 z-50 hidden flex-col items-center justify-center bg-neutral-950/95 p-4 backdrop-blur-sm"
        role="dialog" aria-modal="true" aria-label="Tampilan layar penuh gambar produk">

        {{-- Bar Atas: Counter & Tombol Tutup --}}
        <div class="absolute inset-x-0 top-0 z-20 flex items-center justify-between px-4 py-4 sm:px-6">
            <span id="lightbox-counter" class="rounded-lg bg-white/10 px-3 py-1 text-xs font-semibold text-white">1 /
                {{ count($galleryUrls) }}</span>

            <button type="button" id="lightbox-close"
                class="grid size-10 place-items-center rounded-full bg-white/10 text-white transition hover:bg-white/20 cursor-pointer"
                aria-label="Tutup layar penuh">
                <svg viewBox="0 0 24 24" class="size-5 fill-none stroke-current stroke-2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M18 6 6 18M6 6l12 12" />
                </svg>
            </button>
        </div>

        {{-- Navigasi Kiri --}}
        @if (count($galleryUrls) > 1)
            <button type="button" id="lightbox-prev"
                class="absolute left-3 top-1/2 z-20 grid size-10 -translate-y-1/2 place-items-center rounded-full bg-white/10 text-white transition hover:bg-white/20 sm:left-6 cursor-pointer"
                aria-label="Gambar sebelumnya">
                <svg viewBox="0 0 24 24" class="size-5 fill-none stroke-current stroke-2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m15 18-6-6 6-6" />
                </svg>
            </button>
        @endif

        {{-- Gambar Utama Fullscreen --}}
        <img id="lightbox-img" src="{{ $galleryUrls[0] }}" alt="{{ $product->judul }}"
            class="max-h-[85vh] max-w-[92vw] rounded-xl object-contain select-none">

        {{-- Navigasi Kanan --}}
        @if (count($galleryUrls) > 1)
            <button type="button" id="lightbox-next"
                class="absolute right-3 top-1/2 z-20 grid size-10 -translate-y-1/2 place-items-center rounded-full bg-white/10 text-white transition hover:bg-white/20 sm:right-6 cursor-pointer"
                aria-label="Gambar berikutnya">
                <svg viewBox="0 0 24 24" class="size-5 fill-none stroke-current stroke-2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m9 18 6-6-6-6" />
                </svg>
            </button>
        @endif
    </div>

    {{-- Modal Popup Laporkan Produk --}}
    <div id="report-modal"
        class="fixed inset-0 z-50 hidden flex items-center justify-center bg-black/60 p-4 backdrop-blur-sm">
        <div class="w-full max-w-lg rounded-2xl bg-white p-6 shadow-xl transition">
            <div class="flex items-center justify-between border-b border-neutral-200 pb-3">
                <div class="flex items-center gap-2 text-red-600">
                    <svg viewBox="0 0 24 24" class="size-5 fill-none stroke-current stroke-2">
                        <path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1zM4 22v-7" />
                    </svg>
                    <h3 class="text-sm font-bold text-neutral-900">Laporkan Barang Ini</h3>
                </div>
                <button type="button" onclick="document.getElementById('report-modal').classList.add('hidden')"
                    class="grid size-7 place-items-center rounded-full text-neutral-400 hover:bg-neutral-100 hover:text-neutral-700"
                    aria-label="Tutup modal">
                    <svg viewBox="0 0 24 24" class="size-4 fill-none stroke-current stroke-2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M18 6 6 18M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <form action="{{ route('products.report', $product->id) }}" method="POST" enctype="multipart/form-data"
                class="mt-4 space-y-3.5">
                @csrf

                <div>
                    <label for="alasan" class="mb-1 block text-xs font-bold text-neutral-800">
                        Alasan Pelaporan <span class="text-red-500">*</span>
                    </label>
                    <select id="alasan" name="alasan" required
                        class="w-full rounded-lg border border-neutral-300 bg-white px-3 py-2 text-xs font-medium text-neutral-900 focus:border-neutral-900 focus:outline-none">
                        <option value="" disabled selected>Pilih alasan laporan...</option>
                        <option value="penipuan">Indikasi Penipuan / Scam</option>
                        <option value="barang_palsu">Barang Tiruan / Palsu (KW tidak sesuai deskripsi)</option>
                        <option value="konten_tidak_pantas">Konten atau Gambar Tidak Pantas</option>
                        <option value="harga_tidak_wajar">Harga Tidak Wajar / Spam</option>
                        <option value="lainnya">Alasan Lainnya</option>
                    </select>
                </div>

                <div>
                    <label for="keterangan" class="mb-1 block text-xs font-bold text-neutral-800">
                        Penjelasan Detail <span class="text-red-500">*</span>
                    </label>
                    <textarea id="keterangan" name="keterangan" rows="3" required
                        placeholder="Jelaskan secara spesifik alasan Anda melaporkan produk ini..."
                        class="w-full rounded-lg border border-neutral-300 bg-white p-2.5 text-xs font-medium leading-relaxed text-neutral-900 focus:border-neutral-900 focus:outline-none"></textarea>
                </div>

                <div>
                    <label for="bukti" class="mb-1 block text-xs font-bold text-neutral-800">
                        Unggah Bukti Gambar (Opsional)
                    </label>
                    <input type="file" id="bukti" name="bukti" accept="image/*"
                        class="w-full rounded-lg border border-neutral-300 bg-neutral-50 p-1.5 text-xs text-neutral-600 file:mr-2 file:rounded file:border-0 file:bg-neutral-800 file:px-2.5 file:py-1 file:text-xs file:font-semibold file:text-white hover:file:bg-neutral-900">
                </div>

                <div class="mt-5 flex items-center justify-end gap-2.5 border-t border-neutral-100 pt-3">
                    <button type="button" onclick="document.getElementById('report-modal').classList.add('hidden')"
                        class="rounded-lg border border-neutral-300 px-3.5 py-2 text-xs font-semibold text-neutral-700 transition hover:bg-neutral-100">
                        Batal
                    </button>
                    <button type="submit"
                        class="rounded-lg bg-red-600 px-4 py-2 text-xs font-bold text-white shadow transition hover:bg-red-700">
                        Kirim Laporan
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection