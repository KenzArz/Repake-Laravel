@extends('layouts.app')

@section('title', ($profile['name'] ?? 'Midou Wijaya') . ' - Detail User | RePake')

@section('content')
<div class="mx-auto max-w-6xl px-4 py-6 sm:px-6">

    {{-- Flash Message Sukses --}}
    @if (session('success'))
        <div class="mb-5 flex items-center justify-between rounded-xl bg-lime-50 border border-lime-300 p-3.5 text-xs font-semibold text-lime-900">
            <div class="flex items-center gap-2">
                <svg viewBox="0 0 24 24" class="size-4 fill-current text-lime-700"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>
                <span>{{ session('success') }}</span>
            </div>
            <button type="button" onclick="this.parentElement.remove()" class="text-lime-700 hover:text-lime-900">✕</button>
        </div>
    @endif

    {{-- Detail User Profile Card (Sesuai Wireframe Image 3) --}}
    <div class="rounded-2xl border border-neutral-200 bg-white p-6 sm:p-8 shadow-2xs">
        <div class="flex flex-col gap-6 md:flex-row md:items-center md:justify-between">

            {{-- Kiri: Avatar Siluet Default / Foto Profil + Info User --}}
            <div class="flex items-start sm:items-center gap-5">
                {{-- Lingkaran Avatar Siluet Default Sesuai Permintaan User --}}
                <div class="size-24 shrink-0 rounded-full bg-neutral-200 overflow-hidden border border-neutral-300 grid place-items-center select-none shadow-inner">
                    @if (!empty($profile['avatar']))
                        <img src="{{ asset($profile['avatar']) }}" alt="{{ $profile['name'] }}" class="size-full object-cover">
                    @else
                        <img src="{{ asset('images/default-avatar.png') }}" alt="{{ $profile['name'] ?? 'Profil' }}" class="size-full object-cover">
                    @endif
                </div>

                <div>
                    {{-- Nama Lengkap --}}
                    <h1 class="text-2xl font-black tracking-tight text-neutral-900">
                        {{ $profile['name'] ?? 'Midou Wijaya' }}
                    </h1>

                    {{-- Username --}}
                    <p class="text-xs font-bold text-neutral-800">
                        @<span>{{ $profile['username'] ?? 'midou_kun' }}</span>
                    </p>

                    {{-- Bio --}}
                    <p class="mt-1 text-xs text-neutral-600">
                        {{ $profile['bio'] ?? 'Jual apa aja demi bisa nikahin hiura' }}
                    </p>

                    {{-- Lokasi --}}
                    <p class="mt-2 flex items-center gap-1 text-xs text-neutral-600">
                        <svg viewBox="0 0 24 24" class="size-3.5 fill-none stroke-current stroke-2 text-neutral-700">
                            <path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/>
                        </svg>
                        <span>{{ $profile['kota'] ?? 'Bekasi' }}</span>
                    </p>
                </div>
            </div>

            {{-- Kanan: Rating, 3 Dots Menu, & Tombol Chat Penjual --}}
            <div class="flex flex-col items-start md:items-end justify-between gap-4">
                <div class="flex items-center gap-4">
                    <div class="text-left md:text-right">
                        <a href="{{ route('profile.catalog', ['tab' => 'ulasan']) }}" class="group block">
                            <p class="flex items-center gap-1 text-xs font-bold text-neutral-900 group-hover:text-lime-700 transition">
                                <span class="text-amber-500">★</span>
                                <span>{{ number_format($profile['rating'] ?? 4.5, 1) }} ({{ $profile['reviews_count'] ?? 67 }} ulasan) · {{ $profile['sold_count'] ?? 10 }} Terjual</span>
                            </p>
                            <p class="text-[11px] text-neutral-500 group-hover:underline">Rating &amp; Ulasan</p>
                        </a>
                    </div>

                    {{-- 3 Vertical Dots (Menu Opsi / Edit Profil) --}}
                    <div class="relative">
                        <button type="button" id="profile-options-toggle"
                                class="grid size-8 place-items-center rounded-full text-neutral-700 transition hover:bg-neutral-100 cursor-pointer" aria-label="Menu Opsi">
                            <svg viewBox="0 0 24 24" class="size-4.5 fill-current">
                                <circle cx="12" cy="5" r="1.6"/><circle cx="12" cy="12" r="1.6"/><circle cx="12" cy="19" r="1.6"/>
                            </svg>
                        </button>
                        <div id="profile-options-menu" class="absolute right-0 top-9 z-20 hidden w-44 rounded-xl border border-neutral-200 bg-white py-1.5 shadow-xl ring-1 ring-black/5">
                            <a href="{{ route('profile.edit') }}" class="flex items-center gap-2 px-3 py-2 text-xs font-semibold text-neutral-700 hover:bg-neutral-100 transition">
                                <svg viewBox="0 0 24 24" class="size-4 stroke-current stroke-2 fill-none"><path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/></svg>
                                <span>Edit Profil</span>
                            </a>
                            <form action="{{ route('logout') }}" method="POST">
                                @csrf
                                <button type="submit" class="flex w-full items-center gap-2 px-3 py-2 text-xs font-semibold text-red-600 hover:bg-red-50 transition cursor-pointer">
                                    <svg viewBox="0 0 24 24" class="size-4 stroke-current stroke-2 fill-none"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9"/></svg>
                                    <span>Keluar</span>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                {{-- Tombol Aksi: Edit Profil & Chat Penjual --}}
                <div class="flex items-center gap-2 w-full sm:w-auto">
                    <a href="{{ route('profile.edit') }}"
                       class="flex items-center justify-center gap-1.5 rounded-lg border border-neutral-300 bg-white px-4 py-2.5 text-xs font-bold text-neutral-800 transition hover:bg-neutral-100 shadow-2xs w-full sm:w-auto">
                        <svg viewBox="0 0 24 24" class="size-3.5 stroke-current stroke-2 fill-none"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
                        <span>Edit Profil</span>
                    </a>
                    <a href="{{ Route::has('chat.index') ? route('chat.index') : '#' }}"
                       class="flex items-center justify-center gap-2 rounded-lg bg-[#B8E639] px-6 py-2.5 text-xs font-bold text-neutral-900 transition hover:bg-lime-500 shadow-2xs w-full sm:w-auto">
                        <svg viewBox="0 0 24 24" class="size-4 fill-none stroke-current stroke-2">
                            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
                        </svg>
                        <span>Chat Penjual</span>
                    </a>
                </div>
            </div>

        </div>
    </div>

    {{-- Tabs (Produk, Favorit, Ulasan Sesuai Wireframe) --}}
    <div class="mt-8 border-b border-neutral-200">
        <div class="flex items-center gap-6">
            <a href="{{ route('profile.catalog', ['tab' => 'dijual']) }}"
               class="pb-2.5 text-xs font-bold transition {{ $tab === 'dijual' || empty($tab) ? 'border-b-2 border-neutral-900 text-neutral-900' : 'text-neutral-500 hover:text-neutral-800' }}">
                Produk
            </a>

            <a href="{{ route('profile.catalog', ['tab' => 'favorit']) }}"
               class="pb-2.5 text-xs font-bold transition {{ $tab === 'favorit' ? 'border-b-2 border-neutral-900 text-neutral-900' : 'text-neutral-500 hover:text-neutral-800' }}">
                Favorit ({{ $favoriteCount }})
            </a>

            <a href="{{ route('profile.catalog', ['tab' => 'ulasan']) }}"
               class="pb-2.5 text-xs font-bold transition {{ $tab === 'ulasan' ? 'border-b-2 border-[#8EAE36] text-neutral-900' : 'text-neutral-500 hover:text-neutral-800' }}">
                Ulasan ({{ $reviewCount }})
            </a>
        </div>
    </div>

    {{-- KONTEN TAB --}}
    @if ($tab === 'ulasan')
        {{-- SECTION: SEMUA ULASAN (Sesuai Wireframe Gambar Pengguna) --}}
        <section class="mt-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-xl font-bold tracking-tight text-neutral-900">Semua Ulasan</h2>

                <div class="flex items-center gap-1.5 rounded-full border border-neutral-300 bg-white px-4 py-1.5 text-xs font-semibold text-neutral-800 shadow-2xs">
                    <span>Urutkan: <strong>Terbaru</strong></span>
                    <svg viewBox="0 0 24 24" class="size-3.5 fill-none stroke-current stroke-2"><path d="m6 9 6 6 6-6"/></svg>
                </div>
            </div>

            {{-- Daftar Kartu Ulasan Pembeli --}}
            <div class="space-y-4">
                @forelse ($reviews as $review)
                    <article class="rounded-2xl border border-neutral-200 bg-white p-5 sm:p-6 shadow-2xs transition hover:shadow-xs">
                        {{-- Header Ulasan: Avatar Siluet, Nama Pembeli, Waktu, & Menu 3 Titik --}}
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2.5">
                                <div class="size-8 rounded-full overflow-hidden bg-neutral-200 border border-neutral-300 shrink-0">
                                    <img src="{{ asset('images/default-avatar.png') }}" alt="{{ $review['reviewer_name'] }}" class="size-full object-cover">
                                </div>
                                <div class="flex items-baseline gap-2">
                                    <h3 class="text-xs sm:text-sm font-bold text-neutral-900">{{ $review['reviewer_name'] }}</h3>
                                    <span class="text-xs text-neutral-400 font-normal">{{ $review['time_ago'] }}</span>
                                </div>
                            </div>

                            {{-- Menu 3 Titik Vertikal --}}
                            <button type="button" aria-label="Opsi ulasan"
                                    class="grid size-7 place-items-center rounded-full text-neutral-400 hover:text-neutral-700 hover:bg-neutral-100 transition cursor-pointer">
                                <svg viewBox="0 0 24 24" class="size-4 fill-current">
                                    <circle cx="12" cy="5" r="1.5"/><circle cx="12" cy="12" r="1.5"/><circle cx="12" cy="19" r="1.5"/>
                                </svg>
                            </button>
                        </div>

                        {{-- Bintang Rating (5 Bintang Kuning Sesuai Wireframe) --}}
                        <div class="flex items-center gap-1 mt-2.5 mb-1.5">
                            @for ($i = 0; $i < $review['rating']; $i++)
                                <svg viewBox="0 0 24 24" class="size-4 fill-amber-400 text-amber-400" aria-hidden="true">
                                    <path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/>
                                </svg>
                            @endfor
                        </div>

                        {{-- Isi Komentar Pembeli --}}
                        <p class="text-xs sm:text-sm text-neutral-800 leading-relaxed font-normal">
                            {{ $review['comment'] }}
                        </p>

                        {{-- Kotak Barang yang Terjual (Warna Hijau Lembut Sesuai Gambar Wireframe #EAF5D3) --}}
                        <div class="mt-3.5 flex items-center gap-3.5 rounded-2xl bg-[#EAF5D3] p-3 border border-lime-200/60">
                            <div class="size-14 shrink-0 overflow-hidden rounded-xl bg-white shadow-2xs">
                                <img src="{{ asset($review['product_image']) }}" alt="{{ $review['product_title'] }}"
                                     class="size-full object-cover"
                                     onerror="this.onerror=null; this.src='https://images.unsplash.com/photo-1590658268037-6bf12165a8df?w=200&auto=format&fit=crop&q=80';">
                            </div>
                            <div class="min-w-0 flex-1 leading-snug">
                                <p class="truncate text-xs sm:text-sm font-bold text-neutral-900">{{ $review['product_title'] }}</p>
                                <p class="mt-0.5 text-xs sm:text-sm font-extrabold text-neutral-900">{{ $review['product_price'] }}</p>
                            </div>
                        </div>
                    </article>
                @empty
                    <div class="rounded-2xl border border-neutral-200 bg-white p-8 text-center text-xs text-neutral-500">
                        Belum ada ulasan untuk barang yang sudah terjual.
                    </div>
                @endforelse
            </div>
        </section>
    @else
        {{-- Section: Semua Produk (Dijual / Favorit) --}}
        <section class="mt-6">
            <div class="flex items-center justify-between">
                <h2 class="text-base font-extrabold text-neutral-900">
                    {{ $tab === 'favorit' ? 'Barang Favorit' : 'Semua Produk' }}
                </h2>

                <div class="flex items-center gap-1.5 rounded-lg border border-neutral-300 bg-white px-3 py-1.5 text-xs font-semibold text-neutral-800">
                    <span>Urutkan: <strong>Terbaru</strong></span>
                    <svg viewBox="0 0 24 24" class="size-3.5 fill-none stroke-current stroke-2"><path d="m6 9 6 6 6-6"/></svg>
                </div>
            </div>

            {{-- Grid 4 Kolom x 2 Baris = 8 Produk Sesuai Wireframe --}}
            <div class="mt-4 grid grid-cols-2 gap-4 md:grid-cols-4">
                @forelse ($products as $product)
                    <x-product-card :product="$product" />
                @empty
                    <div class="col-span-full my-8 text-center text-xs text-neutral-500">
                        Tidak ada produk dalam daftar ini.
                    </div>
                @endforelse
            </div>
        </section>
    @endif

</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const toggle = document.getElementById('profile-options-toggle');
    const menu = document.getElementById('profile-options-menu');

    if (toggle && menu) {
        toggle.addEventListener('click', function (e) {
            e.stopPropagation();
            menu.classList.toggle('hidden');
        });

        document.addEventListener('click', function (e) {
            if (!menu.contains(e.target) && !toggle.contains(e.target)) {
                menu.classList.add('hidden');
            }
        });
    }
});
</script>
@endpush
