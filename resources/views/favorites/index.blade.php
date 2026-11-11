@extends('layouts.app')

@section('title', 'Produk Favorit - RePake')

@section('content')
<div class="mx-auto max-w-6xl px-4 py-6 sm:px-6">
    {{-- Header Halaman Favorit --}}
    <div class="mb-6">
        <h1 class="text-h2 md:text-h1 font-bold tracking-tight text-neutral-900">Produk Favorit</h1>
        <p class="mt-1 text-label text-neutral-500">
            <span id="favorites-count">{{ $count }}</span> produk tersimpan
        </p>
    </div>

    {{-- Grid Produk Favorit --}}
    @if ($favorites->isEmpty())
        <div class="my-12 flex flex-col items-center justify-center rounded-2xl border border-dashed border-neutral-300 bg-neutral-50 px-6 py-16 text-center">
            <div class="grid size-16 place-items-center rounded-full bg-neutral-200 text-neutral-400">
                <svg viewBox="0 0 24 24" class="size-8 fill-none stroke-current stroke-2 [stroke-linecap:round] [stroke-linejoin:round]" aria-hidden="true">
                    <path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z"/>
                </svg>
            </div>
            <h2 class="mt-4 text-h4 font-bold text-neutral-800">Belum Ada Produk Favorit</h2>
            <p class="mt-2 max-w-sm text-body text-neutral-500">
                Kamu belum menandai produk apapun sebagai favorit. Klik ikon hati pada barang yang kamu sukai untuk melihatnya di sini.
            </p>
            <a href="{{ route('home') }}" class="mt-6 inline-flex items-center gap-2 rounded-md bg-lime-500 px-5 py-2.5 text-label font-semibold text-neutral-900 transition hover:bg-lime-600">
                Mulai Jelajah Produk
            </a>
        </div>
    @else
        <div class="grid grid-cols-2 gap-3 md:grid-cols-3 md:gap-5 lg:grid-cols-4" data-favorites-grid>
            @foreach ($favorites as $product)
                <div data-favorite-card="{{ $product->id }}">
                    <x-product-card :product="$product" />
                </div>
            @endforeach
        </div>
    @endif
</div>
@endsection
