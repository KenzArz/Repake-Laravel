@php
    $r = fn(string $name, $params = []) => Route::has($name) ? route($name, $params) : '#';
    $isGuest = auth()->guest();
    $needLogin = $isGuest ? 'data-open-login' : '';
    $userAvatar = session('user_profile.avatar') ?? auth()->user()?->avatar ?? null;

    $activeKota = request('kota')
        ?? session('user_location.kota')
        ?? auth()->user()?->kota
        ?? session('user_profile.kota', 'Pilih Lokasimu');

    $locationPresets = [
        ['label' => 'Kampus 1 Unsika (Telukjambe)', 'lat' => -6.3236, 'lng' => 107.3065],
        ['label' => 'Kampus 2 Unsika', 'lat' => -6.3298, 'lng' => 107.3112],
        ['label' => 'Galuh Mas Karawang', 'lat' => -6.3269, 'lng' => 107.2896],
        ['label' => 'Telukjambe Timur', 'lat' => -6.3250, 'lng' => 107.3010],
        ['label' => 'Klari', 'lat' => -6.3489, 'lng' => 107.3514],
        ['label' => 'Karawang Barat', 'lat' => -6.3023, 'lng' => 107.2952],
        ['label' => 'Bekasi', 'lat' => -6.2383, 'lng' => 106.9756],
    ];
@endphp

<header class="sticky top-0 z-40 border-b border-neutral-200 bg-white/95 backdrop-blur-md">
    <div
        class="mx-auto flex max-w-6xl flex-wrap items-center justify-between gap-2.5 px-4 py-2.5 sm:px-6 md:flex-nowrap md:gap-3 md:py-3">

        {{-- 1. Logo Brand --}}
        <a href="{{ $r('home') }}"
            class="order-1 flex shrink-0 items-center gap-2 text-lg sm:text-xl font-black tracking-tight text-neutral-900">
            <img src="{{ asset('images/repake-logo.png') }}" alt="RePake Logo"
                class="size-7 rounded-lg object-contain shrink-0" onerror="this.remove()">
            <span>RePake</span>
        </a>

        {{-- 2. Desktop Search Bar (Hidden di mobile, diganti ikon trigger + full-screen overlay) --}}
        <div class="order-2 hidden flex-1 max-w-md lg:max-w-lg md:block">
            <x-search-bar />
        </div>

        {{-- 3. Location Picker (Mobile: baris ke-2 full-width | Desktop: sejajar di baris utama) --}}
        <div class="relative order-3 w-full md:w-auto shrink-0" id="navbar-location-wrapper"
            data-home-url="{{ route('home') }}" data-search-url="{{ route('products.index') }}">

            <button type="button" id="location-picker-toggle" data-location-picker
                class="flex h-9 md:h-10 w-full md:w-auto md:max-w-48 lg:max-w-56 items-center justify-between md:justify-start gap-1.5 rounded-xl md:rounded-lg border border-neutral-300 bg-neutral-50/70 md:bg-white px-3 text-xs font-semibold text-neutral-700 hover:border-neutral-900 transition whitespace-nowrap cursor-pointer"
                aria-label="Pilih Lokasi">
                <span class="flex items-center gap-1.5 min-w-0">
                    <svg viewBox="0 0 24 24"
                        class="size-4 shrink-0 text-lime-700 md:text-neutral-700 stroke-current stroke-2 fill-none"
                        aria-hidden="true">
                        <path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z" />
                        <circle cx="12" cy="10" r="3" />
                    </svg>
                    <span id="current-location-label" class="truncate">{{ $activeKota ?: 'Pilih Lokasimu' }}</span>
                </span>
                <svg viewBox="0 0 24 24" class="size-3.5 shrink-0 text-neutral-500 stroke-current stroke-2 fill-none"
                    aria-hidden="true">
                    <path d="m6 9 6 6 6-6" />
                </svg>
            </button>

            {{-- Dropdown Pencarian Lokasi & Preset --}}
            <div id="location-picker-dropdown"
                class="absolute left-0 right-0 md:right-auto mt-2 hidden w-full md:w-80 origin-top-left rounded-2xl border border-neutral-200 bg-white p-3 shadow-xl ring-1 ring-black/5 z-50">

                {{-- Input Pencarian Lokasi --}}
                <div class="relative">
                    <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-neutral-400">
                        <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <circle cx="11" cy="11" r="8" />
                            <path d="m21 21-4.3-4.3" />
                        </svg>
                    </span>
                    <input type="text" id="navbar-location-search" autocomplete="off"
                        placeholder="Cari kampus, kelurahan, atau kota..."
                        class="w-full rounded-xl border border-neutral-300 bg-neutral-50 py-2 pl-8 pr-3 text-xs text-neutral-900 placeholder-neutral-400 outline-none transition focus:border-lime-500 focus:bg-white">
                </div>

                {{-- Hasil Pencarian OpenStreetMap --}}
                <div id="navbar-location-results"
                    class="mt-1.5 hidden max-h-40 overflow-y-auto rounded-xl border border-neutral-200 bg-white divide-y divide-neutral-100">
                </div>

                {{-- Tombol Live GPS --}}
                <button type="button" id="navbar-btn-live-location"
                    class="mt-2 flex w-full items-center gap-2 rounded-xl px-2.5 py-2 text-xs font-bold text-lime-700 hover:bg-lime-50 transition cursor-pointer">
                    <svg class="size-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                        stroke-width="2">
                        <circle cx="12" cy="12" r="3" />
                        <path d="M12 2v3m0 14v3M2 12h3m14 0h3" />
                    </svg>
                    <span>Gunakan lokasi saat ini</span>
                </button>
                <p id="navbar-location-status" class="hidden px-2.5 pb-1 text-[11px] font-medium text-neutral-500"></p>

                <div class="my-2 border-t border-neutral-100"></div>

                {{-- Daftar Preset Lokasi Populer --}}
                <div class="flex items-center justify-between px-2.5 py-1">
                    <p class="text-[10px] font-bold uppercase tracking-wider text-neutral-400">Lokasi Populer</p>
                    @if (($activeKota ?? 'Pilih Lokasimu') !== 'Pilih Lokasimu')
                        <button type="button" data-reset-location
                            class="text-[10px] font-bold text-red-500 hover:underline cursor-pointer">
                            Reset
                        </button>
                    @endif
                </div>

                <div class="mt-0.5 max-h-48 space-y-0.5 overflow-y-auto">
                    @foreach ($locationPresets as $preset)
                        <button type="button" data-preset-location data-label="{{ $preset['label'] }}"
                            data-lat="{{ $preset['lat'] }}" data-lng="{{ $preset['lng'] }}"
                            class="flex w-full items-center justify-between rounded-xl px-2.5 py-2 text-xs font-medium text-neutral-700 hover:bg-neutral-100 hover:text-neutral-900 transition text-left cursor-pointer">
                            <span class="truncate">{{ $preset['label'] }}</span>
                            @if ($activeKota === $preset['label'])
                                <svg viewBox="0 0 24 24" class="size-3.5 shrink-0 fill-none stroke-lime-600 stroke-2">
                                    <path d="M20 6 9 17l-5-5" />
                                </svg>
                            @endif
                        </button>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- 4. Right Actions --}}
        <div class="order-2 md:order-4 flex items-center gap-2 sm:gap-3.5 shrink-0">
            {{-- Mobile Search Icon Trigger --}}
            <button type="button" onclick="window.dispatchEvent(new CustomEvent('open-mobile-search'))"
                class="md:hidden rounded-xl p-2 text-neutral-700 hover:bg-neutral-100 hover:text-neutral-900 transition cursor-pointer"
                aria-label="Cari produk">
                <svg viewBox="0 0 24 24" class="size-5 fill-none stroke-current stroke-2">
                    <circle cx="11" cy="11" r="8" />
                    <path d="m21 21-4.3-4.3" />
                </svg>
            </button>

            {{-- Heart (Favorit) --}}
            <a href="{{ route('favorites.index') }}" {{ $needLogin }}
                class="hidden sm:inline-flex text-neutral-700 hover:text-neutral-900 transition" aria-label="Favorit">
                <svg viewBox="0 0 24 24"
                    class="size-5 fill-none stroke-current stroke-2 [stroke-linecap:round] [stroke-linejoin:round]">
                    <path
                        d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z" />
                </svg>
            </a>

            {{-- Mail (Chat) --}}
            <a href="{{ $r('chat.index') }}" {{ $needLogin }}
                class="relative hidden sm:inline-flex text-neutral-700 hover:text-neutral-900 transition" aria-label="Inbox">
                <svg viewBox="0 0 24 24" class="size-5 fill-none stroke-current stroke-2">
                    <rect width="20" height="16" x="2" y="4" rx="2" />
                    <path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7" />
                </svg>
                @auth
                    <span data-chat-badge data-badge-url="{{ route('chat.unread') }}"
                        style="display:none"
                        class="absolute -right-2 -top-2 h-4 min-w-4 place-items-center rounded-full bg-red-500 px-1 text-[10px] font-bold leading-none text-white ring-2 ring-white">0</span>
                @endauth
            </a>

            {{-- State: GUEST vs SUDAH LOGIN --}}
            @if (auth()->check())
                <div class="relative" id="user-menu-wrapper">
                    <button type="button" id="user-menu-toggle"
                        class="flex items-center gap-1.5 rounded-full p-0.5 hover:ring-2 hover:ring-neutral-200 transition cursor-pointer"
                        aria-expanded="false" aria-label="Buka Menu Pengguna">
                        <div
                            class="size-8 rounded-full overflow-hidden border border-neutral-300 bg-neutral-100 shrink-0 shadow-2xs">
                            @if (!empty($userAvatar))
                                <img src="{{ str_starts_with($userAvatar, 'http') ? $userAvatar : asset($userAvatar) }}"
                                    alt="{{ auth()->user()->name }}" class="size-full object-cover">
                            @else
                                <img src="{{ asset('images/default-avatar.png') }}" alt="{{ auth()->user()->name }}"
                                    class="size-full object-cover"
                                    onerror="this.onerror=null; this.src='https://ui-avatars.com/api/?name={{ urlencode(auth()->user()->name) }}&background=B8E639&color=1c1917';">
                            @endif
                        </div>
                        <svg viewBox="0 0 24 24"
                            class="size-3.5 text-neutral-700 stroke-current stroke-2 fill-none transition-transform duration-200"
                            id="user-menu-chevron" aria-hidden="true">
                            <path d="m6 9 6 6 6-6" />
                        </svg>
                    </button>

                    {{-- Dropdown Menu Profil & Logout --}}
                    <div id="user-menu-dropdown"
                        class="absolute right-0 mt-2 hidden w-56 origin-top-right rounded-2xl border border-neutral-200 bg-white p-2 shadow-xl ring-1 ring-black/5 z-50">
                        <div class="px-3 py-2.5 border-b border-neutral-100">
                            <p class="text-xs font-bold text-neutral-900 truncate">{{ auth()->user()->name }}</p>
                            <p class="text-[11px] text-neutral-500 truncate">{{ auth()->user()->email }}</p>
                            <span
                                class="inline-block mt-1 rounded bg-lime-100 px-1.5 py-0.5 text-[9px] font-bold text-lime-800">
                                UNSIKA Verified
                            </span>
                        </div>

                        <div class="py-1">
                            <a href="{{ route('profile.catalog') }}"
                                class="flex items-center gap-2.5 rounded-xl px-3 py-2 text-xs font-medium text-neutral-700 hover:bg-neutral-100 hover:text-neutral-900 transition">
                                <svg viewBox="0 0 24 24" class="size-4 stroke-current stroke-2 fill-none">
                                    <path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2" />
                                    <circle cx="12" cy="7" r="4" />
                                </svg>
                                <span>Profil & Katalog Saya</span>
                            </a>
                            <a href="{{ route('profile.edit') }}"
                                class="flex items-center gap-2.5 rounded-xl px-3 py-2 text-xs font-medium text-neutral-700 hover:bg-neutral-100 hover:text-neutral-900 transition">
                                <svg viewBox="0 0 24 24" class="size-4 stroke-current stroke-2 fill-none">
                                    <path d="M12 20h9" />
                                    <path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z" />
                                </svg>
                                <span>Edit Profil</span>
                            </a>
                            <a href="{{ route('favorites.index') }}"
                                class="flex items-center gap-2.5 rounded-xl px-3 py-2 text-xs font-medium text-neutral-700 hover:bg-neutral-100 hover:text-neutral-900 transition">
                                <svg viewBox="0 0 24 24" class="size-4 stroke-current stroke-2 fill-none">
                                    <path
                                        d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z" />
                                </svg>
                                <span>Produk Favorit</span>
                            </a>
                        </div>

                        <div class="border-t border-neutral-100 pt-1">
                            <form action="{{ route('logout') }}" method="POST">
                                @csrf
                                <button type="submit"
                                    class="flex w-full items-center gap-2.5 rounded-xl px-3 py-2 text-xs font-medium text-red-600 hover:bg-red-50 transition cursor-pointer">
                                    <svg viewBox="0 0 24 24" class="size-4 stroke-current stroke-2 fill-none">
                                        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" />
                                        <polyline points="16 17 21 12 16 7" />
                                        <line x1="21" y1="12" x2="9" y2="12" />
                                    </svg>
                                    <span>Keluar (Logout)</span>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            @else
                <button type="button" data-open-login
                    class="px-2 py-1.5 text-xs font-bold text-neutral-900 hover:underline cursor-pointer">
                    Masuk
                </button>
            @endif

            {{-- Tombol +Jual --}}
            <a href="{{ $r('products.create') }}" {{ $needLogin }}
                class="rounded-lg bg-lime-400 px-3.5 sm:px-4 py-2 text-xs font-bold text-neutral-900 transition hover:bg-lime-500 shadow-2xs">
                +Jual
            </a>
        </div>
    </div>
</header>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const userBtn = document.getElementById('user-menu-toggle');
        const userDropdown = document.getElementById('user-menu-dropdown');
        const userChevron = document.getElementById('user-menu-chevron');

        if (userBtn && userDropdown) {
            userBtn.addEventListener('click', function (e) {
                e.stopPropagation();
                const isHidden = userDropdown.classList.contains('hidden');
                userDropdown.classList.toggle('hidden', !isHidden);
                userBtn.setAttribute('aria-expanded', String(isHidden));
                if (userChevron) userChevron.classList.toggle('rotate-180', isHidden);
            });
        }

        document.addEventListener('click', function (e) {
            if (userDropdown && !userDropdown.contains(e.target) && userBtn && !userBtn.contains(e.target)) {
                userDropdown.classList.add('hidden');
                if (userBtn) userBtn.setAttribute('aria-expanded', 'false');
                if (userChevron) userChevron.classList.remove('rotate-180');
            }
        });

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                if (userDropdown) userDropdown.classList.add('hidden');
                if (userBtn) userBtn.setAttribute('aria-expanded', 'false');
                if (userChevron) userChevron.classList.remove('rotate-180');
                document.getElementById('location-picker-dropdown')?.classList.add('hidden');
            }
        });
    });
</script>

{{-- Bottom nav (mobile saja) - Floating Curved Pill Bar --}}
<div class="fixed inset-x-0 bottom-3 z-40 px-4 md:hidden">
    <nav class="mx-auto flex max-w-sm items-center justify-around rounded-[32px] border border-neutral-300 bg-white/95 px-3 py-2 shadow-lg backdrop-blur-md"
        aria-label="Navigasi utama">
        @php
            $isHome = request()->routeIs('home');
            $isFav = request()->routeIs('favorites.*');
            $itemCls = 'flex flex-1 flex-col items-center justify-center gap-1 text-xs transition';
            $activeCls = 'font-semibold text-lime-700';
            $inactiveCls = 'text-neutral-700 hover:text-neutral-900';
        @endphp

        <a href="{{ route('home') }}" class="{{ $itemCls }} {{ $isHome ? $activeCls : $inactiveCls }}">
            <svg viewBox="0 0 24 24"
                class="size-6 {{ $isHome ? 'fill-current stroke-current' : 'fill-none stroke-current' }} stroke-[1.8]"
                aria-hidden="true">
                <path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z" />
                <path d="M9 22V12h6v10" />
            </svg>
            <span class="text-[11px] leading-none">Home</span>
        </a>

        <a href="{{ route('favorites.index') }}" {{ $needLogin }}
            class="{{ $itemCls }} {{ $isFav ? $activeCls : $inactiveCls }}">
            <svg viewBox="0 0 24 24"
                class="size-6 {{ $isFav ? 'fill-current stroke-current' : 'fill-none stroke-current' }} stroke-[1.8]"
                aria-hidden="true">
                <path
                    d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z" />
            </svg>
            <span class="text-[11px] leading-none">Favorite</span>
        </a>

        <a href="{{ Route::has('products.create') ? route('products.create') : '#' }}" {{ $needLogin }}
            class="{{ $itemCls }} {{ request()->routeIs('products.create') ? $activeCls : $inactiveCls }}">
            <svg viewBox="0 0 24 24" class="size-6 fill-none stroke-current stroke-[1.8]" aria-hidden="true">
                <circle cx="12" cy="12" r="10" />
                <path d="M12 8v8M8 12h8" />
            </svg>
            <span class="text-[11px] leading-none">Jual</span>
        </a>

        <a href="{{ Route::has('chat.index') ? route('chat.index') : '#' }}" {{ $needLogin }}
            class="{{ $itemCls }} {{ request()->routeIs('chat.*') ? $activeCls : $inactiveCls }}">
            <span class="relative inline-flex">
                <svg viewBox="0 0 24 24" class="size-6 fill-none stroke-current stroke-[1.8]" aria-hidden="true">
                    <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z" />
                </svg>
                @auth
                    <span data-chat-badge data-badge-url="{{ route('chat.unread') }}"
                        style="display:none"
                        class="absolute -right-2 -top-1.5 h-4 min-w-4 place-items-center rounded-full bg-red-500 px-1 text-[10px] font-bold leading-none text-white">0</span>
                @endauth
            </span>
            <span class="text-[11px] leading-none">Chat</span>
        </a>
    </nav>
</div>