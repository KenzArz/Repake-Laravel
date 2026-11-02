@extends('layouts.app')

@section('title', 'RePake - Jual Beli Barang Bekas')

@section('content')
<div class="mx-auto max-w-6xl px-4 py-6 sm:px-6">

    {{-- Banner (3 Box Persegi Panjang Abu Sesuai Wireframe) --}}
    <section aria-label="Promo">
        <x-banner-slider :images="$banners" />
    </section>

    {{-- Mau cari apa? (8 Kategori Sesuai Wireframe) --}}
    <section class="mt-10">
        <h2 class="text-xl font-bold text-neutral-900 mb-4">Mau cari apa?</h2>
        <div class="grid grid-cols-4 gap-3 sm:grid-cols-8 sm:gap-4">
            @foreach ($categories as $category)
                <a href="{{ route('home', ['kategori' => $category->slug]) }}" class="group flex flex-col items-center">
                    <div class="aspect-square w-full overflow-hidden rounded-xl bg-neutral-300 transition group-hover:bg-neutral-400">
                        @if ($category->icon_url)
                            <img src="{{ $category->icon_url }}" alt="" class="size-full object-cover" onerror="this.remove()">
                        @endif
                    </div>
                    <p class="mt-2 text-center text-xs font-semibold text-neutral-800">{{ $category->name }}</p>
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
            <a href="#" class="rounded-lg border-2 border-neutral-900 bg-white px-6 py-2 text-xs font-bold text-neutral-900 transition hover:bg-neutral-900 hover:text-white">
                Lihat Lebih Banyak
            </a>
        </div>
    </section>

    {{-- CTA Jual Barang Sesuai Wireframe --}}
    <section class="mt-12 rounded-2xl bg-neutral-200 p-8 sm:p-12 md:p-14">
        <h2 class="text-2xl sm:text-3xl font-extrabold text-neutral-900 max-w-lg leading-tight">
            Ada barang yang sudah nggak kamu pakai?
        </h2>
        <p class="mt-3 text-sm text-neutral-700 max-w-md font-medium">
            Yuk, jual di RePake dan kasih kesempatan barangmu dipakai orang lain.
        </p>
        <a href="{{ Route::has('products.create') ? route('products.create') : '#' }}"
           @guest data-open-login @endguest
           class="mt-6 inline-flex rounded-lg bg-[#B8E639] px-5 py-2.5 text-xs font-bold text-neutral-900 transition hover:bg-lime-500">
            Jual Barang
        </a>
    </section>
</div>
@endsection