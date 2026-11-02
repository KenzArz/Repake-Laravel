@extends('layouts.app')

@section('title', $product->judul . ' | RePake')

@section('content')
<div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">

    {{-- Breadcrumb Navigation (Desktop) --}}
    <nav class="mb-6 hidden items-center gap-2 text-xs text-neutral-500 md:flex" aria-label="Breadcrumb">
        <a href="{{ route('home') }}" class="hover:text-neutral-900 transition">Beranda</a>
        <span>/</span>
        <a href="{{ route('home', ['kategori' => strtolower($product->kategori ?? 'umum')]) }}" class="hover:text-neutral-900 transition">{{ $product->kategori ?? 'Kategori' }}</a>
        <span>/</span>
        <span class="text-neutral-900 font-semibold truncate max-w-md">{{ $product->judul }}</span>
    </nav>

    {{-- Flash message laporan sukses --}}
    @if (session('report_success'))
        <div class="mb-6 flex items-center justify-between rounded-2xl bg-amber-50 border border-amber-300 p-4 text-sm font-semibold text-amber-900 shadow-sm">
            <div class="flex items-center gap-3">
                <div class="grid size-8 place-items-center rounded-full bg-amber-500 text-white shrink-0">
                    <svg viewBox="0 0 24 24" class="size-5 fill-current"><circle cx="12" cy="12" r="10"/><path d="m9 12 2 2 4-4" class="fill-none stroke-white stroke-2"/></svg>
                </div>
                <span>{{ session('report_success') }}</span>
            </div>
            <button type="button" onclick="this.parentElement.remove()" class="text-amber-700 hover:text-amber-900 text-sm">✕</button>
        </div>
    @endif

    {{-- Grid Layout Desktop Utama (Kiri: Galeri & Deskripsi | Kanan: Sticky Pricing & CTA) --}}
    <div class="grid grid-cols-1 gap-8 lg:grid-cols-12">

        {{-- Kolom Kiri: Galeri Foto & Deskripsi Lengkap (Col-span 7) --}}
        <div class="lg:col-span-7 space-y-6">

            {{-- Container Foto Utama --}}
            <div class="relative aspect-4/3 sm:aspect-16/10 overflow-hidden rounded-3xl border border-neutral-200 bg-neutral-100 shadow-sm group">
                @if ($product->gambar)
                    <img src="{{ asset($product->gambar) }}" alt="{{ $product->judul }}"
                         class="size-full object-cover transition-transform duration-500 group-hover:scale-105">
                @endif

                {{-- Badge Status "Dijual" --}}
                <span class="absolute left-4 top-4 rounded-xl bg-lime-500 px-3.5 py-1 text-xs font-extrabold text-neutral-900 shadow-md">
                    {{ $product->status }}
                </span>

                {{-- Tombol Favorit Floating --}}
                <button type="button"
                        aria-label="Favorit" aria-pressed="{{ !empty($product->is_favorite) ? 'true' : 'false' }}"
                        data-favorite-toggle data-url="{{ route('favorites.toggle', $product->id) }}"
                        class="absolute right-4 top-4 z-10 grid size-11 place-items-center rounded-full bg-neutral-900/60 text-white backdrop-blur-md transition hover:bg-neutral-900/80 hover:scale-105 aria-pressed:text-lime-500 aria-pressed:[&>svg]:fill-lime-500 aria-pressed:[&>svg]:stroke-lime-500 shadow-md">
                    <svg viewBox="0 0 24 24" class="size-5.5 fill-none stroke-current stroke-2 [stroke-linecap:round] [stroke-linejoin:round]">
                        <path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z"/>
                    </svg>
                </button>
            </div>

            {{-- Kartu Deskripsi & Spesifikasi Produk (Desktop Card) --}}
            <div class="rounded-3xl border border-neutral-200 bg-white p-6 sm:p-8 shadow-sm space-y-6">
                <div>
                    <h3 class="text-lg font-bold text-neutral-900 border-b border-neutral-100 pb-3">Detail Produk & Deskripsi</h3>
                    <div class="mt-4 text-sm leading-relaxed text-neutral-700 whitespace-pre-line font-normal">
                        {{ $product->deskripsi ?? 'Tidak ada deskripsi rinci untuk produk ini.' }}
                    </div>
                </div>

                {{-- Tabel Spesifikasi Singkat --}}
                <div class="border-t border-neutral-100 pt-6">
                    <h4 class="text-xs font-bold uppercase tracking-wider text-neutral-500 mb-3">Spesifikasi Detail</h4>
                    <div class="grid grid-cols-2 gap-3 text-xs sm:grid-cols-3">
                        <div class="rounded-xl bg-neutral-50 p-3 border border-neutral-200/80">
                            <span class="text-neutral-500 block">Kondisi</span>
                            <span class="font-bold text-neutral-900 mt-0.5 block">{{ $product->kondisi }}</span>
                        </div>
                        <div class="rounded-xl bg-neutral-50 p-3 border border-neutral-200/80">
                            <span class="text-neutral-500 block">Kategori</span>
                            <span class="font-bold text-neutral-900 mt-0.5 block">{{ $product->kategori ?? 'Umum' }}</span>
                        </div>
                        <div class="rounded-xl bg-neutral-50 p-3 border border-neutral-200/80">
                            <span class="text-neutral-500 block">Lokasi Barang</span>
                            <span class="font-bold text-neutral-900 mt-0.5 block">{{ $product->kota }}</span>
                        </div>
                    </div>
                </div>

                {{-- Tips Transaksi Aman --}}
                <div class="rounded-2xl bg-lime-50/70 border border-lime-200/80 p-4 text-xs text-lime-950 flex items-start gap-3">
                    <span class="text-xl">🛡️</span>
                    <div>
                        <span class="font-bold block text-sm text-lime-900">Tips Transaksi Aman di RePake:</span>
                        <p class="mt-1 leading-relaxed text-lime-800">
                            Utamakan bertemu langsung (COD) di tempat umum kampus atau area yang ramai. Periksa kondisi barang secara teliti sebelum menyelesaikan pembayaran.
                        </p>
                    </div>
                </div>
            </div>

        </div>

        {{-- Kolom Kanan: Sticky Pricing, Seller Card & CTA Actions (Col-span 5) --}}
        <div class="lg:col-span-5">
            <div class="sticky top-24 space-y-5">

                {{-- Card Utama: Judul & Harga --}}
                <div class="rounded-3xl border border-neutral-200 bg-white p-6 sm:p-7 shadow-sm">
                    <div class="flex items-center gap-2 mb-2">
                        <span class="rounded-lg bg-lime-100 px-2.5 py-0.5 text-xs font-bold text-lime-800">
                            {{ $product->kondisi }}
                        </span>
                        <span class="text-xs text-neutral-500">
                            📍 {{ $product->kota }}
                        </span>
                    </div>

                    <h1 class="text-xl sm:text-2xl font-extrabold leading-snug text-neutral-900">
                        {{ $product->judul }}
                    </h1>

                    <div class="mt-4 border-y border-neutral-100 py-4">
                        <span class="text-xs text-neutral-500 block">Harga Barang</span>
                        <p class="text-3xl sm:text-4xl font-extrabold text-neutral-900 mt-0.5 tracking-tight">
                            Rp {{ number_format($product->harga, 0, ',', '.') }}
                        </p>
                    </div>

                    {{-- Tombol Aksi Utama --}}
                    <div class="mt-6 space-y-3">
                        <a href="{{ Route::has('chat.index') ? route('chat.index', ['seller' => $product->penjual]) : '#' }}"
                           class="flex w-full items-center justify-center gap-2.5 rounded-xl bg-lime-500 py-3.5 text-sm font-bold text-neutral-900 shadow transition hover:bg-lime-600 hover:shadow-md">
                            <svg viewBox="0 0 24 24" class="size-5 fill-none stroke-current stroke-2 [stroke-linecap:round] [stroke-linejoin:round]">
                                <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
                            </svg>
                            <span>Chat Penjual</span>
                        </a>

                        <div class="grid grid-cols-2 gap-2">
                            {{-- Favorit Button --}}
                            <button type="button"
                                    aria-label="Favorit" aria-pressed="{{ !empty($product->is_favorite) ? 'true' : 'false' }}"
                                    data-favorite-toggle data-url="{{ route('favorites.toggle', $product->id) }}"
                                    class="flex items-center justify-center gap-2 rounded-xl border border-neutral-300 bg-white py-2.5 text-xs font-bold text-neutral-800 transition hover:bg-neutral-50 aria-pressed:border-lime-500 aria-pressed:bg-lime-50 aria-pressed:text-lime-700">
                                <svg viewBox="0 0 24 24" class="size-4.5 fill-none stroke-current stroke-2 [stroke-linecap:round] [stroke-linejoin:round]">
                                    <path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z"/>
                                </svg>
                                <span>Simpan Favorit</span>
                            </button>

                            {{-- Bagikan Button --}}
                            <button type="button" onclick="navigator.clipboard?.writeText(window.location.href); alert('Tautan produk berhasil disalin!');"
                                    class="flex items-center justify-center gap-2 rounded-xl border border-neutral-300 bg-white py-2.5 text-xs font-bold text-neutral-800 transition hover:bg-neutral-50">
                                <svg viewBox="0 0 24 24" class="size-4 stroke-current stroke-2 fill-none"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><line x1="8.59" y1="13.51" x2="15.42" y2="17.49"/><line x1="15.41" y1="6.51" x2="8.59" y2="10.49"/></svg>
                                <span>Bagikan</span>
                            </button>
                        </div>
                    </div>
                </div>

                {{-- Card Penjual --}}
                <div class="rounded-3xl border border-neutral-200 bg-white p-5 sm:p-6 shadow-sm">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="size-12 overflow-hidden rounded-full bg-neutral-200 border border-neutral-200 grid place-items-center text-neutral-400">
                                <svg viewBox="0 0 24 24" class="size-7 fill-neutral-400"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>
                            </div>
                            <div>
                                <div class="flex items-center gap-1.5">
                                    <h3 class="text-sm font-bold text-neutral-900">{{ $seller->name }}</h3>
                                    <svg viewBox="0 0 24 24" class="size-4 fill-emerald-600 text-white" aria-label="Verified"><circle cx="12" cy="12" r="10" class="fill-emerald-600"/><path d="m9 12 2 2 4-4" class="fill-none stroke-white stroke-2"/></svg>
                                </div>
                                <p class="text-xs text-neutral-500">
                                    📍 {{ $seller->kota }} · <span class="text-emerald-600 font-medium">{{ $seller->online_status }}</span>
                                </p>
                            </div>
                        </div>

                        <a href="{{ route('profile.catalog') }}"
                           class="rounded-xl border border-neutral-300 px-3.5 py-1.5 text-xs font-semibold text-neutral-800 transition hover:bg-neutral-900 hover:text-white hover:border-neutral-900">
                            Profil
                        </a>
                    </div>

                    <div class="mt-4 flex items-center justify-between border-t border-neutral-100 pt-3 text-xs text-neutral-600">
                        <span>Rating Penjual:</span>
                        <span class="font-bold text-neutral-900">★ {{ number_format($seller->rating, 1) }} ({{ $seller->reviews_count }} ulasan)</span>
                    </div>

                    {{-- Tombol Laporkan Produk --}}
                    <div class="mt-4 pt-3 border-t border-neutral-100 text-center">
                        <button type="button" onclick="document.getElementById('report-modal').classList.remove('hidden')"
                                class="inline-flex items-center gap-1.5 text-xs font-semibold text-neutral-500 transition hover:text-red-600">
                            <svg viewBox="0 0 24 24" class="size-4 fill-none stroke-current stroke-2"><path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1zM4 22v-7"/></svg>
                            <span>Laporkan jika produk ini mencurigakan atau bermasalah</span>
                        </button>
                    </div>
                </div>

            </div>
        </div>

    </div>

    {{-- Rekomendasi Barang Lainnya (Wide Desktop Grid 4-5 Kolom) --}}
    <div class="mt-16 border-t border-neutral-200 pt-10">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h3 class="text-xl font-bold text-neutral-900">Produk Serupa Lainnya</h3>
                <p class="text-xs text-neutral-500 mt-0.5">Pilihan barang bekas berkualitas yang mungkin Anda minati</p>
            </div>
            <a href="{{ route('home') }}" class="text-xs font-bold text-neutral-900 hover:text-lime-700 transition">
                Lihat Semua &rarr;
            </a>
        </div>

        <div class="grid grid-cols-2 gap-4 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4">
            @foreach ($similarProducts as $item)
                <div class="h-full transition-transform duration-200 hover:-translate-y-1">
                    <x-product-card :product="$item" />
                </div>
            @endforeach
        </div>
    </div>

</div>

{{-- Modal Popup Laporkan Produk --}}
<div id="report-modal" class="fixed inset-0 z-50 hidden flex items-center justify-center bg-black/60 p-4 backdrop-blur-sm">
    <div class="w-full max-w-lg rounded-3xl bg-white p-6 sm:p-8 shadow-2xl transition">
        <div class="flex items-center justify-between border-b border-neutral-200 pb-4">
            <div class="flex items-center gap-2 text-red-600">
                <svg viewBox="0 0 24 24" class="size-5 fill-none stroke-current stroke-2"><path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1zM4 22v-7"/></svg>
                <h3 class="text-base font-bold text-neutral-900">Laporkan Barang Ini</h3>
            </div>
            <button type="button" onclick="document.getElementById('report-modal').classList.add('hidden')"
                    class="grid size-8 place-items-center rounded-full text-neutral-400 hover:bg-neutral-100 hover:text-neutral-700">
                ✕
            </button>
        </div>

        <form action="{{ route('products.report', $product->id) }}" method="POST" enctype="multipart/form-data" class="mt-5 space-y-4">
            @csrf

            {{-- Alasan Pelaporan --}}
            <div>
                <label for="alasan" class="block text-xs font-bold text-neutral-800 mb-1.5">
                    Alasan Pelaporan <span class="text-red-500">*</span>
                </label>
                <select id="alasan" name="alasan" required
                        class="w-full rounded-xl border border-neutral-300 bg-neutral-50 py-2.5 px-3 text-xs font-medium text-neutral-900 focus:border-neutral-900 focus:bg-white focus:outline-none">
                    <option value="" disabled selected>Pilih alasan laporan...</option>
                    <option value="penipuan">Indikasi Penipuan / Scam</option>
                    <option value="barang_palsu">Barang Tiruan / Palsu (KW tidak sesuai deskripsi)</option>
                    <option value="konten_tidak_pantas">Konten atau Gambar Tidak Pantas</option>
                    <option value="harga_tidak_wajar">Harga Tidak Wajar / Spam</option>
                    <option value="lainnya">Alasan Lainnya</option>
                </select>
            </div>

            {{-- Keterangan / Penjelasan --}}
            <div>
                <label for="keterangan" class="block text-xs font-bold text-neutral-800 mb-1.5">
                    Penjelasan Detail <span class="text-red-500">*</span>
                </label>
                <textarea id="keterangan" name="keterangan" rows="4" required
                          placeholder="Jelaskan secara spesifik alasan Anda melaporkan produk ini..."
                          class="w-full rounded-xl border border-neutral-300 bg-neutral-50 p-3 text-xs font-medium text-neutral-900 focus:border-neutral-900 focus:bg-white focus:outline-none leading-relaxed"></textarea>
            </div>

            {{-- Lampiran Bukti --}}
            <div>
                <label for="bukti" class="block text-xs font-bold text-neutral-800 mb-1.5">
                    Unggah Bukti Gambar (Opsional)
                </label>
                <input type="file" id="bukti" name="bukti" accept="image/*"
                       class="w-full rounded-xl border border-neutral-300 bg-neutral-50 p-2 text-xs text-neutral-600 file:mr-3 file:rounded-lg file:border-0 file:bg-neutral-800 file:px-3 file:py-1.5 file:text-xs file:font-semibold file:text-white hover:file:bg-neutral-900">
            </div>

            {{-- Tombol Submit Modal --}}
            <div class="mt-6 flex items-center justify-end gap-3 pt-3 border-t border-neutral-100">
                <button type="button" onclick="document.getElementById('report-modal').classList.add('hidden')"
                        class="rounded-xl border border-neutral-300 px-4 py-2.5 text-xs font-semibold text-neutral-700 transition hover:bg-neutral-100">
                    Batal
                </button>
                <button type="submit"
                        class="rounded-xl bg-red-600 px-5 py-2.5 text-xs font-bold text-white shadow transition hover:bg-red-700">
                    Kirim Laporan
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

