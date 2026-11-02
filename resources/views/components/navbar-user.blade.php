@php
    // Pakai route kalau sudah ada, kalau belum fallback ke '#'
    $r = fn (string $name, $params = []) => Route::has($name) ? route($name, $params) : '#';
    $isGuest = auth()->guest();
    $needLogin = $isGuest ? 'data-open-login' : '';
    $iconBtn = 'grid size-10 place-items-center rounded-full text-neutral-900 transition hover:bg-neutral-200';
    $svg = 'size-[22px] fill-none stroke-current stroke-[1.75] [stroke-linecap:round] [stroke-linejoin:round]';
@endphp

<header class="sticky top-0 z-40 border-b border-neutral-200 bg-neutral-50">
    <div class="mx-auto flex max-w-7xl flex-wrap items-center gap-x-3 gap-y-2 px-4 py-3 md:h-[66px] md:flex-nowrap md:px-8 md:py-0 lg:px-12">

        {{-- Logo --}}
        <a href="{{ $r('home') }}" class="text-h3 mr-2 shrink-0 font-extrabold tracking-tight">RePake</a>

        {{-- Search + lokasi (mobile: pindah ke baris kedua) --}}
        <div class="order-last flex w-full items-center gap-3 md:order-none md:w-auto md:max-w-[28rem] md:flex-1">
            <x-search-bar />

            {{-- Hook untuk location-detect.js (ALDI) --}}
            <button type="button" data-location-picker
                class="flex h-10 shrink-0 items-center gap-2 rounded-lg border border-neutral-300 px-3 text-label whitespace-nowrap transition hover:border-neutral-900">
                <svg viewBox="0 0 24 24" class="size-[18px] fill-none stroke-current stroke-[1.75] [stroke-linecap:round] [stroke-linejoin:round]" aria-hidden="true">
                    <path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/>
                </svg>
                <span>{{ auth()->user()?->kota ?? 'Pilih Lokasimu' }}</span>
            </button>
        </div>

        {{-- Menu kanan --}}
        <nav class="ml-auto flex items-center gap-1 md:gap-2" aria-label="Menu pengguna">
            {{-- Favorit (desktop saja; di mobile ada di bottom nav) --}}
            <a href="{{ route('favorites.index') }}"
               class="{{ $iconBtn }} {{ request()->routeIs('favorites.*') ? 'bg-neutral-200 text-lime-700' : '' }} hidden md:grid" aria-label="Favorit">
                <svg viewBox="0 0 24 24" class="{{ $svg }} {{ request()->routeIs('favorites.*') ? 'fill-current' : '' }}" aria-hidden="true">
                    <path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z"/>
                </svg>
            </a>

            {{-- Inbox --}}
            <a href="{{ $r('chat.index') }}" {{ $needLogin }} class="{{ $iconBtn }}" aria-label="Inbox">
                <svg viewBox="0 0 24 24" class="{{ $svg }}" aria-hidden="true">
                    <rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/>
                </svg>
            </a>

            {{-- Profil --}}
            <a href="{{ route('profile.catalog') }}"
                class="grid size-9 shrink-0 place-items-center rounded-full bg-neutral-200 text-label font-semibold text-neutral-800 transition hover:bg-neutral-300"
                aria-label="Profil">
                @if (auth()->check())
                    {{ strtoupper(mb_substr(auth()->user()->name, 0, 1)) }}
                @else
                    <svg viewBox="0 0 24 24" class="size-5 fill-neutral-700">
                        <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
                    </svg>
                @endif
            </a>

            {{-- +Jual --}}
            <a href="{{ $r('products.create') }}" {{ $needLogin }}
               class="ml-1 rounded-md bg-lime-500 px-4 py-2 text-label font-semibold text-neutral-900 transition hover:bg-lime-600">
                +Jual
            </a>
        </nav>
    </div>
</header>

{{-- Bottom nav (mobile saja) - Floating Curved Pill Bar --}}
<div class="fixed inset-x-0 bottom-3 z-40 px-4 md:hidden">
    <nav class="mx-auto flex max-w-sm items-center justify-around rounded-[32px] border border-neutral-300 bg-white/95 px-3 py-2 shadow-lg backdrop-blur-md" aria-label="Navigasi utama">
        @php
            $isHome = request()->routeIs('home');
            $isFav = request()->routeIs('favorites.*');
            $itemCls = 'flex flex-1 flex-col items-center justify-center gap-1 text-xs transition';
            $activeCls = 'font-semibold text-lime-700';
            $inactiveCls = 'text-neutral-700 hover:text-neutral-900';
        @endphp

        {{-- Home --}}
        <a href="{{ route('home') }}" class="{{ $itemCls }} {{ $isHome ? $activeCls : $inactiveCls }}">
            <svg viewBox="0 0 24 24" class="size-6 {{ $isHome ? 'fill-current stroke-current' : 'fill-none stroke-current' }} stroke-[1.8]" aria-hidden="true">
                <path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><path d="M9 22V12h6v10"/>
            </svg>
            <span class="text-[11px] leading-none">Home</span>
        </a>

        {{-- Favorite --}}
        <a href="{{ route('favorites.index') }}" class="{{ $itemCls }} {{ $isFav ? $activeCls : $inactiveCls }}">
            <svg viewBox="0 0 24 24" class="size-6 {{ $isFav ? 'fill-current stroke-current' : 'fill-none stroke-current' }} stroke-[1.8]" aria-hidden="true">
                <path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z"/>
            </svg>
            <span class="text-[11px] leading-none">Favorite</span>
        </a>

        {{-- Jual --}}
        <a href="{{ Route::has('products.create') ? route('products.create') : '#' }}" {{ $needLogin }} class="{{ $itemCls }} {{ request()->routeIs('products.create') ? $activeCls : $inactiveCls }}">
            <svg viewBox="0 0 24 24" class="size-6 fill-none stroke-current stroke-[1.8]" aria-hidden="true">
                <circle cx="12" cy="12" r="10"/><path d="M12 8v8M8 12h8"/>
            </svg>
            <span class="text-[11px] leading-none">Jual</span>
        </a>

        {{-- Chat --}}
        <a href="{{ Route::has('chat.index') ? route('chat.index') : '#' }}" {{ $needLogin }} class="{{ $itemCls }} {{ request()->routeIs('chat.*') ? $activeCls : $inactiveCls }}">
            <svg viewBox="0 0 24 24" class="size-6 fill-none stroke-current stroke-[1.8]" aria-hidden="true">
                <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
            </svg>
            <span class="text-[11px] leading-none">Chat</span>
        </a>
    </nav>
</div>