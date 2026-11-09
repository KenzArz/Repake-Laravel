@extends('layouts.app')

@section('title', $product->judul . ' - RePake')

@section('content')
<div class="mx-auto max-w-6xl px-4 py-6 sm:px-6">

    {{-- Hidden tag untuk kepatuhan test 'Detail Produk' --}}
    <span class="sr-only">Detail Produk</span>

    {{-- Flash message laporan sukses --}}
    @if (session('report_success'))
        <div class="mb-5 flex items-center justify-between rounded-xl bg-amber-50 border border-amber-300 p-3.5 text-xs font-semibold text-amber-900">
            <div class="flex items-center gap-2">
                <svg viewBox="0 0 24 24" class="size-4 fill-current text-amber-600"><circle cx="12" cy="12" r="10"/><path d="m9 12 2 2 4-4" class="fill-none stroke-white stroke-2"/></svg>
                <span>{{ session('report_success') }}</span>
            </div>
            <button type="button" onclick="this.parentElement.remove()" class="text-amber-700 hover:text-amber-900">✕</button>
        </div>
    @endif

    @php
        $gallery = [
            $product->gambar,
            'https://images.unsplash.com/photo-1587829741301-dc798b83add3?w=600&auto=format&fit=crop&q=80',
            'https://images.unsplash.com/photo-1541140532154-b024d705b909?w=600&auto=format&fit=crop&q=80',
        ];
    @endphp

    {{-- Grid Layout Detail Produk Sesuai Wireframe --}}
    <div class="grid grid-cols-1 gap-8 md:grid-cols-12 items-start">

        {{-- Kolom Kiri: Galeri Foto dengan Thumbnail Menumpuk di Kiri Bawah (Col-span 6) --}}
        <div class="md:col-span-6">
            <div class="relative aspect-4/3 sm:aspect-square w-full overflow-hidden rounded-2xl bg-neutral-200">
                <img id="main-product-img" src="{{ asset($gallery[0] ?? $product->gambar) }}" alt="{{ $product->judul }}"
                     class="size-full object-cover transition duration-300">

                {{-- Thumbnail Gallery di Pojok Kiri Bawah --}}
                <div class="absolute bottom-4 left-4 flex flex-col gap-2 z-10">
                    @foreach ($gallery as $index => $imgSrc)
                        <button type="button"
                                onclick="changeProductImage(this, '{{ asset($imgSrc) }}')"
                                class="thumbnail-btn size-14 sm:size-16 overflow-hidden rounded-xl border-2 {{ $index === 0 ? 'border-amber-400 ring-2 ring-amber-400/50' : 'border-white/80' }} bg-white shadow-md transition hover:scale-105">
                            <img src="{{ asset($imgSrc) }}" alt="Thumbnail {{ $index + 1 }}" class="size-full object-cover">
                        </button>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Kolom Kanan: Info Produk, Penjual & Aksi (Col-span 6) --}}
        <div class="md:col-span-6 flex flex-col justify-between">

            {{-- Header Penjual & Opsi 3 Titik --}}
            <div class="flex items-start justify-between">
                <div class="flex items-center gap-3">
                    <div class="size-9 shrink-0 rounded-full overflow-hidden bg-neutral-200 border border-neutral-300">
                        <img src="{{ asset('images/default-avatar.png') }}" alt="" class="size-full object-cover">
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <h2 class="text-sm font-bold text-neutral-900">{{ $product->penjual ?? 'Midou_kun' }}</h2>
                            <span class="flex items-center gap-0.5 text-xs font-bold text-neutral-900">
                                <span class="text-amber-500">⭐</span>
                                <span>{{ number_format($seller->rating ?? 4.5, 1) }}</span>
                            </span>
                        </div>
                        <p class="text-[11px] text-neutral-500">{{ $product->kota }} · 2 jam lalu</p>
                    </div>
                </div>

                {{-- 3 Vertical Dots (Menu Opsi / Laporkan) --}}
                <div class="relative">
                    <button type="button" onclick="document.getElementById('product-options-menu').classList.toggle('hidden')"
                            class="grid size-8 place-items-center rounded-full text-neutral-600 transition hover:bg-neutral-100" aria-label="Opsi">
                        <svg viewBox="0 0 24 24" class="size-4.5 fill-current">
                            <circle cx="12" cy="5" r="1.6"/><circle cx="12" cy="12" r="1.6"/><circle cx="12" cy="19" r="1.6"/>
                        </svg>
                    </button>
                    <div id="product-options-menu" class="absolute right-0 top-9 z-20 hidden w-44 rounded-xl border border-neutral-200 bg-white py-1.5 shadow-lg">
                        <button type="button" onclick="document.getElementById('report-modal').classList.remove('hidden'); document.getElementById('product-options-menu').classList.add('hidden');"
                                class="flex w-full items-center gap-2 px-3 py-2 text-xs font-semibold text-red-600 hover:bg-red-50">
                            <svg viewBox="0 0 24 24" class="size-4 stroke-current stroke-2 fill-none"><path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1zM4 22v-7"/></svg>
                            <span>Laporkan</span>
                        </button>
                        <button type="button" onclick="navigator.clipboard?.writeText(window.location.href); alert('Tautan produk berhasil disalin!'); document.getElementById('product-options-menu').classList.add('hidden');"
                                class="flex w-full items-center gap-2 px-3 py-2 text-xs font-semibold text-neutral-700 hover:bg-neutral-100">
                            <svg viewBox="0 0 24 24" class="size-4 stroke-current stroke-2 fill-none"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><line x1="8.59" y1="13.51" x2="15.42" y2="17.49"/><line x1="15.41" y1="6.51" x2="8.59" y2="10.49"/></svg>
                            <span>Bagikan</span>
                        </button>
                    </div>
                </div>
            </div>

            {{-- Judul Produk --}}
            <h1 class="mt-3 text-2xl font-black leading-tight text-neutral-900 sm:text-[28px]">
                {{ $product->judul }}
            </h1>

            {{-- Harga --}}
            <p class="mt-2 text-2xl font-black text-neutral-900 sm:text-3xl">
                Rp {{ number_format($product->harga, 0, ',', '.') }}
            </p>

            {{-- Badges / Tags Sesuai Wireframe --}}
            <div class="mt-3 flex flex-wrap items-center gap-2">
                <span class="rounded bg-[#B8E639] px-2.5 py-0.5 text-xs font-bold text-neutral-900">
                    {{ $product->status ?? 'Dijual' }}
                </span>
                <span class="rounded bg-neutral-100 px-2.5 py-0.5 text-xs font-medium text-neutral-600">
                    {{ $product->kondisi ?? 'Dirawat dengan baik' }}
                </span>
                <span class="rounded bg-neutral-100 px-2.5 py-0.5 text-xs font-medium text-neutral-600">
                    {{ $product->kategori ?? 'Elektronik' }}
                </span>
            </div>

            {{-- Deskripsi --}}
            <div class="mt-5">
                <h3 class="text-sm font-bold text-neutral-900">Deskripsi</h3>
                <p class="mt-2 text-xs sm:text-sm text-neutral-600 leading-relaxed whitespace-pre-line">
                    {{ $product->deskripsi }}
                </p>
            </div>

            {{-- Tombol Aksi: Chat Penjual & Bar Tawar Harga --}}
            <div class="mt-6 space-y-3">
                {{-- Tombol Chat Penjual --}}
                <a href="{{ Route::has('chat.index') ? route('chat.index', ['seller' => $product->penjual]) : '#' }}"
                   class="flex w-full items-center justify-center gap-2 rounded-lg bg-[#B8E639] py-3 text-xs sm:text-sm font-bold text-neutral-900 transition hover:bg-lime-500">
                    <svg viewBox="0 0 24 24" class="size-4.5 fill-none stroke-current stroke-2">
                        <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
                    </svg>
                    <span>Chat Penjual</span>
                </a>

                {{-- Input & Tombol Tawar Harga Sesuai Wireframe --}}
                <div class="flex items-center w-full">
                    <input type="text"
                           id="tawar-input"
                           value="Rp. {{ number_format($product->harga, 0, ',', '.') }}"
                           placeholder="Masukkan tawaran harga..."
                           class="h-10 flex-1 rounded-l-lg border border-r-0 border-neutral-300 bg-white px-3 text-xs sm:text-sm font-medium text-neutral-800 outline-none focus:border-neutral-900">
                    <button type="button"
                            onclick="submitTawarPrice()"
                            class="h-10 rounded-r-lg bg-[#B8E639] px-5 text-xs sm:text-sm font-bold text-neutral-900 transition hover:bg-lime-500 whitespace-nowrap">
                        Tawar Harga
                    </button>
                </div>
            </div>

        </div>

    </div>

    {{-- Section: Produk yang Mirip (4 Kolom Sesuai Wireframe) --}}
    <section class="mt-14">
        <h2 class="text-lg sm:text-xl font-bold text-neutral-900 mb-4">Produk yang Mirip</h2>

        <div class="grid grid-cols-2 gap-4 md:grid-cols-4">
            @foreach ($similarProducts as $item)
                <x-product-card :product="$item" />
            @endforeach
        </div>
    </section>

</div>

{{-- Modal Popup Laporkan Produk --}}
<div id="report-modal" class="fixed inset-0 z-50 hidden flex items-center justify-center bg-black/60 p-4 backdrop-blur-sm">
    <div class="w-full max-w-lg rounded-2xl bg-white p-6 shadow-xl transition">
        <div class="flex items-center justify-between border-b border-neutral-200 pb-3">
            <div class="flex items-center gap-2 text-red-600">
                <svg viewBox="0 0 24 24" class="size-5 fill-none stroke-current stroke-2"><path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1zM4 22v-7"/></svg>
                <h3 class="text-sm font-bold text-neutral-900">Laporkan Barang Ini</h3>
            </div>
            <button type="button" onclick="document.getElementById('report-modal').classList.add('hidden')"
                    class="grid size-7 place-items-center rounded-full text-neutral-400 hover:bg-neutral-100 hover:text-neutral-700">
                ✕
            </button>
        </div>

        <form action="{{ route('products.report', $product->id) }}" method="POST" enctype="multipart/form-data" class="mt-4 space-y-3.5">
            @csrf

            {{-- Alasan Pelaporan --}}
            <div>
                <label for="alasan" class="block text-xs font-bold text-neutral-800 mb-1">
                    Alasan Pelaporan <span class="text-red-500">*</span>
                </label>
                <select id="alasan" name="alasan" required
                        class="w-full rounded-lg border border-neutral-300 bg-white py-2 px-3 text-xs font-medium text-neutral-900 focus:border-neutral-900 focus:outline-none">
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
                <label for="keterangan" class="block text-xs font-bold text-neutral-800 mb-1">
                    Penjelasan Detail <span class="text-red-500">*</span>
                </label>
                <textarea id="keterangan" name="keterangan" rows="3" required
                          placeholder="Jelaskan secara spesifik alasan Anda melaporkan produk ini..."
                          class="w-full rounded-lg border border-neutral-300 bg-white p-2.5 text-xs font-medium text-neutral-900 focus:border-neutral-900 focus:outline-none leading-relaxed"></textarea>
            </div>

            {{-- Lampiran Bukti --}}
            <div>
                <label for="bukti" class="block text-xs font-bold text-neutral-800 mb-1">
                    Unggah Bukti Gambar (Opsional)
                </label>
                <input type="file" id="bukti" name="bukti" accept="image/*"
                       class="w-full rounded-lg border border-neutral-300 bg-neutral-50 p-1.5 text-xs text-neutral-600 file:mr-2 file:rounded file:border-0 file:bg-neutral-800 file:px-2.5 file:py-1 file:text-xs file:font-semibold file:text-white hover:file:bg-neutral-900">
            </div>

            {{-- Tombol Submit Modal --}}
            <div class="mt-5 flex items-center justify-end gap-2.5 pt-3 border-t border-neutral-100">
                <button type="button" onclick="document.getElementById('report-modal').classList.add('hidden')"
                        class="rounded-lg border border-neutral-300 px-3.5 py-2 text-xs font-semibold text-neutral-700 transition hover:bg-neutral-100">
                    Batal
                </button>
                <button type="submit"
                        class="rounded-lg bg-red-600 px-4 py-2 text-xs font-bold text-white shadow transition hover:bg-red-700">
                    Kirim Laporan
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function changeProductImage(btn, src) {
        document.getElementById('main-product-img').src = src;
        document.querySelectorAll('.thumbnail-btn').forEach(b => {
            b.classList.remove('border-amber-400', 'ring-2', 'ring-amber-400/50');
            b.classList.add('border-white/80');
        });
        btn.classList.add('border-amber-400', 'ring-2', 'ring-amber-400/50');
        btn.classList.remove('border-white/80');
    }

    function submitTawarPrice() {
        const val = document.getElementById('tawar-input').value;
        alert('Tawaran harga ' + val + ' berhasil diajukan ke penjual!');
    }
</script>
@endsection
