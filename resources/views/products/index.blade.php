@extends('layouts.app')

@section('title', 'Semua Produk - RePake')

@section('content')
<div class="mx-auto max-w-6xl px-4 py-6 sm:px-6">
    <div class="mb-6">
        <h1 class="text-xl sm:text-2xl font-extrabold tracking-tight text-neutral-900">Semua Produk</h1>
        <p class="mt-1 text-xs text-neutral-500">
            @if ($q !== '' || $kategori !== '' || $kota !== '')
                {{ $products->total() }} hasil ditemukan
            @else
                Jelajahi barang bekas berkualitas di sekitarmu
            @endif
        </p>
    </div>

    {{-- Filter & Pencarian --}}
    <form method="GET" action="{{ route('products.index') }}" role="search"
          class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center">
        <div class="relative flex-1">
            <svg viewBox="0 0 24 24" class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 fill-none stroke-neutral-700 stroke-2" aria-hidden="true">
                <circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/>
            </svg>
            <input type="search" name="q" value="{{ $q }}" placeholder="Cari judul atau deskripsi..."
                   class="h-10 w-full rounded-lg border border-neutral-300 bg-white pl-9 pr-3 text-xs text-neutral-900 outline-none focus:border-neutral-900">
        </div>
        <select name="kategori" class="h-10 rounded-lg border border-neutral-300 bg-white px-3 text-xs text-neutral-900 outline-none focus:border-neutral-900">
            <option value="">Semua Kategori</option>
            @foreach ($categories as $category)
                <option value="{{ $category->slug }}" {{ $kategori === $category->slug ? 'selected' : '' }}>{{ $category->name }}</option>
            @endforeach
        </select>
        <input type="text" name="kota" value="{{ $kota }}" placeholder="Kota / area..."
               class="h-10 w-full sm:w-44 rounded-lg border border-neutral-300 bg-white px-3 text-xs text-neutral-900 outline-none focus:border-neutral-900">
        <button type="submit"
                class="rounded-lg bg-lime-400 px-5 py-2.5 text-xs font-bold text-neutral-900 transition hover:bg-lime-500">
            Cari
        </button>
    </form>

    {{-- Hasil --}}
    @if ($products->isEmpty())
        <div class="my-16 rounded-2xl border border-dashed border-neutral-300 bg-neutral-50 px-6 py-14 text-center text-xs text-neutral-500">
            Tidak ada produk yang cocok. Coba kata kunci atau filter lain.
        </div>
    @else
        <div class="grid grid-cols-2 gap-3 md:grid-cols-3 md:gap-5 lg:grid-cols-4">
            @foreach ($products as $product)
                <x-product-card :product="$product" />
            @endforeach
        </div>
        <div class="mt-8">
            {{ $products->links() }}
        </div>
    @endif
</div>
@endsection