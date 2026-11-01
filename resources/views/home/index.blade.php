@extends('layouts.app')

@section('title', 'RePake - Jual Beli Barang Bekas')

@section('content')
<div class="mx-auto max-w-7xl px-4 pb-10 md:px-8 lg:px-12">

    {{-- Banner --}}
    <section aria-label="Promo" class="pt-6 md:pt-10">
        <x-banner-slider :images="$banners" />
    </section>

    {{-- Kategori --}}
    <section class="mt-10">
        <h2 class="text-h3 mb-5 font-semibold">Mau cari apa?</h2>
        <div class="no-scrollbar -mx-4 flex gap-4 overflow-x-auto px-4 md:mx-0 md:px-0 lg:grid lg:grid-cols-8">
            @foreach ($categories as $category)
                <a href="{{ route('home', ['kategori' => $category->slug]) }}" class="group w-[88px] shrink-0 lg:w-auto">
                    <div class="aspect-square overflow-hidden rounded-md bg-neutral-200 transition group-hover:bg-neutral-300">
                        @if ($category->icon_url)
                            <img src="{{ $category->icon_url }}" alt="" class="size-full object-cover" onerror="this.remove()">
                        @endif
                    </div>
                    <p class="mt-2 line-clamp-2 text-center text-label">{{ $category->name }}</p>
                </a>
            @endforeach
        </div>
    </section>

    {{-- Ada di dekat kamu --}}
    <section class="mt-10">
        <h2 class="text-h3 mb-5 font-semibold">Ada di dekat kamu</h2>
        <div class="no-scrollbar -mx-4 flex gap-3 overflow-x-auto px-4 md:mx-0 md:px-0 lg:grid lg:grid-cols-4 lg:gap-5 lg:overflow-visible">
            @foreach ($nearby as $product)
                <div class="w-[175px] shrink-0 lg:w-auto">
                    <x-product-card :product="$product" />
                </div>
            @endforeach
        </div>
    </section>

    {{-- Baru di RePake --}}
    <section class="mt-10">
        <h2 class="text-h3 mb-5 font-semibold">Baru di RePake</h2>
        <div class="grid grid-cols-2 gap-3 md:grid-cols-3 md:gap-5 lg:grid-cols-4">
            @foreach ($latest as $product)
                <x-product-card :product="$product" />
            @endforeach
        </div>

        <div class="mt-5 flex justify-center">
            {{-- TODO: pagination / load more --}}
            <a href="#" class="rounded-md border-2 border-neutral-900 px-5 py-1.5 text-label text-base font-semibold transition hover:bg-neutral-900 hover:text-neutral-50">
                Lihat Lebih Banyak
            </a>
        </div>
    </section>

    {{-- CTA jual barang --}}
    <section class="mt-10 rounded-xl bg-neutral-200 px-6 py-12 md:px-[52px] md:py-24">
        <h2 class="text-h1 max-w-[520px] md:text-[40px] md:leading-[1.15]">
            Ada barang yang sudah nggak kamu pakai?
        </h2>
        <p class="text-body-large mt-5 max-w-md font-medium">
            Yuk, jual di RePake dan kasih kesempatan barangmu dipakai orang lain.
        </p>
        <a href="{{ Route::has('products.create') ? route('products.create') : '#' }}"
           @guest data-open-login @endguest
           class="mt-6 inline-flex rounded-md bg-lime-500 px-5 py-2.5 text-label text-base font-semibold text-neutral-900 transition hover:bg-lime-600">
            Jual Barang
        </a>
    </section>
</div>
@endsection