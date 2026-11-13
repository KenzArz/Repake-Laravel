@extends('layouts.app')

@section('title', 'RePake - Jual Beli Barang Bekas')

@section('content')

    {{-- Banner Stacking Carousel (Full-width agar peek kiri & kanan tidak terpotong) --}}
    <section aria-label="Promo" class="pt-4 sm:pt-6">
        <x-banner-slider :images="$banners" />
    </section>

    <div class="mx-auto max-w-6xl px-4 py-2 sm:px-6">

        {{-- Mau cari apa? (1 Baris Horizontal Scroll seperti Evensie) --}}
        <section class="mt-8">
            <h2 class="mb-4 text-xl font-bold text-neutral-900">Mau cari apa?</h2>
            <div
                class="-mx-4 -mt-2.5 flex flex-nowrap items-start gap-3 overflow-x-auto px-4 pt-2.5 pb-2 [scrollbar-width:none] [-ms-overflow-style:none] [&::-webkit-scrollbar]:hidden sm:mx-0 sm:justify-between sm:gap-2 sm:px-0">
                @foreach ($categories as $category)
                    <a href="{{ route('products.index', ['kategori' => [$category->slug]]) }}"
                        class="group flex w-[76px] shrink-0 flex-col items-center text-center sm:w-auto sm:flex-1">
                        <div
                            class="flex size-16 shrink-0 items-center justify-center rounded-full bg-neutral-200 transition-all duration-200 ease-out group-hover:-translate-y-1.5 group-hover:bg-lime-100 group-hover:ring-2 group-hover:ring-lime-500 group-hover:shadow-md sm:size-[72px]">
                            @if ($category->icon_url)
                                <img src="{{ $category->icon_url }}" alt="{{ $category->name }}"
                                    class="size-8 object-contain transition-transform duration-200 ease-out group-hover:scale-105 sm:size-9"
                                    onerror="this.remove()">
                            @endif
                        </div>
                        <p
                            class="mt-2 line-clamp-2 text-xs font-semibold leading-tight text-neutral-800 transition-colors duration-200 group-hover:text-neutral-900">
                            {{ $category->name }}
                        </p>
                    </a>
                @endforeach
            </div>
        </section>

        {{-- Ada di dekat kamu (4 Kolom) --}}
        <section class="mt-10">
            <h2 class="mb-4 text-xl font-bold text-neutral-900">
                <a href="{{ route('products.index', ['sort' => 'terdekat']) }}"
                    class="group flex w-full items-center justify-between transition-colors hover:text-lime-700">
                    <span>Ada di dekat kamu</span>
                    <svg viewBox="0 0 24 24"
                        class="size-5 shrink-0 fill-none stroke-current stroke-[2.5] text-neutral-700 transition-transform duration-200 group-hover:translate-x-1 group-hover:text-lime-700"
                        aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m9 18 6-6-6-6" />
                    </svg>
                </a>
            </h2>
            <div class="grid grid-cols-2 gap-4 md:grid-cols-4">
                @foreach ($nearby as $product)
                    <x-product-card :product="$product" />
                @endforeach
            </div>
        </section>

        {{-- Baru di RePake (4 Kolom x 2 Baris = 8 Produk) --}}
        <section class="mt-10">
            <h2 class="mb-4 text-xl font-bold text-neutral-900">
                <a href="{{ route('products.index') }}"
                    class="group flex w-full items-center justify-between transition-colors hover:text-lime-700">
                    <span>Baru di RePake</span>
                    <svg viewBox="0 0 24 24"
                        class="size-5 shrink-0 fill-none stroke-current stroke-[2.5] text-neutral-700 transition-transform duration-200 group-hover:translate-x-1 group-hover:text-lime-700"
                        aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m9 18 6-6-6-6" />
                    </svg>
                </a>
            </h2>
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
            <img src="{{ asset('images/repake-banner-notext.webp') }}" alt="Ayo jual barang bekas mu di RePake"
                loading="lazy" class="absolute inset-0 size-full object-cover object-center">

            <div
                class="absolute inset-0 bg-linear-to-r from-black/90 via-black/70 to-black/30 sm:from-black/85 sm:via-black/60 sm:to-transparent">
            </div>

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