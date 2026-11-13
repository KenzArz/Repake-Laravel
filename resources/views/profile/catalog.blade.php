@extends('layouts.app')

@section('title', ($profile['name'] ?? 'Midou Wijaya') . ' - Detail User | RePake')
@section('canonical', $profileUrl ?? route('profile.catalog'))

@section('content')
@php
    // Profil sendiri dan profil publik penjual lain memakai markup yang sama.
    // $isOwner membedakan aksi (edit profil vs chat penjual) dan tab favorit.
    $isOwner = $isOwner ?? true;
    $chatProducts = $chatProducts ?? collect();
    $profileUrl = $profileUrl ?? route('profile.catalog');
    // $profileUrl sudah membawa `?id={uuid}` pada profil penjual publik,
    // jadi pemisah query harus dipilih agar tidak menjadi `?id=...?tab=`.
    $tabUrl = fn (string $value): string => $profileUrl
        .(str_contains($profileUrl, '?') ? '&' : '?')
        .http_build_query(['tab' => $value]);
@endphp

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
    <div class="rounded-2xl border border-neutral-200 bg-white p-6 sm:p-8 shadow-2xs">
        <div class="flex flex-col gap-6 md:flex-row md:items-center md:justify-between">

            {{-- Kiri: Avatar Siluet Default / Foto Profil + Info User --}}
            <div class="flex items-start sm:items-center gap-5">
                {{-- Lingkaran Avatar Siluet Default Sesuai Permintaan User --}}
                <div class="size-24 shrink-0 rounded-full bg-neutral-200 overflow-hidden border border-neutral-300 grid place-items-center select-none shadow-inner">
                    @if (!empty($profile['avatar_url']))
                        <img src="{{ $profile['avatar_url'] }}" alt="{{ $profile['name'] }}" class="size-full object-cover">
                    @else
                        <img src="{{ asset('images/default-avatar.png') }}" alt="{{ $profile['name'] ?? 'Profil' }}" class="size-full object-cover">
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
                        <a href="{{ $tabUrl('ulasan') }}" class="group block">
                            <p class="flex items-center gap-1 text-xs font-bold text-neutral-900 group-hover:text-lime-700 transition">
                                <span class="text-amber-500">★</span>
                                <span>{{ number_format($profile['rating'] ?? 4.5, 1) }} ({{ $profile['reviews_count'] ?? 67 }} ulasan) · {{ $profile['sold_count'] ?? 10 }} Terjual</span>
                            </p>
                            <p class="text-[11px] text-neutral-500 group-hover:underline">Rating &amp; Ulasan</p>
                        </a>
                    </div>

                    {{-- 3 Vertical Dots (Menu Opsi / Edit Profil) — hanya untuk pemilik profil --}}
                    @if ($isOwner)
                        <div class="relative">
                            <button type="button" id="profile-options-toggle"
                                    class="grid size-8 place-items-center rounded-full text-neutral-700 transition hover:bg-neutral-100 cursor-pointer" aria-label="Menu Opsi">
                                <svg viewBox="0 0 24 24" class="size-4.5 fill-current">
                                    <circle cx="12" cy="5" r="1.6"/><circle cx="12" cy="12" r="1.6"/><circle cx="12" cy="19" r="1.6"/>
                                </svg>
                            </button>
                            <div id="profile-options-menu" class="absolute right-0 top-9 z-20 hidden w-44 rounded-xl border border-neutral-200 bg-white py-1.5 shadow-xl ring-1 ring-black/5">
                                <a href="{{ route('profile.edit') }}" class="flex items-center gap-2 px-3 py-2 text-xs font-semibold text-neutral-700 hover:bg-neutral-100 transition">
                                    <svg viewBox="0 0 24 24" class="size-4 stroke-current stroke-2 fill-none"><path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/></svg>
                                    <span>Edit Profil</span>
                                </a>
                                <form action="{{ route('logout') }}" method="POST">
                                    @csrf
                                    <button type="submit" class="flex w-full items-center gap-2 px-3 py-2 text-xs font-semibold text-red-600 hover:bg-red-50 transition cursor-pointer">
                                        <svg viewBox="0 0 24 24" class="size-4 stroke-current stroke-2 fill-none"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9"/></svg>
                                        <span>Keluar</span>
                                    </button>
                                </form>
                            </div>
                        </div>
                    @endif
                </div>

                {{-- Tombol Aksi: pemilik bisa edit profil, pengunjung hanya bisa chat penjual --}}
                <div class="flex items-center gap-2 w-full sm:w-auto">
                    @if ($isOwner)
                        <a href="{{ route('profile.edit') }}"
                           class="flex items-center justify-center gap-1.5 rounded-lg border border-neutral-300 bg-white px-4 py-2.5 text-xs font-bold text-neutral-800 transition hover:bg-neutral-100 shadow-2xs w-full sm:w-auto">
                            <svg viewBox="0 0 24 24" class="size-3.5 stroke-current stroke-2 fill-none"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
                            <span>Edit Profil</span>
                        </a>
                        <a href="{{ Route::has('chat.index') ? route('chat.index') : '#' }}"
                           class="flex items-center justify-center gap-2 rounded-lg bg-lime-400 px-6 py-2.5 text-xs font-bold text-neutral-900 transition hover:bg-lime-500 shadow-2xs w-full sm:w-auto">
                            <svg viewBox="0 0 24 24" class="size-4 fill-none stroke-current stroke-2">
                                <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
                            </svg>
                            <span>Chat Penjual</span>
                        </a>
                    @else
                        <button type="button" id="seller-chat-open"
                                @guest data-open-login @endguest
                                @if (auth()->check() && $chatProducts->isEmpty()) disabled title="Penjual belum punya produk aktif" @endif
                                class="flex items-center justify-center gap-2 rounded-lg bg-lime-400 px-6 py-2.5 text-xs font-bold text-neutral-900 transition hover:bg-lime-500 shadow-2xs w-full sm:w-auto disabled:cursor-not-allowed disabled:bg-neutral-200 disabled:text-neutral-500 disabled:hover:bg-neutral-200">
                            <svg viewBox="0 0 24 24" class="size-4 fill-none stroke-current stroke-2">
                                <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
                            </svg>
                            <span>Chat Penjual</span>
                        </button>
                    @endif
                </div>
            </div>

        </div>
    </div>

    {{-- Tabs: Produk & Ulasan (profil sendiri menambah tab Favorit) --}}
    <div class="mt-8 border-b border-neutral-200">
        <div class="flex items-center gap-6">
            <a href="{{ $tabUrl('dijual') }}"
               class="pb-2.5 text-xs font-bold transition {{ $tab === 'dijual' || empty($tab) ? 'border-b-2 border-neutral-900 text-neutral-900' : 'text-neutral-500 hover:text-neutral-800' }}">
                Produk
            </a>

            @if ($isOwner)
                <a href="{{ $tabUrl('favorit') }}"
                   class="pb-2.5 text-xs font-bold transition {{ $tab === 'favorit' ? 'border-b-2 border-neutral-900 text-neutral-900' : 'text-neutral-500 hover:text-neutral-800' }}">
                    Favorit ({{ $favoriteCount }})
                </a>
            @endif

            <a href="{{ $tabUrl('ulasan') }}"
               class="pb-2.5 text-xs font-bold transition {{ $tab === 'ulasan' ? 'border-b-2 border-[#8EAE36] text-neutral-900' : 'text-neutral-500 hover:text-neutral-800' }}">
                Ulasan ({{ $reviewCount }})
            </a>
        </div>
    </div>

    {{-- KONTEN TAB --}}
    @if ($tab === 'ulasan')
        {{-- SECTION: SEMUA ULASAN --}}
        <section class="mt-6 scroll-mt-24" id="ulasan" data-profile-reviews>
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-xl font-bold tracking-tight text-neutral-900">Semua Ulasan</h2>

                <div class="flex items-center gap-1.5 rounded-full border border-neutral-300 bg-white px-4 py-1.5 text-xs font-semibold text-neutral-800 shadow-2xs">
                    <span>Urutkan: <strong>Terbaru</strong></span>
                    <svg viewBox="0 0 24 24" class="size-3.5 fill-none stroke-current stroke-2"><path d="m6 9 6 6 6-6"/></svg>
                </div>
            </div>

            {{-- Form ulasan penjual -> pembeli (langsung di profil, tanpa produk) --}}
            @if (! empty($reviewableOrder))
                <form method="POST" action="{{ route('reviews.store', $reviewableOrder) }}"
                      class="mb-4 rounded-2xl border border-lime-200 bg-lime-50 p-5 shadow-2xs"
                      data-review-form>
                    @csrf
                    <p class="text-sm font-bold text-neutral-900">Beri ulasan untuk {{ $seller->name }}</p>

                    <div class="mt-3" data-rating-input>
                        <input type="hidden" name="rating" value="" required>
                        <div class="flex items-center gap-1">
                            @for ($i = 1; $i <= 5; $i++)
                                <button type="button" data-rating-star="{{ $i }}"
                                        aria-label="{{ $i }} bintang"
                                        class="text-neutral-300 transition hover:scale-110 cursor-pointer">
                                    <svg viewBox="0 0 24 24" class="size-8 fill-current" aria-hidden="true">
                                        <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                                    </svg>
                                </button>
                            @endfor
                        </div>
                        @error('rating')
                            <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <textarea name="komentar" rows="3" maxlength="500"
                              placeholder="Ceritakan pengalaman transaksi Anda (opsional)..."
                              class="mt-3 w-full rounded-lg border border-neutral-300 bg-white p-2.5 text-xs font-medium leading-relaxed text-neutral-900 focus:border-neutral-900 focus:outline-none"></textarea>

                    <div class="mt-3 flex justify-end">
                        <button type="submit"
                                class="rounded-lg bg-lime-500 px-4 py-2 text-xs font-bold text-neutral-900 transition hover:bg-lime-600">
                            Kirim Ulasan
                        </button>
                    </div>
                </form>
            @endif

            {{-- Daftar Kartu Ulasan --}}
            <div class="space-y-4">
                @forelse ($reviews as $review)
                    <article class="rounded-2xl border border-neutral-200 bg-white p-5 sm:p-6 shadow-2xs transition hover:shadow-xs" data-review-card>
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2.5">
                                <div class="size-8 rounded-full overflow-hidden bg-neutral-200 border border-neutral-300 shrink-0">
                                    <img src="{{ asset('images/default-avatar.png') }}" alt="{{ $review['reviewer_name'] }}" class="size-full object-cover">
                                </div>
                                <div class="flex items-baseline gap-2">
                                    <h3 class="text-xs sm:text-sm font-bold text-neutral-900">{{ $review['reviewer_name'] }}</h3>
                                    <span class="text-xs text-neutral-400 font-normal">{{ $review['time_ago'] }}</span>
                                </div>
                            </div>

                            @if (! empty($review['is_mine']))
                                <div class="relative shrink-0" data-review-menu-root>
                                    <button type="button" data-review-menu-toggle aria-label="Opsi ulasan" aria-expanded="false"
                                            class="grid size-7 place-items-center rounded-full text-neutral-400 hover:text-neutral-700 hover:bg-neutral-100 transition cursor-pointer">
                                        <svg viewBox="0 0 24 24" class="size-4 fill-current">
                                            <circle cx="12" cy="5" r="1.5"/><circle cx="12" cy="12" r="1.5"/><circle cx="12" cy="19" r="1.5"/>
                                        </svg>
                                    </button>
                                    <div data-review-menu class="absolute right-0 top-full z-30 mt-1 hidden w-40 overflow-hidden rounded-xl border border-neutral-200 bg-white py-1 shadow-xl">
                                        <button type="button" data-edit-review-toggle
                                                class="flex w-full items-center gap-2 px-4 py-2 text-left text-xs font-medium text-neutral-700 transition hover:bg-neutral-100">
                                            Edit ulasan
                                        </button>
                                        <form method="POST" action="{{ route('reviews.destroy', $review['id']) }}"
                                              onsubmit="return confirm('Hapus ulasan Anda?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                    class="flex w-full items-center gap-2 px-4 py-2 text-left text-xs font-medium text-red-600 transition hover:bg-red-50">
                                                Hapus ulasan
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            @endif
                        </div>

                        <div class="flex items-center gap-1 mt-2.5 mb-1.5">
                            @for ($i = 1; $i <= 5; $i++)
                                <svg viewBox="0 0 24 24"
                                     class="{{ $i <= $review['rating'] ? 'fill-amber-400 text-amber-400' : 'fill-neutral-200 text-neutral-200' }} size-4"
                                     aria-hidden="true">
                                    <path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/>
                                </svg>
                            @endfor
                        </div>

                        <p class="text-xs sm:text-sm text-neutral-800 leading-relaxed font-normal">
                            {{ $review['comment'] }}
                        </p>

                        @if (! empty($review['has_product']))
                            <div class="mt-3.5 flex items-center gap-3.5 rounded-2xl bg-[#EAF5D3] p-3 border border-lime-200/60">
                                <div class="size-14 shrink-0 overflow-hidden rounded-xl bg-white shadow-2xs">
                                    <img src="{{ asset($review['product_image']) }}" alt="{{ $review['product_title'] }}"
                                         class="size-full object-cover"
                                         onerror="this.onerror=null; this.src='https://images.unsplash.com/photo-1590658268037-6bf12165a8df?w=200&auto=format&fit=crop&q=80';">
                                </div>
                                <div class="min-w-0 flex-1 leading-snug">
                                    <p class="truncate text-xs sm:text-sm font-bold text-neutral-900">{{ $review['product_title'] }}</p>
                                    <p class="mt-0.5 text-xs sm:text-sm font-extrabold text-neutral-900">{{ $review['product_price'] }}</p>
                                </div>
                            </div>
                        @endif

                        @if (! empty($review['is_mine']))
                            <form method="POST" action="{{ route('reviews.update', $review['id']) }}"
                                  hidden data-edit-review-form
                                  class="mt-4 rounded-xl border border-lime-200 bg-lime-50 p-4">
                                @csrf
                                @method('PATCH')
                                <p class="text-xs font-bold text-neutral-900">Perbarui ulasan</p>

                                <div class="mt-2" data-rating-input>
                                    <input type="hidden" name="rating" value="{{ $review['rating'] }}" required>
                                    <div class="flex items-center gap-1">
                                        @for ($i = 1; $i <= 5; $i++)
                                            <button type="button" data-rating-star="{{ $i }}"
                                                    aria-label="{{ $i }} bintang"
                                                    class="text-neutral-300 transition hover:scale-110 cursor-pointer">
                                                <svg viewBox="0 0 24 24" class="size-7 fill-current" aria-hidden="true">
                                                    <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                                                </svg>
                                            </button>
                                        @endfor
                                    </div>
                                </div>

                                <textarea name="komentar" rows="3" maxlength="500"
                                          placeholder="Perbarui pengalaman Anda... (opsional)"
                                          class="mt-2 w-full rounded-lg border border-neutral-300 bg-white p-2.5 text-xs font-medium leading-relaxed text-neutral-900 focus:border-neutral-900 focus:outline-none">{{ $review['comment'] }}</textarea>

                                <div class="mt-3 flex items-center justify-end gap-2">
                                    <button type="button" data-edit-review-cancel
                                            class="rounded-lg border border-neutral-300 px-3.5 py-2 text-xs font-semibold text-neutral-700 transition hover:bg-neutral-100">
                                        Batal
                                    </button>
                                    <button type="submit"
                                            class="rounded-lg bg-lime-500 px-4 py-2 text-xs font-bold text-neutral-900 transition hover:bg-lime-600">
                                        Simpan
                                    </button>
                                </div>
                            </form>
                        @endif
                    </article>
                @empty
                    <div class="rounded-2xl border border-neutral-200 bg-white p-8 text-center text-xs text-neutral-500">
                        Belum ada ulasan.
                    </div>
                @endforelse
            </div>
        </section>
    @else
        {{-- Section: Semua Produk (Dijual / Favorit) --}}
        <section class="mt-6">
            <div class="flex items-center justify-between">
                <h2 class="text-base font-extrabold text-neutral-900">
                    {{ $tab === 'favorit' ? 'Barang Favorit' : 'Semua Produk' }}
                </h2>

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
    @endif

    {{-- Modal pilih produk sebelum memulai chat dengan penjual --}}
    @unless ($isOwner)
        <div id="seller-chat-modal"
             class="fixed inset-0 z-50 hidden overflow-y-auto bg-black/60 p-4 backdrop-blur-sm"
             role="dialog" aria-modal="true" aria-labelledby="seller-chat-title">
            <div class="mx-auto mt-[10vh] flex w-full max-w-md flex-col overflow-hidden rounded-2xl bg-white shadow-xl">
                <div class="flex items-center justify-between gap-3 border-b border-neutral-200 p-4">
                    <div class="min-w-0">
                        <h2 id="seller-chat-title" class="truncate text-sm font-bold text-neutral-900">
                            Chat dengan {{ $profile['name'] }}
                        </h2>
                        <p class="mt-0.5 text-[11px] text-neutral-500">Pilih produk yang ingin ditanyakan.</p>
                    </div>
                    <button type="button" id="seller-chat-close"
                            class="grid size-8 shrink-0 place-items-center rounded-full text-neutral-500 transition hover:bg-neutral-100 hover:text-neutral-900"
                            aria-label="Tutup pilihan produk">
                        <svg viewBox="0 0 24 24" class="size-4 fill-none stroke-current stroke-2"><path d="M18 6 6 18M6 6l12 12" stroke-linecap="round"/></svg>
                    </button>
                </div>

                <div class="max-h-[55vh] overflow-y-auto p-2">
                    @forelse ($chatProducts as $chatProduct)
                        <form method="POST" action="{{ route('chat.store') }}">
                            @csrf
                            <input type="hidden" name="product_id" value="{{ $chatProduct->id }}">
                            <button type="submit"
                                    class="flex w-full items-center gap-3 rounded-xl px-2 py-2 text-left transition hover:bg-neutral-100 cursor-pointer">
                                <div class="size-12 shrink-0 overflow-hidden rounded-lg bg-neutral-200">
                                    @if ($chatProduct->gambar)
                                        <img src="{{ asset($chatProduct->gambar) }}" alt="" class="size-full object-cover">
                                    @endif
                                </div>
                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-xs font-bold text-neutral-900">{{ $chatProduct->judul }}</p>
                                    <p class="mt-0.5 text-xs font-extrabold text-neutral-900">Rp {{ number_format($chatProduct->harga, 0, ',', '.') }}</p>
                                </div>
                                <svg viewBox="0 0 24 24" class="size-4 shrink-0 fill-none stroke-current stroke-2 text-neutral-400"><path d="m9 18 6-6-6-6" stroke-linecap="round"/></svg>
                            </button>
                        </form>
                    @empty
                        <p class="px-3 py-6 text-center text-xs text-neutral-500">Penjual belum punya produk aktif.</p>
                    @endforelse
                </div>
            </div>
        </div>
    @endunless

</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const toggle = document.getElementById('profile-options-toggle');
    const menu = document.getElementById('profile-options-menu');

    if (toggle && menu) {
        toggle.addEventListener('click', function (e) {
            e.stopPropagation();
            menu.classList.toggle('hidden');
        });

        document.addEventListener('click', function (e) {
            if (!menu.contains(e.target) && !toggle.contains(e.target)) {
                menu.classList.add('hidden');
            }
        });
    }

    // Modal "Chat Penjual" di profil publik: pilih produk dulu, baru masuk chat.
    const chatOpen = document.getElementById('seller-chat-open');
    const chatModal = document.getElementById('seller-chat-modal');
    const chatClose = document.getElementById('seller-chat-close');

    if (chatOpen && chatModal) {
        chatOpen.addEventListener('click', function () {
            // Tamu diarahkan ke modal login oleh listener [data-open-login].
            if (chatOpen.hasAttribute('data-open-login')) return;

            chatModal.classList.remove('hidden');
            chatClose?.focus();
        });

        chatClose?.addEventListener('click', function () {
            chatModal.classList.add('hidden');
            chatOpen.focus();
        });

        chatModal.addEventListener('click', function (e) {
            if (e.target === chatModal) {
                chatModal.classList.add('hidden');
            }
        });

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && !chatModal.classList.contains('hidden')) {
                chatModal.classList.add('hidden');
                chatOpen.focus();
            }
        });
    }
});
</script>
@endpush
