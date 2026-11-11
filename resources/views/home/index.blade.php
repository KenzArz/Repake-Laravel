@extends('layouts.app')

@section('title', 'RePake - Jual Beli Barang Bekas')

@section('content')
    <div class="mx-auto max-w-6xl px-4 py-6 sm:px-6">

        {{-- Banner (3 Box Persegi Panjang Abu Sesuai Wireframe) --}}
        <section aria-label="Promo">
            <x-banner-slider :images="$banners" />
        </section>

        {{-- Mau cari apa? (Kategori Scrollable Mobile & Modern Hover Circle) --}}
        <section class="mt-10">
            <h2 class="mb-4 text-xl font-bold tracking-tight text-[var(--color-text-primary)]">
                Mau cari apa?
            </h2>

            {{-- pt-3 -mt-3 pb-3 agar animasi hover tidak terpotong overflow container --}}
            <div
                class="no-scrollbar scrollbar-none -mx-4 -mt-3 flex items-start gap-3 overflow-x-auto px-4 pt-3 pb-3 sm:mx-0 sm:gap-4 sm:px-0 sm:justify-between">
                @foreach ($categories as $category)
                    <a href="{{ route('home', ['kategori' => $category->slug]) }}"
                        class="group flex w-[76px] shrink-0 cursor-pointer flex-col items-center gap-2 text-center sm:w-auto sm:flex-1">

                        {{-- Circle Icon Container --}}
                        <div
                            class="flex h-[64px] w-[64px] shrink-0 items-center justify-center rounded-full border border-[var(--color-border-default)] bg-[var(--color-neutral-100)] transition-all duration-200 ease-out group-hover:-translate-y-1.5 group-hover:border-[var(--color-lime)] group-hover:bg-[var(--color-lime-100)] group-hover:shadow-md sm:h-[72px] sm:w-[72px]">
                            @php
                                $iconSrc = $category->icon_url ?? $category->icon;
                            @endphp

                            @if ($iconSrc)
                                <img src="{{ str_starts_with($iconSrc, 'http') ? $iconSrc : asset('storage/' . $iconSrc) }}"
                                    alt="{{ $category->name }}"
                                    class="h-7 w-7 object-contain transition-transform duration-200 group-hover:scale-110 sm:h-9 sm:w-9" />
                            @else
                                <div class="h-6 w-6 rounded-full bg-[var(--color-neutral-300)]"></div>
                            @endif
                        </div>

                        {{-- Label Teks --}}
                        <span
                            class="line-clamp-2 text-xs font-semibold leading-tight text-[var(--color-neutral-800)] transition-colors duration-200 group-hover:text-[var(--color-neutral-900)]">
                            {{ $category->name }}
                        </span>
                    </a>
                @endforeach
            </div>
        </section>

        {{-- Ada di dekat kamu (4 Kolom Sesuai Wireframe) --}}
        <section class="mt-10">
            <h2 class="text-xl font-bold text-neutral-900 mb-4">Ada di dekat kamu</h2>
            <div class="grid grid-cols-2 gap-4 md:grid-cols-4">
                @foreach ($nearby as $product)
                    <x-product-card :product="$product" />
                @endforeach
            </div>
        </section>

        {{-- Baru di RePake (4 Kolom x 2 Baris = 8 Produk Sesuai Wireframe) --}}
        <section class="mt-10">
            <h2 class="text-xl font-bold text-neutral-900 mb-4">Baru di RePake</h2>
            <div class="grid grid-cols-2 gap-4 md:grid-cols-4">
                @foreach ($latest as $product)
                    <x-product-card :product="$product" />
                @endforeach
            </div>

            <div class="mt-8 flex justify-center">
                <a href="#"
                    class="rounded-lg border-2 border-neutral-900 bg-white px-6 py-2 text-xs font-bold text-neutral-900 transition hover:bg-neutral-900 hover:text-white">
                    Lihat Lebih Banyak
                </a>
            </div>
        </section>

        {{-- CTA Jual Barang dengan Image Background --}}
        <section class="relative mt-12 overflow-hidden rounded-3xl bg-neutral-900 p-8 sm:p-12 md:p-14">
            {{-- Background Image & Gradient Overlay --}}
            <div class="absolute inset-0 z-0">
                {{-- Ganti URL gambar di bawah dengan asset lokal kamu (misal: asset('images/cta-banner.jpg')) --}}
                <img src="https://images.unsplash.com/photo-1556905055-8f358a7a47b2?q=80&w=1200&auto=format&fit=crop"
                    alt="Jual Barang di RePake" class="h-full w-full object-cover object-center opacity-85">

                {{-- Overlay Gradient Gelap agar Teks Selalu Terbaca Jelas --}}
                <div class="absolute inset-0 bg-gradient-to-r from-neutral-950/90 via-neutral-900/75 to-neutral-900/30">
                </div>
            </div>

            {{-- Konten Utama (z-10 di atas background) --}}
            <div class="relative z-10 max-w-lg">
                <h2 class="text-2xl font-extrabold leading-tight text-white sm:text-3xl md:text-4xl drop-shadow-sm">
                    Ada barang yang sudah nggak kamu pakai?
                </h2>
                <p class="mt-3 text-sm font-medium text-neutral-200 sm:text-base leading-relaxed">
                    Yuk, jual di RePake dan kasih kesempatan barangmu dipakai orang lain.
                </p>
                <a href="{{ Route::has('products.create') ? route('products.create') : '#' }}" @guest data-open-login
                @endguest
                    class="mt-6 inline-flex items-center justify-center rounded-xl bg-[#B8E639] px-6 py-3 text-xs sm:text-sm font-bold text-neutral-950 shadow-lg shadow-lime-950/30 transition-all hover:bg-lime-400 hover:scale-105 active:scale-95">
                    Jual Barang
                </a>
            </div>
        </section>
    </div>
@endsection