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
            <a href="{{ $r('profile.catalog', ['tab' => 'favorit']) }}" {{ $needLogin }}
               class="{{ $iconBtn }} hidden md:grid" aria-label="Favorit">
                <svg viewBox="0 0 24 24" class="{{ $svg }}" aria-hidden="true">
                    <path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z"/>
                </svg>
            </a>

            {{-- Inbox --}}
            <a href="{{ $r('chat.index') }}" {{ $needLogin }} class="{{ $iconBtn }}" aria-label="Inbox">
                <svg viewBox="0 0 24 24" class="{{ $svg }}" aria-hidden="true">
                    <rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/>
                </svg>
            </a>

            {{-- Masuk / Profil --}}
            @guest
                <button type="button" data-open-login class="text-h5 hidden px-2 md:inline-block">Masuk</button>
            @else
                <a href="{{ $r('profile.catalog') }}"
                   class="hidden size-9 place-items-center rounded-full bg-neutral-200 text-label font-semibold md:grid"
                   aria-label="Profil">
                    {{ strtoupper(mb_substr(auth()->user()->name, 0, 1)) }}
                </a>
            @endguest

            {{-- +Jual --}}
            <a href="{{ $r('products.create') }}" {{ $needLogin }}
               class="ml-1 rounded-md bg-lime-500 px-4 py-2 text-label font-semibold text-neutral-900 transition hover:bg-lime-600">
                +Jual
            </a>
        </nav>
    </div>
</header>

{{-- Bottom nav (mobile saja) --}}
<nav class="fixed inset-x-0 bottom-0 z-40 grid grid-cols-3 border-t border-neutral-200 bg-white md:hidden" aria-label="Navigasi utama">
    @php
        $item = 'flex flex-col items-center gap-1 py-2 text-caption';
        $on = 'font-semibold text-neutral-900';
        $off = 'text-neutral-500';
    @endphp

    <a href="{{ $r('home') }}" class="{{ $item }} {{ request()->routeIs('home') ? $on : $off }}">
        <svg viewBox="0 0 24 24" class="{{ $svg }}" aria-hidden="true">
            <path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><path d="M9 22V12h6v10"/>
        </svg>
        Home
    </a>

    <a href="{{ $r('profile.catalog', ['tab' => 'favorit']) }}" {{ $needLogin }} class="{{ $item }} {{ $off }}">
        <svg viewBox="0 0 24 24" class="{{ $svg }}" aria-hidden="true">
            <path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z"/>
        </svg>
        Favorit
    </a>

    <a href="{{ $r('profile.catalog') }}" {{ $needLogin }} class="{{ $item }} {{ $off }}">
        <svg viewBox="0 0 24 24" class="{{ $svg }}" aria-hidden="true">
            <path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>
        </svg>
        Profil
    </a>
</nav>