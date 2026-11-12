@extends('layouts.app')

@section('title', 'RePake - Jual Beli Barang Bekas')

@section('content')
    <div class="mx-auto max-w-6xl px-4 py-6 sm:px-6">

        {{-- Banner (3 Box Persegi Panjang Abu Sesuai Wireframe) --}}
        <section aria-label="Promo">
            <x-banner-slider :images="$banners" />
        </section>

        {{-- Mau cari apa? (8 Kategori) --}}
        <section class="mt-10">
            <h2 class="text-xl font-bold text-neutral-900 mb-4">Mau cari apa?</h2>
            <div class="grid grid-cols-4 gap-x-3 gap-y-5 sm:grid-cols-8 sm:gap-4">
                @foreach ($categories as $category)
                    @php $isActive = $activeCategory && $category->slug === $activeCategory->slug; @endphp
                    <a href="{{ route('home', ['kategori' => $category->slug]) }}" class="group flex flex-col items-center">
                        <div
                            class="flex size-16 shrink-0 items-center justify-center rounded-full bg-neutral-200 transition-all duration-200 ease-out group-hover:scale-110 group-hover:bg-lime-100 group-hover:ring-2 group-hover:ring-lime-500 group-hover:shadow-lg group-hover:shadow-neutral-900/10 sm:size-20 {{ $isActive ? 'bg-lime-100 ring-2 ring-lime-500' : '' }}">
                            @if ($category->icon_url)
                                <img src="{{ $category->icon_url }}" alt=""
                                    class="size-8 object-contain transition-transform duration-200 ease-out group-hover:scale-105 sm:size-9"
                                    onerror="this.remove()">
                            @endif
                        </div>
                        <p
                            class="mt-2.5 text-center text-xs font-medium text-neutral-800 transition-colors duration-200 group-hover:text-neutral-900 sm:text-sm">
                            {{ $category->name }}
                        </p>
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
                <a href="{{ route('products.index') }}"
                    class="rounded-lg border-2 border-neutral-900 bg-white px-6 py-2 text-xs font-bold text-neutral-900 transition hover:bg-neutral-900 hover:text-white">
                    Lihat Lebih Banyak
                </a>
            </div>
        </section>

        {{-- CTA Jual Barang --}}
        <section class="relative mt-12 overflow-hidden rounded-2xl bg-neutral-900 p-8 sm:p-12 md:p-14">
            {{-- Background Image (ganti dengan asset('images/nama-file.jpg') jika pakai gambar lokal) --}}
            <img src="{{ asset('images/repake-banner-notext.webp') }}" alt="Ayo jual barang bekas mu di RePake" loading="lazy"
                class="absolute inset-0 size-full object-cover object-center">

            {{-- Gradient Hitam (lebih pekat di kiri untuk teks, memudar ke kanan agar gambar terlihat) --}}
            <div
                class="absolute inset-0 bg-linear-to-r from-black/90 via-black/70 to-black/30 sm:from-black/85 sm:via-black/60 sm:to-transparent">
            </div>

            {{-- Konten Teks & Tombol --}}
            <div class="relative z-10">
                <h2 class="max-w-lg text-2xl font-extrabold leading-tight text-white sm:text-3xl">
                    Ada barang yang sudah nggak kamu pakai?
                </h2>
                <p class="mt-3 max-w-md text-sm font-medium text-neutral-200">
                    Yuk, jual di RePake dan kasih kesempatan barangmu dipakai orang lain.
                </p>
                <a href="{{ Route::has('products.create') ? route('products.create') : '#' }}" @guest data-open-login
                @endguest
                    class="mt-6 inline-flex rounded-lg bg-lime-400 px-5 py-2.5 text-xs font-bold text-neutral-900 transition hover:bg-lime-500">
                    Jual Barang
                </a>
            </div>
        </section>
    </div>
@endsection