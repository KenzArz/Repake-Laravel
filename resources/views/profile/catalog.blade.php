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
    <div class="rounded-2xl border border-neutral-200 bg-white p-6 sm:p-8">
        <div class="flex flex-col gap-6 md:flex-row md:items-center md:justify-between">

            {{-- Kiri: Avatar Lingkaran Hijau Lime + Info User --}}
            <div class="flex items-start sm:items-center gap-5">
                {{-- Lingkaran Avatar Hijau Lime --}}
                <div class="size-24 shrink-0 rounded-full bg-[#B8E639] overflow-hidden grid place-items-center">
                    @if (!empty($profile['avatar']))
                        <img src="{{ asset($profile['avatar']) }}" alt="{{ $profile['name'] }}" class="size-full object-cover">
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
                        <p class="flex items-center gap-1 text-xs font-bold text-neutral-900">
                            <span class="text-amber-500">★</span>
                            <span>{{ number_format($profile['rating'] ?? 4.5, 1) }} ({{ $profile['reviews_count'] ?? 67 }} ulasan) · {{ $profile['sold_count'] ?? 10 }} Terjual</span>
                        </p>
                        <p class="text-[11px] text-neutral-500">Rating & Ulasan</p>
                    </div>

                    {{-- 3 Vertical Dots (Menu Opsi / Edit Profil) --}}
                    <div class="relative" x-data="{ open: false }">
                        <button type="button" onclick="document.getElementById('profile-options-menu').classList.toggle('hidden')"
                                class="grid size-8 place-items-center rounded-full text-neutral-700 transition hover:bg-neutral-100" aria-label="Menu Opsi">
                            <svg viewBox="0 0 24 24" class="size-4.5 fill-current">
                                <circle cx="12" cy="5" r="1.6"/><circle cx="12" cy="12" r="1.6"/><circle cx="12" cy="19" r="1.6"/>
                            </svg>
                        </button>
                        <div id="profile-options-menu" class="absolute right-0 top-9 z-20 hidden w-44 rounded-xl border border-neutral-200 bg-white py-1.5 shadow-lg">
                            <a href="{{ route('profile.edit') }}" class="flex items-center gap-2 px-3 py-2 text-xs font-semibold text-neutral-700 hover:bg-neutral-100">
                                <svg viewBox="0 0 24 24" class="size-4 stroke-current stroke-2 fill-none"><path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/></svg>
                                <span>Edit Profil</span>
                            </a>
                            <form action="{{ route('logout') }}" method="POST">
                                @csrf
                                <button type="submit" class="flex w-full items-center gap-2 px-3 py-2 text-xs font-semibold text-red-600 hover:bg-red-50">
                                    <svg viewBox="0 0 24 24" class="size-4 stroke-current stroke-2 fill-none"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9"/></svg>
                                    <span>Keluar</span>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                {{-- Tombol Chat Penjual --}}
                <div class="flex items-center gap-2 w-full sm:w-auto">
                    <a href="{{ Route::has('chat.index') ? route('chat.index') : '#' }}"
                       class="flex items-center justify-center gap-2 rounded-lg bg-[#B8E639] px-6 py-2.5 text-xs font-bold text-neutral-900 transition hover:bg-lime-500 w-full sm:w-auto">
                        <svg viewBox="0 0 24 24" class="size-4 fill-none stroke-current stroke-2">
                            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
                        </svg>
                        <span>Chat Penjual</span>
                    </a>
                </div>
            </div>

        </div>
    </div>

    {{-- Tabs (Produk & Ulasan Sesuai Wireframe) --}}
    <div class="mt-8 border-b border-neutral-200">
        <div class="flex items-center gap-6">
            <a href="{{ route('profile.catalog', ['tab' => 'dijual']) }}"
               class="pb-2 text-xs font-bold transition {{ $tab !== 'favorit' ? 'border-b-2 border-neutral-900 text-neutral-900' : 'text-neutral-500 hover:text-neutral-800' }}">
                Produk
            </a>

            <a href="{{ route('profile.catalog', ['tab' => 'favorit']) }}"
               class="pb-2 text-xs font-bold transition {{ $tab === 'favorit' ? 'border-b-2 border-neutral-900 text-neutral-900' : 'text-neutral-500 hover:text-neutral-800' }}">
                Favorit ({{ $favoriteCount }})
            </a>

            <button type="button" class="pb-2 text-xs font-semibold text-neutral-400 cursor-not-allowed">
                Ulasan
            </button>
        </div>
    </div>

    {{-- Section: Semua Produk --}}
    <section class="mt-6">
        <div class="flex items-center justify-between">
            <h2 class="text-base font-extrabold text-neutral-900">Semua Produk</h2>

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

</div>
@endsection
