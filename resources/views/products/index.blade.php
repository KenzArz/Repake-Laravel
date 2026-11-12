@extends('layouts.app')

@section('title', ($q !== '' ? "{$products->total()} Pencarian untuk '{$q}'" : 'Semua Produk') . ' - RePake')

@section('content')
    <div class="mx-auto max-w-6xl px-4 py-6 pb-24 md:pb-12 sm:px-6 space-y-5">

        {{-- Header Judul Pencarian (Persis Wireframe: "67 Pencarian untuk 'keyboard'") --}}
        <div>
            <h1 class="text-xl sm:text-2xl font-extrabold tracking-tight text-neutral-900">
                <span id="result-count">{{ $products->total() }}</span> Pencarian
                @if ($q !== '')
                    <span>untuk '{{ $q }}'</span>
                @endif
            </h1>
        </div>

        {{-- Baris Chip Kapsul (Kategori, Urutkan | Kondisi, Harga) + Active Tags --}}
        <x-product-filter-bar :categories="$categories" :kondisiOptions="$kondisiOptions" :activeKota="$activeKota"
            :defaultSort="$defaultSort" />

        {{-- Grid Hasil Pencarian 4 Kolom --}}
        <div id="search-results" class="pt-1 transition-opacity duration-150">
            @include('products.partials.results')
        </div>

    </div>
@endsection