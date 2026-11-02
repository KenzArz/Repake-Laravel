@extends('layouts.app')

@section('title', $profile['name'] . ' - Profil & Katalog')

@section('content')
<div class="mx-auto max-w-4xl px-4 py-4 md:px-8">

    {{-- Top App Bar Profil --}}
    <div class="mb-4 flex items-center justify-between border-b border-neutral-200 pb-3">
        <div class="flex items-center gap-3">
            <a href="{{ route('home') }}" class="grid size-9 place-items-center rounded-full text-neutral-800 transition hover:bg-neutral-200" aria-label="Kembali">
                <svg viewBox="0 0 24 24" class="size-6 fill-none stroke-current stroke-2 [stroke-linecap:round] [stroke-linejoin:round]">
                    <path d="M19 12H5M12 19l-7-7 7-7"/>
                </svg>
            </a>
            <h1 class="text-h4 font-bold text-neutral-900">{{ $profile['name'] }}</h1>
        </div>

        <div class="flex items-center gap-1">
            <a href="{{ route('profile.edit') }}" class="grid size-9 place-items-center rounded-full text-neutral-800 transition hover:bg-neutral-200" aria-label="Edit Profil">
                <svg viewBox="0 0 24 24" class="size-5 fill-none stroke-current stroke-2 [stroke-linecap:round] [stroke-linejoin:round]">
                    <path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/>
                </svg>
            </a>
            <button type="button" onclick="navigator.clipboard?.writeText(window.location.href); alert('Tautan profil berhasil disalin!');" class="grid size-9 place-items-center rounded-full text-neutral-800 transition hover:bg-neutral-200" aria-label="Bagikan Profil">
                <svg viewBox="0 0 24 24" class="size-5 fill-none stroke-current stroke-2 [stroke-linecap:round] [stroke-linejoin:round]">
                    <circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/>
                    <line x1="8.59" y1="13.51" x2="15.42" y2="17.49"/><line x1="15.41" y1="6.51" x2="8.59" y2="10.49"/>
                </svg>
            </button>
        </div>
    </div>

    {{-- Flash message sukses --}}
    @if (session('success'))
        <div class="mb-4 flex items-center gap-2 rounded-xl bg-lime-100 border border-lime-300 px-4 py-3 text-sm font-semibold text-lime-900">
            <svg viewBox="0 0 24 24" class="size-5 fill-current text-lime-700 shrink-0">
                <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
            </svg>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    {{-- Header Info Profil Pengguna --}}
    <div class="pt-2">
        <div class="flex items-start gap-4">
            {{-- Avatar dengan Online Dot --}}
            <div class="relative size-20 shrink-0">
                <div class="size-20 overflow-hidden rounded-full bg-neutral-200 border-2 border-white shadow-sm grid place-items-center">
                    @if (!empty($profile['avatar']))
                        <img src="{{ asset($profile['avatar']) }}" alt="{{ $profile['name'] }}" class="size-full object-cover">
                    @else
                        <svg viewBox="0 0 24 24" class="size-12 fill-neutral-400 text-neutral-400">
                            <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
                        </svg>
                    @endif
                </div>
                <span class="absolute bottom-0.5 right-0.5 size-4 rounded-full bg-emerald-500 border-2 border-white" title="Online"></span>
            </div>

            {{-- Detail Nama, Lokasi, Rating --}}
            <div class="min-w-0 flex-1 leading-normal">
                <div class="flex items-center gap-1.5 flex-wrap">
                    <h2 class="text-h3 font-bold text-neutral-900">{{ $profile['name'] }}</h2>
                    @if (!empty($profile['is_verified']))
                        <svg viewBox="0 0 24 24" class="size-5 shrink-0 fill-emerald-600 text-white" aria-label="Terverifikasi">
                            <circle cx="12" cy="12" r="10" class="fill-emerald-600"/>
                            <path d="m9 12 2 2 4-4" class="fill-none stroke-white stroke-2 [stroke-linecap:round] [stroke-linejoin:round]"/>
                        </svg>
                    @endif
                </div>

                <p class="mt-0.5 flex items-center gap-1 text-xs text-neutral-600">
                    <svg viewBox="0 0 24 24" class="size-3.5 fill-none stroke-red-500 stroke-2" aria-hidden="true">
                        <path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/>
                    </svg>
                    <span>{{ $profile['kota'] }}</span>
                    <span>•</span>
                    <span class="text-neutral-500">{{ $profile['online_status'] }}</span>
                </p>

                <p class="mt-1 flex items-center gap-1 text-xs font-semibold text-neutral-900">
                    <span class="text-amber-500 text-sm">★</span>
                    <span>{{ number_format($profile['rating'], 1) }}</span>
                    <span class="font-normal text-neutral-500">({{ $profile['reviews_count'] }} ulasan)</span>
                </p>
            </div>
        </div>

        {{-- Box Bio / Deskripsi Toko --}}
        @if (!empty($profile['bio']))
            <div class="mt-4 rounded-xl border border-neutral-200 bg-neutral-100/80 px-4 py-3 text-sm leading-relaxed text-neutral-700">
                {{ $profile['bio'] }}
            </div>
        @endif

        {{-- Tombol Aksi Profil --}}
        <div class="mt-4 grid grid-cols-2 gap-3">
            <a href="{{ route('profile.edit') }}" class="flex items-center justify-center gap-2 rounded-xl bg-lime-500 py-2.5 text-label font-bold text-neutral-900 shadow-sm transition hover:bg-lime-600">
                <svg viewBox="0 0 24 24" class="size-4 stroke-current stroke-2 fill-none">
                    <path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/>
                </svg>
                <span>Edit Profil</span>
            </a>

            <form action="{{ route('logout') }}" method="POST" class="w-full">
                @csrf
                <button type="submit" class="flex w-full items-center justify-center gap-2 rounded-xl border border-red-500 bg-white py-2.5 text-label font-bold text-red-600 shadow-sm transition hover:bg-red-50">
                    <svg viewBox="0 0 24 24" class="size-4 stroke-current stroke-2 fill-none">
                        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9"/>
                    </svg>
                    <span>Keluar</span>
                </button>
            </form>
        </div>
    </div>

    {{-- Tabs Katalog (Dijual / Favorit) --}}
    <div class="mt-8 border-t border-neutral-200 pt-6">
        <div class="flex items-center justify-between border-b border-neutral-200 pb-2">
            {{-- Tabs --}}
            <div class="flex items-center gap-4">
                <a href="{{ route('profile.catalog', ['tab' => 'dijual']) }}"
                   class="pb-2 text-label font-bold transition {{ $tab !== 'favorit' ? 'border-b-2 border-neutral-900 text-neutral-900' : 'text-neutral-500 hover:text-neutral-800' }}">
                    Semua Produk ({{ $soldCount }})
                </a>
                <a href="{{ route('profile.catalog', ['tab' => 'favorit']) }}"
                   class="pb-2 text-label font-bold transition {{ $tab === 'favorit' ? 'border-b-2 border-neutral-900 text-neutral-900' : 'text-neutral-500 hover:text-neutral-800' }}">
                    Favorit ({{ $favoriteCount }})
                </a>
            </div>

            {{-- Filter Urutkan --}}
            <span class="text-caption text-neutral-500">Urutkan: Terbaru</span>
        </div>

        {{-- Grid Produk --}}
        @if ($products->isEmpty())
            <div class="my-10 flex flex-col items-center justify-center rounded-2xl border border-dashed border-neutral-300 bg-neutral-50 px-6 py-12 text-center">
                <p class="text-body text-neutral-500">
                    {{ $tab === 'favorit' ? 'Belum ada produk favorit tersimpan.' : 'Belum ada produk yang dijual saat ini.' }}
                </p>
                <a href="{{ route('home') }}" class="mt-4 inline-flex rounded-md bg-lime-500 px-4 py-2 text-label font-semibold text-neutral-900 transition hover:bg-lime-600">
                    Jelajah Produk
                </a>
            </div>
        @else
            <div class="mt-4 grid grid-cols-2 gap-3 md:grid-cols-3 md:gap-5 lg:grid-cols-4">
                @foreach ($products as $product)
                    <x-product-card :product="$product" />
                @endforeach
            </div>
        @endif
    </div>

</div>
@endsection
