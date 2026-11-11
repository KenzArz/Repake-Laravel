@extends('layouts.app')

@section('title', 'Jual Barang - RePake')

@section('content')
    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <form action="{{ route('products.store') }}" method="POST" enctype="multipart/form-data" id="productForm">
            @csrf

            <div class="grid grid-cols-1 gap-8 lg:grid-cols-12">

                {{-- SISI KIRI: Media Upload Section --}}
                <div class="lg:col-span-5">
                    <div class="sticky top-6 space-y-4">

                        {{-- Dropzone Main Box --}}
                        <div id="dropzone"
                            class="group relative flex aspect-[4/3] w-full cursor-pointer flex-col items-center justify-center rounded-2xl border-2 border-dashed border-[var(--color-border-default)] bg-[var(--color-lime-50)] p-6 text-center transition hover:border-[var(--color-lime)] hover:bg-[var(--color-lime-100)]">

                            <input type="file" id="imageInput" name="images[]" multiple
                                accept="image/png, image/jpeg, image/jpg, image/webp" class="hidden">

                            <div
                                class="flex h-16 w-16 items-center justify-center rounded-2xl bg-[var(--color-charcoal)] text-white shadow-md transition group-hover:scale-105">
                                <svg class="h-8 w-8 text-[var(--color-lime)]" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                            </div>

                            <h3 class="mt-4 text-base font-bold text-[var(--color-text-primary)]">
                                Pilih Sampai 3 Gambar Produk
                            </h3>
                            <p class="mt-1 text-xs font-medium text-[var(--color-text-secondary)]">
                                Format otomatis dikompresi ke WebP. Gambar pertama otomatis jadi Cover.
                            </p>
                        </div>

                        {{-- Inline Error Box --}}
                        <div id="imageErrorContainer"
                            class="hidden flex items-center justify-between rounded-xl border border-[var(--color-error-base)] bg-red-50 p-3 text-xs font-semibold text-[var(--color-error-base)]">
                            <span id="imageErrorMessage"></span>
                            <button type="button" id="closeErrorBtn"
                                class="ml-2 text-red-500 hover:text-red-700">&times;</button>
                        </div>

                        @error('images')
                            <p class="text-xs font-semibold text-[var(--color-error-base)]">{{ $message }}</p>
                        @enderror

                        {{-- Drag Instruction --}}
                        <div class="flex items-center gap-1.5 text-xs font-medium text-[var(--color-text-secondary)]">
                            <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4" />
                            </svg>
                            <span>Tahan & geser gambar untuk menukar urutan / menentukan Cover.</span>
                        </div>

                        {{-- Preview Grid (Maksimal 3 Gambar) --}}
                        <div id="previewContainer" class="grid grid-cols-3 gap-3">
                            {{-- Dynamic Preview Items via JS --}}
                        </div>
                    </div>
                </div>

                {{-- SISI KANAN: Form Input Card --}}
                <div class="lg:col-span-7">
                    <div
                        class="rounded-3xl border border-[var(--color-border-default)] bg-[var(--color-surface-elevated)] p-6 shadow-sm sm:p-8">

                        <div class="space-y-6">
                            {{-- 1. Nama Produk --}}
                            <div>
                                <label for="judul" class="block text-sm font-bold text-[var(--color-text-primary)]">
                                    Nama Produk
                                </label>
                                <input type="text" id="judul" name="judul" value="{{ old('judul') }}" required
                                    placeholder="Masukkan nama barang..."
                                    class="mt-2 w-full rounded-xl border border-[var(--color-border-default)] bg-[var(--color-surface-page)] px-4 py-3 text-sm text-[var(--color-text-primary)] placeholder-[var(--color-text-muted)] outline-none transition focus:border-[var(--color-lime)] focus:bg-white focus:ring-1 focus:ring-[var(--color-lime)]">
                                @error('judul')
                                    <p class="mt-1 text-xs text-[var(--color-error-base)]">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- 2. Kategori Produk --}}
                            <div>
                                <label for="category_id" class="block text-sm font-bold text-[var(--color-text-primary)]">
                                    Kategori Produk
                                </label>
                                <div class="relative mt-2">
                                    <select id="category_id" name="category_id" required
                                        class="w-full appearance-none rounded-xl border border-[var(--color-border-default)] bg-[var(--color-surface-page)] px-4 py-3 text-sm text-[var(--color-text-primary)] outline-none transition focus:border-[var(--color-lime)] focus:bg-white focus:ring-1 focus:ring-[var(--color-lime)]">
                                        <option value="" disabled {{ old('category_id') ? '' : 'selected' }}>Pilih
                                            kategori...</option>
                                        @foreach($categories as $category)
                                            <option value="{{ $category->id }}" {{ old('category_id') == $category->id ? 'selected' : '' }}>
                                                {{ $category->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <div
                                        class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-4 text-[var(--color-text-secondary)]">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M19 9l-7 7-7-7" />
                                        </svg>
                                    </div>
                                </div>
                                @error('category_id')
                                    <p class="mt-1 text-xs text-[var(--color-error-base)]">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- 3. Kondisi Produk --}}
                            <div>
                                <label class="block text-sm font-bold text-[var(--color-text-primary)]">
                                    Kondisi Produk
                                </label>
                                <div class="mt-2 flex flex-wrap gap-2.5">
                                    @php
                                        $kondisiList = ['Seperti baru', 'Dirawat dengan baik', 'Bekas pakai'];
                                        $selectedKondisi = old('kondisi', 'Seperti baru');
                                    @endphp

                                    @foreach($kondisiList as $kondisi)
                                        <label class="cursor-pointer">
                                            <input type="radio" name="kondisi" value="{{ $kondisi }}" class="peer hidden" {{ $selectedKondisi === $kondisi ? 'checked' : '' }}>
                                            <span
                                                class="inline-block rounded-full border border-[var(--color-border-default)] bg-[var(--color-surface-page)] px-5 py-2 text-xs font-semibold text-[var(--color-text-primary)] transition peer-checked:border-[var(--color-lime)] peer-checked:bg-[var(--color-lime)] peer-checked:text-[var(--color-charcoal)] peer-checked:shadow-sm">
                                                {{ $kondisi }}
                                            </span>
                                        </label>
                                    @endforeach
                                </div>
                                @error('kondisi')
                                    <p class="mt-1 text-xs text-[var(--color-error-base)]">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- 4. Harga --}}
                            <div>
                                <label for="harga" class="block text-sm font-bold text-[var(--color-text-primary)]">
                                    Harga
                                </label>
                                <div class="relative mt-2">
                                    <span
                                        class="absolute inset-y-0 left-0 flex items-center pl-4 text-sm font-semibold text-[var(--color-text-primary)]">
                                        Rp.
                                    </span>
                                    <input type="number" id="harga" name="harga" value="{{ old('harga') }}" required min="0"
                                        step="500" placeholder="Masukkan harga..."
                                        class="w-full rounded-xl border border-[var(--color-border-default)] bg-[var(--color-surface-page)] py-3 pl-12 pr-4 text-sm text-[var(--color-text-primary)] placeholder-[var(--color-text-muted)] outline-none transition focus:border-[var(--color-lime)] focus:bg-white focus:ring-1 focus:ring-[var(--color-lime)]">
                                </div>
                                @error('harga')
                                    <p class="mt-1 text-xs text-[var(--color-error-base)]">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- 5. Deskripsi --}}
                            <div>
                                <label for="deskripsi" class="block text-sm font-bold text-[var(--color-text-primary)]">
                                    Deskripsi
                                </label>
                                <textarea id="deskripsi" name="deskripsi" rows="4" required
                                    placeholder="Jelaskan kondisi, ukuran, kelengkapan, kekurangan, atau hal lain yang perlu diketahui pembeli..."
                                    class="mt-2 w-full rounded-xl border border-[var(--color-border-default)] bg-[var(--color-surface-page)] p-4 text-sm text-[var(--color-text-primary)] placeholder-[var(--color-text-muted)] outline-none transition focus:border-[var(--color-lime)] focus:bg-white focus:ring-1 focus:ring-[var(--color-lime)]">{{ old('deskripsi') }}</textarea>
                                @error('deskripsi')
                                    <p class="mt-1 text-xs text-[var(--color-error-base)]">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- Lokasi & Patokan COD (Compact Grid) --}}
                            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                {{-- 6. Lokasi / Area COD --}}
                                <div>
                                    <label for="kota"
                                        class="block text-xs font-bold text-[var(--color-text-primary)]">
                                        Kota / Area Produk
                                    </label>
                                    <div class="relative mt-1">
                                        <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-neutral-400">
                                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                                stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                            </svg>
                                        </span>
                                        <input type="text" id="kota" name="kota"
                                            value="{{ old('kota') }}" required
                                            placeholder="Contoh: Kampus 1 UNSIKA / Telukjambe"
                                            class="w-full rounded-xl border border-[var(--color-border-default)] bg-[var(--color-surface-page)] py-2.5 pl-9 pr-3 text-xs text-[var(--color-text-primary)] placeholder-[var(--color-text-muted)] outline-none transition focus:border-[var(--color-lime)] focus:bg-white">
                                    </div>
                                    @error('kota')
                                        <p class="mt-1 text-xs text-[var(--color-error-base)]">{{ $message }}</p>
                                    @enderror
                                </div>

                                {{-- 7. Patokan Lokasi --}}
                                <div>
                                    <label for="patokan_lokasi"
                                        class="block text-xs font-bold text-[var(--color-text-primary)]">
                                        Patokan Lokasi <span
                                            class="text-[10px] font-normal text-neutral-400">(Opsional)</span>
                                    </label>
                                    <input type="text" id="patokan_lokasi" name="patokan_lokasi"
                                        value="{{ old('patokan_lokasi') }}" placeholder="Contoh: Depan Gd. H. Oemar Said"
                                        class="mt-1 w-full rounded-xl border border-[var(--color-border-default)] bg-[var(--color-surface-page)] px-3 py-2.5 text-xs text-[var(--color-text-primary)] placeholder-[var(--color-text-muted)] outline-none transition focus:border-[var(--color-lime)] focus:bg-white">
                                    @error('patokan_lokasi')
                                        <p class="mt-1 text-xs text-[var(--color-error-base)]">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            {{-- Submit Button --}}
                            <div class="pt-2">
                                <button type="submit"
                                    class="w-full rounded-2xl bg-[var(--color-lime)] py-3.5 text-sm font-extrabold text-[var(--color-charcoal)] shadow-md transition hover:bg-[#a8c942] active:scale-[0.99]">
                                    Pasang Barang
                                </button>
                            </div>

                        </div>
                    </div>
                </div>

            </div>
        </form>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const dropzone = document.getElementById('dropzone');
            const imageInput = document.getElementById('imageInput');
            const previewContainer = document.getElementById('previewContainer');
            const errorContainer = document.getElementById('imageErrorContainer');
            const errorMessage = document.getElementById('imageErrorMessage');
            const closeErrorBtn = document.getElementById('closeErrorBtn');

            let filesArray = [];

            function showError(msg) {
                errorMessage.textContent = msg;
                errorContainer.classList.remove('hidden');
            }

            function hideError() {
                errorContainer.classList.add('hidden');
                errorMessage.textContent = '';
            }

            closeErrorBtn.addEventListener('click', hideError);

            dropzone.addEventListener('click', () => imageInput.click());

            imageInput.addEventListener('change', (e) => {
                hideError();
                const selectedFiles = Array.from(e.target.files);

                if (filesArray.length + selectedFiles.length > 3) {
                    showError('Maksimal hanya dapat memilih 3 gambar produk.');
                    imageInput.value = ''; // Reset nilai input
                    return;
                }

                filesArray = [...filesArray, ...selectedFiles].slice(0, 3);
                updatePreviews();
            });

            function updatePreviews() {
                previewContainer.innerHTML = '';
                const dt = new DataTransfer();

                filesArray.forEach((file, index) => {
                    dt.items.add(file);

                    const isCover = index === 0;
                    const objectUrl = URL.createObjectURL(file);

                    const itemDiv = document.createElement('div');
                    itemDiv.className = `relative aspect-square overflow-hidden rounded-2xl border-2 ${isCover ? 'border-[var(--color-lime)] ring-2 ring-[var(--color-lime)]/40 shadow-sm' : 'border-[var(--color-border-default)]'} bg-neutral-100 group cursor-grab active:cursor-grabbing transition-all duration-150`;
                    itemDiv.setAttribute('draggable', 'true');
                    itemDiv.setAttribute('data-index', index);

                    itemDiv.innerHTML = `
                                <img src="${objectUrl}" alt="Preview ${index + 1}" class="h-full w-full object-cover pointer-events-none select-none">

                                <button type="button" onclick="removeImage(${index})" 
                                        class="absolute top-1.5 right-1.5 flex h-6 w-6 items-center justify-center rounded-full bg-black/60 text-white transition hover:bg-red-600 z-10 pointer-events-auto">
                                    &times;
                                </button>

                                ${isCover ? `
                                    <div class="absolute inset-x-0 bottom-0 bg-[var(--color-lime)] py-1 text-center text-[10px] font-extrabold text-[var(--color-charcoal)] shadow-sm pointer-events-none select-none">
                                        ★ COVER UTAMA
                                    </div>
                                ` : `
                                    <div class="absolute inset-x-0 bottom-0 bg-black/40 py-0.5 text-center text-[9px] font-semibold text-white pointer-events-none select-none">
                                        Foto ${index + 1}
                                    </div>
                                `}
                            `;

                    // --- Drag and Drop Handlers ---
                    itemDiv.addEventListener('dragstart', (evt) => {
                        evt.dataTransfer.setData('text/plain', index.toString());
                        evt.dataTransfer.effectAllowed = 'move';
                        itemDiv.classList.add('opacity-40', 'scale-95');
                    });

                    itemDiv.addEventListener('dragover', (evt) => {
                        evt.preventDefault();
                        evt.dataTransfer.dropEffect = 'move';
                        itemDiv.classList.add('scale-105', 'border-amber-400');
                    });

                    itemDiv.addEventListener('dragleave', () => {
                        itemDiv.classList.remove('scale-105', 'border-amber-400');
                    });

                    itemDiv.addEventListener('drop', (evt) => {
                        evt.preventDefault();
                        evt.stopPropagation();
                        itemDiv.classList.remove('scale-105', 'border-amber-400');

                        const fromIndex = parseInt(evt.dataTransfer.getData('text/plain'), 10);
                        const toIndex = index;

                        if (!isNaN(fromIndex) && fromIndex !== toIndex) {
                            const movedItem = filesArray.splice(fromIndex, 1)[0];
                            filesArray.splice(toIndex, 0, movedItem);
                            updatePreviews();
                        }
                    });

                    itemDiv.addEventListener('dragend', () => {
                        itemDiv.classList.remove('opacity-40', 'scale-95');
                    });

                    previewContainer.appendChild(itemDiv);
                });

                imageInput.files = dt.files;
            }

            window.removeImage = function (index) {
                hideError();
                filesArray.splice(index, 1);
                updatePreviews();
            };
        });
    </script>
@endpush