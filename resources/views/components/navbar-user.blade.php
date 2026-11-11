@php
    // Pakai route kalau sudah ada, kalau belum fallback ke '#'
    $r = fn (string $name, $params = []) => Route::has($name) ? route($name, $params) : '#';
    $isGuest = auth()->guest();
    $needLogin = $isGuest ? 'data-open-login' : '';
    $userCity = auth()->user()?->kota ?? session('user_profile.kota', 'Pilih Lokasimu');
    $userAvatar = session('user_profile.avatar') ?? auth()->user()?->avatar ?? null;
@endphp

<header class="sticky top-0 z-40 border-b border-neutral-200 bg-white">
    <div class="mx-auto flex max-w-6xl items-center justify-between gap-3 px-4 py-3 sm:px-6">

        {{-- Logo Brand Sesuai Gambar --}}
        <a href="{{ $r('home') }}" class="flex items-center gap-2 text-xl font-black tracking-tight text-neutral-900 shrink-0">
            <img src="{{ asset('images/repake-logo.png') }}" alt="RePake Logo" class="size-7 rounded-lg object-contain shrink-0" onerror="this.remove()">
            <span>RePake</span>
        </a>

        {{-- Search + Lokasi (Sesuai Wireframe) --}}
        <div class="flex flex-1 items-center gap-3 max-w-xl mx-2">
            <x-search-bar />

            {{-- Location Picker --}}
            <div class="relative shrink-0">
                <button type="button" id="location-picker-toggle" data-location-picker
                    class="flex h-10 shrink-0 items-center gap-1.5 rounded-lg border border-neutral-300 bg-white px-3 text-xs font-semibold text-neutral-700 hover:border-neutral-900 transition whitespace-nowrap cursor-pointer"
                    aria-label="Pilih Lokasi">
                    <svg viewBox="0 0 24 24" class="size-4 text-neutral-700 stroke-current stroke-2 fill-none" aria-hidden="true">
                        <path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/>
                    </svg>
                    <span id="current-location-label">{{ $userCity ?? 'Pilih Lokasimu' }}</span>
                </button>

                {{-- Dropdown Lokasi Kampus & Sekitar --}}
                <div id="location-picker-dropdown"
                     class="absolute left-0 mt-2 hidden w-60 origin-top-left rounded-2xl border border-neutral-200 bg-white p-2 shadow-xl ring-1 ring-black/5 z-50">
                    <p class="px-3 py-1.5 text-[10px] font-bold uppercase tracking-wider text-neutral-400">Pilih Lokasi Kampus / Domisili</p>
                    <div class="space-y-0.5">
                        @foreach (['Pilih Lokasimu', 'Kampus 1 Unsika (Telukjambe)', 'Kampus 2 Unsika', 'Galuh Mas Karawang', 'Telukjambe Timur', 'Klari', 'Bekasi', 'Karawang Barat'] as $loc)
                            <button type="button"
                                    onclick="selectLocation('{{ $loc }}')"
                                    class="flex w-full items-center justify-between rounded-xl px-3 py-2 text-xs font-medium text-neutral-700 hover:bg-neutral-100 hover:text-neutral-900 transition text-left cursor-pointer">
                                <span>{{ $loc }}</span>
                                @if (($userCity ?? 'Pilih Lokasimu') === $loc)
                                    <svg viewBox="0 0 24 24" class="size-3.5 fill-none stroke-lime-600 stroke-2"><path d="M20 6 9 17l-5-5"/></svg>
                                @endif
                            </button>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        {{-- Right Actions (Sesuai Wireframe: Heart, Mail, Masuk/Avatar, +Jual) --}}
        <div class="flex items-center gap-3 sm:gap-4 shrink-0">
            {{-- Heart (Favorit) --}}
            <a href="{{ route('favorites.index') }}" {{ $needLogin }} class="text-neutral-700 hover:text-neutral-900 transition" aria-label="Favorit">
                <svg viewBox="0 0 24 24" class="size-5 fill-none stroke-current stroke-2 [stroke-linecap:round] [stroke-linejoin:round]">
                    <path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z"/>
                </svg>
            </a>

            {{-- Mail (Chat) --}}
            <a href="{{ $r('chat.index') }}" {{ $needLogin }} class="text-neutral-700 hover:text-neutral-900 transition" aria-label="Inbox">
                <svg viewBox="0 0 24 24" class="size-5 fill-none stroke-current stroke-2">
                    <rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/>
                </svg>
            </a>

            {{-- State: GUEST vs SUDAH LOGIN (Sesuai Wireframe) --}}
            @if (auth()->check())
                {{-- SUDAH LOGIN: Lingkaran Foto Avatar + Chevron Panah Bawah 'v' (Tanpa teks username di navbar) --}}
                <div class="relative" id="user-menu-wrapper">
                    <button type="button" id="user-menu-toggle"
                            class="flex items-center gap-1.5 rounded-full p-0.5 hover:ring-2 hover:ring-neutral-200 transition cursor-pointer"
                            aria-expanded="false" aria-label="Buka Menu Pengguna">
                        {{-- Lingkaran Foto Avatar --}}
                        <div class="size-8 rounded-full overflow-hidden border border-neutral-300 bg-neutral-100 shrink-0 shadow-2xs">
                            @if (!empty($userAvatar))
                                @if (str_starts_with($userAvatar, 'http'))
                                    <img src="{{ $userAvatar }}" alt="{{ auth()->user()->name }}" class="size-full object-cover">
                                @else
                                    <img src="{{ asset($userAvatar) }}" alt="{{ auth()->user()->name }}" class="size-full object-cover">
                                @endif
                            @else
                                <img src="{{ asset('images/default-avatar.png') }}" alt="{{ auth()->user()->name }}" class="size-full object-cover" onerror="this.onerror=null; this.src='https://ui-avatars.com/api/?name={{ urlencode(auth()->user()->name) }}&background=B8E639&color=1c1917';">
                            @endif
                        </div>
                        {{-- Chevron Down 'v' Sesuai Gambar --}}
                        <svg viewBox="0 0 24 24" class="size-3.5 text-neutral-700 stroke-current stroke-2 fill-none transition-transform duration-200" id="user-menu-chevron" aria-hidden="true">
                            <path d="m6 9 6 6 6-6"/>
                        </svg>
                    </button>

                    {{-- Dropdown Menu Profil & Logout --}}
                    <div id="user-menu-dropdown"
                         class="absolute right-0 mt-2 hidden w-56 origin-top-right rounded-2xl border border-neutral-200 bg-white p-2 shadow-xl ring-1 ring-black/5 z-50">
                        <div class="px-3 py-2.5 border-b border-neutral-100">
                            <p class="text-xs font-bold text-neutral-900 truncate">{{ auth()->user()->name }}</p>
                            <p class="text-[11px] text-neutral-500 truncate">{{ auth()->user()->email }}</p>
                            <span class="inline-block mt-1 rounded bg-lime-100 px-1.5 py-0.5 text-[9px] font-bold text-lime-800">UNSIKA Verified</span>
                        </div>

                        <div class="py-1">
                            <a href="{{ route('profile.catalog') }}"
                               class="flex items-center gap-2.5 rounded-xl px-3 py-2 text-xs font-medium text-neutral-700 hover:bg-neutral-100 hover:text-neutral-900 transition">
                                <svg viewBox="0 0 24 24" class="size-4 stroke-current stroke-2 fill-none"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                                <span>Profil & Katalog Saya</span>
                            </a>
                            <a href="{{ route('profile.edit') }}"
                               class="flex items-center gap-2.5 rounded-xl px-3 py-2 text-xs font-medium text-neutral-700 hover:bg-neutral-100 hover:text-neutral-900 transition">
                                <svg viewBox="0 0 24 24" class="size-4 stroke-current stroke-2 fill-none"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
                                <span>Edit Profil</span>
                            </a>
                            <a href="{{ route('favorites.index') }}"
                               class="flex items-center gap-2.5 rounded-xl px-3 py-2 text-xs font-medium text-neutral-700 hover:bg-neutral-100 hover:text-neutral-900 transition">
                                <svg viewBox="0 0 24 24" class="size-4 stroke-current stroke-2 fill-none"><path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z"/></svg>
                                <span>Produk Favorit</span>
                            </a>
                        </div>

                        <div class="border-t border-neutral-100 pt-1">
                            <form action="{{ route('logout') }}" method="POST">
                                @csrf
                                <button type="submit"
                                        class="flex w-full items-center gap-2.5 rounded-xl px-3 py-2 text-xs font-medium text-red-600 hover:bg-red-50 transition cursor-pointer">
                                    <svg viewBox="0 0 24 24" class="size-4 stroke-current stroke-2 fill-none"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                                    <span>Keluar (Logout)</span>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            @else
                {{-- GUEST: Tombol Teks 'Masuk' Polos Sesuai Wireframe --}}
                <button type="button" data-open-login class="text-xs font-bold text-neutral-900 hover:underline cursor-pointer">
                    Masuk
                </button>
            @endif

            {{-- Tombol +Jual (Pill Lime Hijau Sesuai Wireframe) --}}
            <a href="{{ $r('products.create') }}" {{ $needLogin }}
               class="rounded-lg bg-lime-400 px-4 py-2 text-xs font-bold text-neutral-900 transition hover:bg-lime-500 shadow-2xs">
                +Jual
            </a>
        </div>
    </div>
</header>

{{-- Script Interaktif Dropdown Navbar & Location Picker --}}
<script>
document.addEventListener('DOMContentLoaded', function () {
    // Dropdown User Menu (Avatar + Chevron)
    const userBtn = document.getElementById('user-menu-toggle');
    const userDropdown = document.getElementById('user-menu-dropdown');
    const userChevron = document.getElementById('user-menu-chevron');

    if (userBtn && userDropdown) {
        userBtn.addEventListener('click', function (e) {
            e.stopPropagation();
            const isHidden = userDropdown.classList.contains('hidden');
            if (isHidden) {
                userDropdown.classList.remove('hidden');
                userBtn.setAttribute('aria-expanded', 'true');
                if (userChevron) userChevron.classList.add('rotate-180');
            } else {
                userDropdown.classList.add('hidden');
                userBtn.setAttribute('aria-expanded', 'false');
                if (userChevron) userChevron.classList.remove('rotate-180');
            }
        });
    }

    // Dropdown Lokasi
    const locBtn = document.getElementById('location-picker-toggle');
    const locDropdown = document.getElementById('location-picker-dropdown');

    if (locBtn && locDropdown) {
        locBtn.addEventListener('click', function (e) {
            e.stopPropagation();
            locDropdown.classList.toggle('hidden');
        });
    }

    // Klik di luar untuk menutup semua dropdown
    document.addEventListener('click', function (e) {
        if (userDropdown && !userDropdown.contains(e.target) && userBtn && !userBtn.contains(e.target)) {
            userDropdown.classList.add('hidden');
            if (userBtn) userBtn.setAttribute('aria-expanded', 'false');
            if (userChevron) userChevron.classList.remove('rotate-180');
        }
        if (locDropdown && !locDropdown.contains(e.target) && locBtn && !locBtn.contains(e.target)) {
            locDropdown.classList.add('hidden');
        }
    });

    // Tekan tombol ESC untuk menutup
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            if (userDropdown) userDropdown.classList.add('hidden');
            if (locDropdown) locDropdown.classList.add('hidden');
            if (userChevron) userChevron.classList.remove('rotate-180');
        }
    });

    // Pulihkan preferensi lokasi jika ada
    try {
        const savedLoc = localStorage.getItem('repake_location');
        const label = document.getElementById('current-location-label');
        if (savedLoc && label && label.textContent.trim() === 'Pilih Lokasimu') {
            label.textContent = savedLoc;
        }
    } catch (e) {}
});

function selectLocation(loc) {
    const label = document.getElementById('current-location-label');
    if (label) label.textContent = loc;
    const dropdown = document.getElementById('location-picker-dropdown');
    if (dropdown) dropdown.classList.add('hidden');
    try {
        localStorage.setItem('repake_location', loc);
    } catch (e) {}
}
</script>

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
        <a href="{{ route('favorites.index') }}" {{ $needLogin }} class="{{ $itemCls }} {{ $isFav ? $activeCls : $inactiveCls }}">
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
