@props(['images' => []])

@php
    $rawItems = collect($images);

    $slides = $rawItems->map(function ($item, $idx) {
        if (is_string($item)) {
            return [
                'image' => $item,
                'url' => route('products.index'),
                'badge' => 'Promo RePake',
                'title' => 'Promo RePake #' . ($idx + 1),
                'alt' => null,
                'bg' => 'from-neutral-900 via-neutral-800 to-neutral-900',
            ];
        }

        $imageUrl = $item->image_url ?? null;

        if (!$imageUrl) {
            $rawPath = $item->path_url ?? $item->image_path ?? $item->image ?? $item->gambar ?? null;
            if ($rawPath) {
                $imageUrl = (str_starts_with($rawPath, 'http://') || str_starts_with($rawPath, 'https://') || str_starts_with($rawPath, '/'))
                    ? $rawPath
                    : asset(ltrim($rawPath, '/'));
            }
        }

        return [
            'image' => $imageUrl,
            'url' => $item->target_url ?? $item->url ?? $item->link ?? route('products.index'),
            'badge' => $item->badge ?? 'Promo RePake',
            'title' => $item->title ?? $item->judul ?? 'Promo RePake',
            'alt' => $item->alt_text ?? $item->subtitle ?? $item->deskripsi ?? null,
            'bg' => 'from-neutral-900 via-neutral-800 to-neutral-900',
        ];
    })->values();

    if ($slides->isEmpty()) {
        $slides = collect([
            [
                'image' => asset('images/repake-banner-notext.webp'),
                'url' => route('products.index'),
                'badge' => 'Preloved Kampus',
                'title' => 'Temukan Barang Bekas Berkualitas di Sekitarmu',
                'alt' => 'Hemat budget kuliah dengan transaksi COD langsung di area kampus.',
                'bg' => 'from-neutral-900 via-neutral-800 to-lime-950',
            ],
            [
                'image' => asset('images/repake-banner-notext.webp'),
                'url' => route('products.index', ['sort' => 'terdekat']),
                'badge' => 'COD Praktis',
                'title' => 'Cek Barang Terdekat dari Lokasimu',
                'alt' => 'Pilih titik temu favorit seperti Gerbang Kampus atau Galuh Mas.',
                'bg' => 'from-lime-950 via-neutral-900 to-neutral-800',
            ],
            [
                'image' => asset('images/repake-banner-notext.webp'),
                'url' => Route::has('products.create') ? route('products.create') : route('products.index'),
                'badge' => 'Gratis Pasang Iklan',
                'title' => 'Barang Nggak Terpakai? Ubah Jadi Cuan!',
                'alt' => 'Upload foto, atur harga, dan langsung terhubung dengan pembeli.',
                'bg' => 'from-neutral-800 via-neutral-900 to-lime-900',
            ],
        ]);
    }

    // Susun track dengan 2 lapisan intipan di kiri & kanan agar efek mengecil ke samping terlihat penuh
    $count = $slides->count();
    $trackItems = collect();

    if ($count > 1) {
        $prev2 = ($count - 2 + $count) % $count;
        $prev1 = $count - 1;
        $trackItems->push(['slide' => $slides[$prev2], 'index' => $prev2, 'clone' => true]);
        $trackItems->push(['slide' => $slides[$prev1], 'index' => $prev1, 'clone' => true]);
    }

    foreach ($slides as $idx => $s) {
        $trackItems->push(['slide' => $s, 'index' => $idx, 'clone' => false]);
    }

    if ($count > 1) {
        $next1 = 0;
        $next2 = 1 % $count;
        $trackItems->push(['slide' => $slides[$next1], 'index' => $next1, 'clone' => true]);
        $trackItems->push(['slide' => $slides[$next2], 'index' => $next2, 'clone' => true]);
    }
@endphp

<div x-data="{
        active: 0,
        total: {{ $count }},
        offsetBase: {{ $count > 1 ? 2 : 0 }},
        interval: null,
        isDragging: false,
        hasDragged: false,
        startX: 0,
        dragOffset: 0,
        init() {
            this.startAuto();
        },
        startAuto() {
            this.stopAuto();
            if (this.total > 1 && !window.matchMedia?.('(prefers-reduced-motion: reduce)').matches) {
                this.interval = setInterval(() => {
                    if (!document.hidden && !this.isDragging) this.next();
                }, 4500);
            }
        },
        stopAuto() {
            if (this.interval) {
                clearInterval(this.interval);
                this.interval = null;
            }
        },
        next() {
            this.active = (this.active + 1) % this.total;
        },
        prev() {
            this.active = (this.active - 1 + this.total) % this.total;
        },
        goTo(index) {
            this.active = index;
        },
        startDrag(clientX) {
            if (this.total <= 1) return;
            this.isDragging = true;
            this.hasDragged = false;
            this.startX = clientX;
            this.dragOffset = 0;
            this.stopAuto();
        },
        onDrag(clientX) {
            if (!this.isDragging) return;
            const diff = clientX - this.startX;
            if (Math.abs(diff) > 8) this.hasDragged = true;
            this.dragOffset = diff;
        },
        endDrag() {
            if (!this.isDragging) return;
            if (Math.abs(this.dragOffset) > 50) {
                this.dragOffset < 0 ? this.next() : this.prev();
            }
            this.isDragging = false;
            this.dragOffset = 0;
            this.startAuto();
            setTimeout(() => { this.hasDragged = false; }, 50);
        },
        distance(pos) {
            return Math.abs(pos - (this.active + this.offsetBase));
        }
    }" @mouseenter="stopAuto()" @mouseleave="startAuto()" @mousemove.window="onDrag($event.clientX)"
    @mouseup.window="endDrag()"
    class="group/banner relative w-full overflow-hidden select-none [--slide-gap:0.5rem] [--slide-w:84%] sm:[--slide-gap:0.75rem] sm:[--slide-w:440px] md:[--slide-w:520px]">

    {{-- Viewport Carousel (Mendukung Touch Swipe & Mouse Drag di PC) --}}
    <div @mousedown.prevent="startDrag($event.clientX)" @touchstart.passive="startDrag($event.touches[0].clientX)"
        @touchmove.passive="onDrag($event.touches[0].clientX)" @touchend.passive="endDrag()"
        :class="isDragging ? 'cursor-grabbing' : 'cursor-grab'" class="relative w-full overflow-hidden py-2">

        {{-- Track Slide (Rasio 16:9: 520x292px di desktop, mengecil bertahap ke samping) --}}
        <div class="flex transform-gpu items-center"
            :class="isDragging ? 'transition-none' : 'transition-transform duration-500 ease-out'"
            :style="`transform: translateX(calc((100% - var(--slide-w)) / 2 - (${active} + ${offsetBase}) * (var(--slide-w) + var(--slide-gap)) + ${dragOffset}px)); gap: var(--slide-gap);`"
            style="will-change: transform;">

            @foreach ($trackItems as $pos => $item)
                @php
                    $slide = $item['slide'];
                    $idx = $item['index'];
                    $isClone = $item['clone'];
                @endphp

                <a href="{{ $slide['url'] }}" draggable="false" @if ($isClone) aria-hidden="true" tabindex="-1" @endif
                    @click="
                            if (hasDragged) { $event.preventDefault(); return; }
                            if (active !== {{ $idx }}) { $event.preventDefault(); goTo({{ $idx }}); }
                        "
                    class="group relative aspect-video w-[var(--slide-w)] shrink-0 origin-center transform-gpu overflow-hidden rounded-2xl transition-[transform,opacity,filter,box-shadow] duration-500 ease-out"
                    :class="distance({{ $pos }}) === 0
                            ? 'z-20 scale-100 opacity-100 ring-1 ring-black/10 shadow-md hover:shadow-xl'
                            : (distance({{ $pos }}) === 1
                                ? 'z-10 scale-90 opacity-65 brightness-95 hover:opacity-85 cursor-pointer'
                                : 'z-0 scale-80 opacity-35 brightness-90 hover:opacity-60 cursor-pointer')">

                    @if ($slide['image'])
                        <img src="{{ $slide['image'] }}" alt="{{ $isClone ? '' : ($slide['alt'] ?? $slide['title']) }}"
                            draggable="false"
                            class="pointer-events-none size-full object-cover object-center transition-transform duration-500 ease-out group-hover:scale-105">

                        @if (str_contains($slide['image'], 'notext'))
                            <div
                                class="absolute inset-0 flex flex-col justify-center bg-linear-to-r from-black/85 via-black/60 to-transparent px-5 text-white sm:px-8">
                                <span
                                    class="mb-2 inline-block w-fit rounded-full bg-lime-400 px-2.5 py-0.5 text-[10px] font-extrabold uppercase tracking-wider text-neutral-900">
                                    {{ $slide['badge'] }}
                                </span>
                                <h3 class="line-clamp-2 max-w-sm text-sm font-extrabold leading-snug sm:text-lg md:text-xl">
                                    {{ $slide['title'] }}
                                </h3>
                                @if (!empty($slide['alt']))
                                    <p class="mt-1.5 line-clamp-2 max-w-sm text-xs text-neutral-200">
                                        {{ $slide['alt'] }}
                                    </p>
                                @endif
                            </div>
                        @endif
                    @else
                        <div
                            class="relative flex size-full flex-col justify-center bg-linear-to-r {{ $slide['bg'] }} px-5 text-white sm:px-8">
                            <span
                                class="mb-2 inline-block w-fit rounded-full bg-lime-400 px-2.5 py-0.5 text-[10px] font-extrabold uppercase tracking-wider text-neutral-900">
                                {{ $slide['badge'] }}
                            </span>
                            <h3 class="line-clamp-2 max-w-sm text-sm font-extrabold leading-snug sm:text-lg md:text-xl">
                                {{ $slide['title'] }}
                            </h3>
                            @if (!empty($slide['alt']))
                                <p class="mt-1.5 line-clamp-2 max-w-sm text-xs text-neutral-300">
                                    {{ $slide['alt'] }}
                                </p>
                            @endif
                        </div>
                    @endif
                </a>
            @endforeach
        </div>

        {{-- Tombol Panah Minimalis di Tepi Luar (Tidak Menutupi Banner Utama, Muncul Saat Hover di Desktop) --}}
        @if ($count > 1)
            <button type="button" @click.stop="prev()" aria-label="Banner Sebelumnya"
                class="absolute top-1/2 left-4 z-30 hidden size-10 -translate-y-1/2 items-center justify-center rounded-full border border-neutral-200/80 bg-white/90 text-neutral-900 opacity-0 shadow-md backdrop-blur-xs transition-all duration-200 hover:scale-105 hover:bg-lime-400 focus-visible:opacity-100 group-hover/banner:opacity-100 active:scale-95 md:flex cursor-pointer">
                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" />
                </svg>
            </button>

            <button type="button" @click.stop="next()" aria-label="Banner Selanjutnya"
                class="absolute top-1/2 right-4 z-30 hidden size-10 -translate-y-1/2 items-center justify-center rounded-full border border-neutral-200/80 bg-white/90 text-neutral-900 opacity-0 shadow-md backdrop-blur-xs transition-all duration-200 hover:scale-105 hover:bg-lime-400 focus-visible:opacity-100 group-hover/banner:opacity-100 active:scale-95 md:flex cursor-pointer">
                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                </svg>
            </button>
        @endif
    </div>

    {{-- Page Control / Indicator Dots --}}
    @if ($count > 1)
        <div class="mt-2 flex items-center justify-center gap-1.5">
            @foreach ($slides as $index => $slide)
                <button type="button" @click="goTo({{ $index }})" aria-label="Slide {{ $index + 1 }}"
                    class="h-1.5 rounded-full transition-all duration-300 cursor-pointer focus:outline-none"
                    :class="active === {{ $index }} ? 'w-6 bg-lime-500' : 'w-1.5 bg-neutral-300 hover:bg-neutral-400'">
                </button>
            @endforeach
        </div>
    @endif
</div>