@extends('layouts.app')

@section('title', 'Jual Barang - RePake')

@section('content')
    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <form action="{{ route('products.store') }}" method="POST" enctype="multipart/form-data" id="productForm" novalidate>
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
                                <p id="err-judul" class="mt-1 text-xs text-[var(--color-error-base)] {{ $errors->has('judul') ? '' : 'hidden' }}">{{ $errors->first('judul') }}</p>
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
                                <p id="err-category_id" class="mt-1 text-xs text-[var(--color-error-base)] {{ $errors->has('category_id') ? '' : 'hidden' }}">{{ $errors->first('category_id') }}</p>
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
                                <p id="err-kondisi" class="mt-1 text-xs text-[var(--color-error-base)] {{ $errors->has('kondisi') ? '' : 'hidden' }}">{{ $errors->first('kondisi') }}</p>
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
                                <p id="err-harga" class="mt-1 text-xs text-[var(--color-error-base)] {{ $errors->has('harga') ? '' : 'hidden' }}">{{ $errors->first('harga') }}</p>
                            </div>

                            {{-- 5. Deskripsi --}}
                            <div>
                                <label for="deskripsi" class="block text-sm font-bold text-[var(--color-text-primary)]">
                                    Deskripsi
                                </label>
                                <textarea id="deskripsi" name="deskripsi" rows="4" required
                                    placeholder="Jelaskan kondisi, ukuran, kelengkapan, kekurangan, atau hal lain yang perlu diketahui pembeli..."
                                    class="mt-2 w-full rounded-xl border border-[var(--color-border-default)] bg-[var(--color-surface-page)] p-4 text-sm text-[var(--color-text-primary)] placeholder-[var(--color-text-muted)] outline-none transition focus:border-[var(--color-lime)] focus:bg-white focus:ring-1 focus:ring-[var(--color-lime)]">{{ old('deskripsi') }}</textarea>
                                <p id="err-deskripsi" class="mt-1 text-xs text-[var(--color-error-base)] {{ $errors->has('deskripsi') ? '' : 'hidden' }}">{{ $errors->first('deskripsi') }}</p>
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
                                    <p id="err-kota" class="mt-1 text-xs text-[var(--color-error-base)] {{ $errors->has('kota') ? '' : 'hidden' }}">{{ $errors->first('kota') }}</p>
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
                                    <p id="err-patokan_lokasi" class="mt-1 text-xs text-[var(--color-error-base)] {{ $errors->has('patokan_lokasi') ? '' : 'hidden' }}">{{ $errors->first('patokan_lokasi') }}</p>
                                </div>
                            </div>

                            {{-- Error umum (gagal simpan / server) --}}
                            <p id="formError" class="hidden rounded-xl border border-[var(--color-error-base)] bg-red-50 p-3 text-xs font-semibold text-[var(--color-error-base)]"></p>

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
            console.log('[create] DOMContentLoaded fired');
            const form = document.getElementById('productForm');
            const dropzone = document.getElementById('dropzone');
            const imageInput = document.getElementById('imageInput');
            const previewContainer = document.getElementById('previewContainer');
            const errorContainer = document.getElementById('imageErrorContainer');
            const errorMessage = document.getElementById('imageErrorMessage');
            const closeErrorBtn = document.getElementById('closeErrorBtn');
            const formError = document.getElementById('formError');
            const submitBtn = form.querySelector('button[type="submit"]');

            console.log('[create] elements:', { form: !!form, dropzone: !!dropzone, imageInput: !!imageInput, previewContainer: !!previewContainer, submitBtn: !!submitBtn });

            const MAX_FILES = 3;
            const MAX_BYTES = 5 * 1024 * 1024; // 5MB (client-side check)
            const SAFE_UPLOAD_BYTES = 2 * 1024 * 1024; // 2MB — aman terhadap limit php.ini
            const FIELD_IDS = ['judul', 'category_id', 'harga', 'deskripsi', 'kota', 'patokan_lokasi'];

            let filesArray = [];

            function showError(msg) {
                errorMessage.textContent = msg;
                errorContainer.classList.remove('hidden');
            }

            function hideError() {
                errorContainer.classList.add('hidden');
                errorMessage.textContent = '';
            }

            function showFormError(msg) {
                formError.textContent = msg;
                formError.classList.toggle('hidden', !msg);
            }

            function setFieldError(name, msg) {
                const el = document.getElementById('err-' + name);
                if (el) {
                    el.textContent = msg;
                    el.classList.toggle('hidden', !msg);
                }
            }

            closeErrorBtn.addEventListener('click', hideError);
            dropzone.addEventListener('click', () => {
                // Pastikan imageInput ada sebelum memanggil .click()
                if (imageInput) imageInput.click();
            });

            // Juga support drag & drop langsung ke dropzone
            dropzone.addEventListener('dragover', (e) => {
                e.preventDefault();
                dropzone.classList.add('border-[var(--color-lime)]', 'bg-[var(--color-lime-100)]');
            });
            dropzone.addEventListener('dragleave', () => {
                dropzone.classList.remove('border-[var(--color-lime)]', 'bg-[var(--color-lime-100)]');
            });
            dropzone.addEventListener('drop', (e) => {
                e.preventDefault();
                dropzone.classList.remove('border-[var(--color-lime)]', 'bg-[var(--color-lime-100)]');
                // Trigger change handler dengan file dari drag-drop
                if (imageInput) imageInput.files = e.dataTransfer.files;
                imageInput.dispatchEvent(new Event('change', { bubbles: true }));
            });

            function textVal(id) {
                const el = document.getElementById(id);
                return el ? el.value.trim() : '';
            }

            const validators = {
                judul: () => {
                    const v = textVal('judul');
                    if (!v) return 'Nama produk wajib diisi.';
                    if (v.length > 255) return 'Nama produk maksimal 255 karakter.';
                    return '';
                },
                category_id: () => (textVal('category_id') ? '' : 'Pilih kategori produk.'),
                harga: () => {
                    const el = document.getElementById('harga');
                    if (el.value === '' || el.value === null) return 'Harga wajib diisi.';
                    const n = Number(el.value);
                    if (!Number.isInteger(n)) return 'Harga harus angka bulat.';
                    if (n < 0) return 'Harga minimal 0 (Rp 0).';
                    return '';
                },
                deskripsi: () => {
                    const v = textVal('deskripsi');
                    if (!v) return 'Deskripsi wajib diisi.';
                    if (v.length < 10) return 'Deskripsi minimal 10 karakter.';
                    return '';
                },
                kota: () => {
                    const v = textVal('kota');
                    if (!v) return 'Kota / area produk wajib diisi.';
                    if (v.length > 255) return 'Kota maksimal 255 karakter.';
                    return '';
                },
                patokan_lokasi: () => {
                    const v = textVal('patokan_lokasi');
                    if (v.length > 255) return 'Patokan lokasi maksimal 255 karakter.';
                    return '';
                },
            };

            // Validasi real-time tiap input
            FIELD_IDS.forEach((name) => {
                const el = document.getElementById(name);
                if (el) {
                    el.addEventListener('input', () => setFieldError(name, validators[name]()));
                    el.addEventListener('change', () => setFieldError(name, validators[name]()));
                }
            });

            function validateImages() {
                if (filesArray.length === 0) return 'Unggah minimal 1 gambar produk.';
                if (filesArray.length > MAX_FILES) return 'Maksimal ' + MAX_FILES + ' gambar produk.';
                for (const f of filesArray) {
                    if (!f.type.startsWith('image/')) return 'Hanya file gambar yang diperbolehkan.';
                    if (f.size > MAX_BYTES) return 'Gambar "' + f.name + '" melebihi 5MB.';
                }
                return '';
            }

            // Validasi seluruh form; kembalikan nama field pertama yang gagal (atau 'images')
            function validateAll() {
                let first = null;
                FIELD_IDS.forEach((name) => {
                    const msg = validators[name]();
                    setFieldError(name, msg);
                    if (msg && first === null) first = name;
                });
                const imgErr = validateImages();
                if (imgErr) {
                    showError(imgErr);
                    if (first === null) first = 'images';
                } else {
                    hideError();
                }
                return first;
            }

            function focusInvalid(name) {
                if (name === 'images') {
                    dropzone.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    return;
                }
                const el = document.getElementById(name);
                if (el) {
                    el.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    el.focus({ preventScroll: true });
                }
            }

            imageInput.addEventListener('change', (e) => {
                console.log('[change] imageInput triggered, files:', e.target.files?.length);
                hideError();
                const accepted = [];
                let rejected = '';

                const fileInputFiles = Array.from(e.target.files || []);
                if (fileInputFiles.length === 0) return;

                fileInputFiles.forEach((f) => {
                    if (!f.type.startsWith('image/')) { rejected = 'Hanya file gambar yang diperbolehkan.'; return; }
                    if (f.size > MAX_BYTES) { rejected = 'Gambar "' + f.name + '" melebihi 5MB.'; return; }
                    accepted.push(f);
                });

                if (filesArray.length + accepted.length > MAX_FILES) {
                    showError('Maksimal hanya dapat memilih ' + MAX_FILES + ' gambar produk.');
                    imageInput.value = '';
                    return;
                }

                if (accepted.length > 0) {
                    // Kompres gambar yang masih besar agar tetap di bawah batas 5MB,
                    // namun tetap aman di-limit upload php.ini (2MB) agar tidak ditolak server.
                    Promise.all(accepted.map(async (f) => {
                        if (f.size <= SAFE_UPLOAD_BYTES) {
                            console.log('[change] skip compress for', f.name, 'size:', f.size);
                            return f;
                        }
                        // Coba compress bertahap: pertama ke quality 0.7, lalu 0.4 jika masih > 2MB
                        let result = await compressImage(f, 0.7);
                        if (result.size > SAFE_UPLOAD_BYTES) {
                            result = await compressImage(f, 0.4);
                        }
                        return result;
                    })).then((compressed) => {
                        console.log('[change] compressed', compressed.length, 'files:', compressed.map(f => f.name));
                        filesArray = [...filesArray, ...compressed].slice(0, MAX_FILES);
                        console.log('[change] filesArray now has', filesArray.length, 'files');
                        updatePreviews();
                    }).catch((err) => {
                        console.error('[change] compress error:', err);
                        showError('Gagal memproses gambar. Coba gambar lain.');
                    });
                }

                if (rejected) showError(rejected);
            });

            /**
             * Kompres gambar via Canvas API ke JPEG.
             * @param {File} file File asli
             * @param {number} quality Kualitas JPEG 0-1 (default 0.7)
             * @param {number} maxSide Max dimension pixel (default 1920)
             * @returns {Promise<File>}
             */
            function compressImage(file, quality = 0.7, maxSide = 1920) {
                console.log('[compress] starting for', file.name, 'size:', file.size);
                return new Promise((resolve) => {
                    const img = new Image();
                    img.onload = () => {
                        console.log('[compress] onload for', file.name, 'dims:', img.naturalWidth, 'x', img.naturalHeight);
                        const canvas = document.createElement('canvas');
                        let w = img.width, h = img.height;
                        if (w > maxSide) { h = (maxSide / w) * h; w = maxSide; }
                        if (h > maxSide) { w = (maxSide / h) * w; h = maxSide; }
                        canvas.width = Math.round(w);
                        canvas.height = Math.round(h);
                        const ctx = canvas.getContext('2d');
                        ctx.drawImage(img, 0, 0, w, h);
                        canvas.toBlob((blob) => {
                            if (blob) {
                                console.log('[compress] done for', file.name, 'new size:', blob.size);
                                resolve(new File([blob], file.name, { type: 'image/jpeg' }));
                            } else {
                                console.log('[compress] blob null for', file.name, 'return original');
                                resolve(file);
                            }
                        }, 'image/jpeg', quality);
                    };
                    img.onerror = () => {
                        console.log('[compress] onerror for', file.name, 'return original');
                        resolve(file);
                    };
                    img.src = URL.createObjectURL(file);
                });
            }

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

                // Sync ke native input file (untuk enctype="multipart/form-data")
                imageInput.files = dt.files;
            }

            /**
             * Buang FormData(form) lalu susun ulang dari fields + filesArray secara manual.
             * Ini menghindari bug browser di mana assignment `input.files = FileList` tidak
             * terbaca saat `new FormData(form)` dipanggil setelahnya.
             */
            function buildFormData() {
                const fd = new FormData();
                // Salin semua field text/number/textarea/select
                for (const el of form.querySelectorAll('input, textarea, select')) {
                    if (el.name === 'images[]') continue; // lewati input file
                    if (el.type === 'radio') {
                        if (el.checked) fd.append(el.name, el.value);
                    } else {
                        fd.append(el.name, el.value);
                    }
                }
                // Tambahkan file menggunakan key 'images[]' — format HTML standar
                // untuk multiple file input. Laravel otomatis mengurai ini sebagai array.
                filesArray.forEach((f) => fd.append('images[]', f, f.name));

                // Debug: verifikasi isi FormData
                console.log('[buildFormData] filesArray.length =', filesArray.length);
                console.log('[buildFormData] keys:', [...fd.keys()].join(', '));
                const imageEntries = [...fd.entries()].filter(([k]) => k.startsWith('images'));
                console.log('[buildFormData] images entries:', imageEntries.length);
                imageEntries.forEach(([k, v]) => {
                    console.log(`  - ${k}: File(${v.name}, ${v.size}b)`);
                });

                return fd;
            }

            window.removeImage = function (index) {
                hideError();
                filesArray.splice(index, 1);
                updatePreviews();
            };

            // Submit via AJAX agar gambar & isian tetap ada saat gagal
            form.addEventListener('submit', (ev) => {
                console.log('[submit] form submitted');
                ev.preventDefault();
                hideError();
                showFormError('');

                const first = validateAll();
                if (first) {
                    console.log('[submit] validation failed, first:', first);
                    focusInvalid(first);
                    return;
                }

                console.log('[submit] validation passed, filesArray.length:', filesArray.length);
                submitBtn.disabled = true;

                fetch(form.action, {
                    method: 'POST',
                    body: buildFormData(),
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                    credentials: 'same-origin',
                }).then((resp) => {
                    if (resp.status === 419) {
                        window.location.reload();
                        return;
                    }
                    if (resp.status === 413) {
                        showError('File terlalu besar. Maksimal 5MB per gambar.');
                        throw { generic: true };
                    }
                    if (resp.status === 422) {
                        return resp.json().then((data) => { throw { validation: data.errors || {} }; });
                    }
                    if (resp.ok) {
                        return resp.json().then((data) => {
                            if (data && data.redirect) {
                                window.location.assign(data.redirect);
                            } else {
                                window.location.reload();
                            }
                        });
                    }
                    throw { generic: true };
                }).catch((err) => {
                    submitBtn.disabled = false;
                    if (err && err.validation) {
                        let firstField = null;
                        Object.entries(err.validation).forEach(([key, msgs]) => {
                            const msg = Array.isArray(msgs) ? msgs[0] : msgs;
                            if (key.startsWith('images')) {
                                showError(msg);
                                if (firstField === null) firstField = 'images';
                            } else {
                                setFieldError(key, msg);
                                if (firstField === null) firstField = key;
                            }
                        });
                        if (firstField) focusInvalid(firstField);
                        return;
                    }
                    showFormError('Terjadi kesalahan saat mengunggah. Silakan coba lagi atau gunakan gambar lain.');
                });
            });
        });
    </script>
@endpush