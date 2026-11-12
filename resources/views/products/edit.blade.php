@extends('layouts.app')

@section('title', 'Edit ' . $product->judul . ' - RePake')

@section('content')
    @php
        $codArea = $product->codAreas->first();
        $coords = data_get($codArea, 'boundary.coordinates', []);
        $defaultLng = is_array($coords) && isset($coords[0]) ? $coords[0] : '';
        $defaultLat = is_array($coords) && isset($coords[1]) ? $coords[1] : '';
        $defaultPlace = $codArea?->name ?: $product->kota;
        $defaultProvince = $codArea?->province ?: '';

        $orderedImages = $product->images
            ->sortBy(fn($img) => [$img->is_cover ? 0 : 1, $img->urutan ?? 99, $img->id])
            ->values();

        $initialImagesPayload = $orderedImages->map(fn($img) => [
            'id' => $img->id,
            'url' => asset($img->path_url),
        ])->all();
    @endphp

    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <form action="{{ route('products.update', $product) }}" method="POST" enctype="multipart/form-data"
            id="productEditForm" novalidate>
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 gap-8 lg:grid-cols-12">

                {{-- Kolom Kiri: Dropzone & Preview Gambar (Identik dengan Form Jual) --}}
                <div class="lg:col-span-5">
                    <div class="sticky top-6 space-y-4">

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

                        <div id="imageErrorContainer"
                            class="hidden flex items-center justify-between rounded-xl border border-[var(--color-error-base)] bg-red-50 p-3 text-xs font-semibold text-[var(--color-error-base)]">
                            <span id="imageErrorMessage"></span>
                            <button type="button" id="closeErrorBtn"
                                class="ml-2 text-red-500 hover:text-red-700 cursor-pointer">&times;</button>
                        </div>

                        @error('images')
                            <p class="text-xs font-semibold text-[var(--color-error-base)]">{{ $message }}</p>
                        @enderror

                        <div class="flex items-center justify-between gap-2">
                            <div class="flex items-center gap-1.5 text-xs font-medium text-[var(--color-text-secondary)]">
                                <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                    stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4" />
                                </svg>
                                <span>Tahan & geser gambar untuk menukar urutan / menentukan Cover.</span>
                            </div>

                            <button type="button" id="restoreDeletedBtn"
                                class="hidden shrink-0 text-xs font-bold text-lime-700 hover:underline cursor-pointer">
                                Pulihkan foto
                            </button>
                        </div>

                        <div id="previewContainer" data-existing-images='@json($initialImagesPayload)'
                            class="grid grid-cols-3 gap-3">
                        </div>
                    </div>
                </div>

                {{-- Kolom Kanan: Form Detail Produk (Identik dengan Form Jual) --}}
                <div class="lg:col-span-7">
                    <div
                        class="rounded-3xl border border-[var(--color-border-default)] bg-[var(--color-surface-elevated)] p-6 shadow-sm sm:p-8">

                        <div
                            class="mb-6 flex items-center justify-between gap-4 border-b border-[var(--color-border-default)] pb-4">
                            <h1 class="text-xl font-extrabold text-[var(--color-text-primary)] sm:text-2xl">
                                Edit <span id="headerProductTitle" class="text-lime-600">{{ $product->judul }}</span>
                            </h1>
                            <a href="{{ route('products.show', $product) }}"
                                class="shrink-0 rounded-xl border border-[var(--color-border-default)] bg-[var(--color-surface-page)] px-3.5 py-2 text-xs font-bold text-[var(--color-text-secondary)] transition hover:bg-neutral-100 hover:text-[var(--color-text-primary)]">
                                Batal
                            </a>
                        </div>

                        <div class="space-y-6">
                            <div>
                                <label for="judul" class="block text-sm font-bold text-[var(--color-text-primary)]">
                                    Nama Produk
                                </label>
                                <input type="text" id="judul" name="judul"
                                    value="{{ old('judul', $product->judul) }}" required
                                    placeholder="Masukkan nama barang..."
                                    class="mt-2 w-full rounded-xl border border-[var(--color-border-default)] bg-[var(--color-surface-page)] px-4 py-3 text-sm text-[var(--color-text-primary)] placeholder-[var(--color-text-muted)] outline-none transition focus:border-[var(--color-lime)] focus:bg-white focus:ring-1 focus:ring-[var(--color-lime)]">
                                <p id="err-judul"
                                    class="mt-1 text-xs text-[var(--color-error-base)] {{ $errors->has('judul') ? '' : 'hidden' }}">
                                    {{ $errors->first('judul') }}</p>
                            </div>

                            <div>
                                <label for="category_id" class="block text-sm font-bold text-[var(--color-text-primary)]">
                                    Kategori Produk
                                </label>
                                <div class="relative mt-2">
                                    <select id="category_id" name="category_id" required
                                        class="w-full appearance-none rounded-xl border border-[var(--color-border-default)] bg-[var(--color-surface-page)] px-4 py-3 text-sm text-[var(--color-text-primary)] outline-none transition focus:border-[var(--color-lime)] focus:bg-white focus:ring-1 focus:ring-[var(--color-lime)]">
                                        <option value="" disabled
                                            {{ old('category_id', $product->category_id) ? '' : 'selected' }}>Pilih
                                            kategori...</option>
                                        @foreach ($categories as $category)
                                            <option value="{{ $category->id }}"
                                                {{ (int) old('category_id', $product->category_id) === (int) $category->id ? 'selected' : '' }}>
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
                                <p id="err-category_id"
                                    class="mt-1 text-xs text-[var(--color-error-base)] {{ $errors->has('category_id') ? '' : 'hidden' }}">
                                    {{ $errors->first('category_id') }}</p>
                            </div>

                            <div>
                                <label class="block text-sm font-bold text-[var(--color-text-primary)]">
                                    Kondisi Produk
                                </label>
                                <div class="mt-2 flex flex-wrap gap-2.5">
                                    @php
                                        $kondisiList = ['Seperti baru', 'Dirawat dengan baik', 'Bekas pakai'];
                                        $selectedKondisi = old('kondisi', $product->kondisi);
                                    @endphp

                                    @foreach ($kondisiList as $kondisi)
                                        <label class="cursor-pointer">
                                            <input type="radio" name="kondisi" value="{{ $kondisi }}" class="peer hidden"
                                                {{ $selectedKondisi === $kondisi ? 'checked' : '' }}>
                                            <span
                                                class="inline-block rounded-full border border-[var(--color-border-default)] bg-[var(--color-surface-page)] px-5 py-2 text-xs font-semibold text-[var(--color-text-primary)] transition peer-checked:border-[var(--color-lime)] peer-checked:bg-[var(--color-lime)] peer-checked:text-[var(--color-charcoal)] peer-checked:shadow-sm">
                                                {{ $kondisi }}
                                            </span>
                                        </label>
                                    @endforeach
                                </div>
                                <p id="err-kondisi"
                                    class="mt-1 text-xs text-[var(--color-error-base)] {{ $errors->has('kondisi') ? '' : 'hidden' }}">
                                    {{ $errors->first('kondisi') }}</p>
                            </div>

                            <div>
                                <label class="block text-sm font-bold text-[var(--color-text-primary)]">
                                    Status Barang
                                </label>
                                <div class="mt-2 flex flex-wrap gap-2.5">
                                    @php
                                        $statusList = [
                                            \App\Models\Product::STATUS_DIJUAL => 'Dijual',
                                            \App\Models\Product::STATUS_DRAFT => 'Draft',
                                            \App\Models\Product::STATUS_TERJUAL => 'Terjual',
                                        ];
                                        $selectedStatus = old('status', $product->status);
                                    @endphp

                                    @foreach ($statusList as $val => $label)
                                        <label class="cursor-pointer">
                                            <input type="radio" name="status" value="{{ $val }}" class="peer hidden"
                                                {{ $selectedStatus === $val ? 'checked' : '' }}>
                                            <span
                                                class="inline-block rounded-full border border-[var(--color-border-default)] bg-[var(--color-surface-page)] px-5 py-2 text-xs font-semibold text-[var(--color-text-primary)] transition peer-checked:border-[var(--color-lime)] peer-checked:bg-[var(--color-lime)] peer-checked:text-[var(--color-charcoal)] peer-checked:shadow-sm">
                                                {{ $label }}
                                            </span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>

                            <div>
                                <label for="harga" class="block text-sm font-bold text-[var(--color-text-primary)]">
                                    Harga
                                </label>
                                <div class="relative mt-2">
                                    <span
                                        class="absolute inset-y-0 left-0 flex items-center pl-4 text-sm font-semibold text-[var(--color-text-primary)]">
                                        Rp.
                                    </span>
                                    <input type="number" id="harga" name="harga"
                                        value="{{ old('harga', $product->harga) }}" required min="0" step="500"
                                        placeholder="Masukkan harga..."
                                        class="w-full rounded-xl border border-[var(--color-border-default)] bg-[var(--color-surface-page)] py-3 pl-12 pr-4 text-sm text-[var(--color-text-primary)] placeholder-[var(--color-text-muted)] outline-none transition focus:border-[var(--color-lime)] focus:bg-white focus:ring-1 focus:ring-[var(--color-lime)]">
                                </div>
                                <p id="err-harga"
                                    class="mt-1 text-xs text-[var(--color-error-base)] {{ $errors->has('harga') ? '' : 'hidden' }}">
                                    {{ $errors->first('harga') }}</p>
                            </div>

                            <div>
                                <label for="deskripsi" class="block text-sm font-bold text-[var(--color-text-primary)]">
                                    Deskripsi
                                </label>
                                <textarea id="deskripsi" name="deskripsi" rows="4" required
                                    placeholder="Jelaskan kondisi, ukuran, kelengkapan, kekurangan, atau hal lain yang perlu diketahui pembeli..."
                                    class="mt-2 w-full rounded-xl border border-[var(--color-border-default)] bg-[var(--color-surface-page)] p-4 text-sm text-[var(--color-text-primary)] placeholder-[var(--color-text-muted)] outline-none transition focus:border-[var(--color-lime)] focus:bg-white focus:ring-1 focus:ring-[var(--color-lime)]">{{ old('deskripsi', $product->deskripsi) }}</textarea>
                                <p id="err-deskripsi"
                                    class="mt-1 text-xs text-[var(--color-error-base)] {{ $errors->has('deskripsi') ? '' : 'hidden' }}">
                                    {{ $errors->first('deskripsi') }}</p>
                            </div>

                            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                {{-- 1. Lokasi COD / Titik Temu (Google Maps Autocomplete + Live GPS) --}}
                                <div>
                                    <div class="flex items-center justify-between">
                                        <label for="location_search"
                                            class="block text-xs font-bold text-[var(--color-text-primary)]">
                                            Area / Titik COD
                                        </label>
                                        <button type="button" id="btn-live-location"
                                            class="inline-flex items-center gap-1 text-[11px] font-bold text-lime-700 hover:underline cursor-pointer">
                                            <svg class="size-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                                stroke-width="2">
                                                <circle cx="12" cy="12" r="3" />
                                                <path d="M12 2v3m0 14v3M2 12h3m14 0h3" />
                                            </svg>
                                            <span>Lokasi saat ini</span>
                                        </button>
                                    </div>

                                    <div class="relative mt-1">
                                        <span
                                            class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-neutral-400">
                                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                                stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                            </svg>
                                        </span>

                                        <input type="text" id="location_search" name="cod_place_name"
                                            value="{{ old('cod_place_name', $defaultPlace) }}" required
                                            autocomplete="off" placeholder="Cari tempat COD di Maps..."
                                            class="w-full rounded-xl border border-[var(--color-border-default)] bg-[var(--color-surface-page)] py-2.5 pl-9 pr-3 text-xs text-[var(--color-text-primary)] placeholder-[var(--color-text-muted)] outline-none transition focus:border-[var(--color-lime)] focus:bg-white">

                                        <input type="hidden" id="kota" name="kota"
                                            value="{{ old('kota', $product->kota) }}">
                                        <input type="hidden" id="province" name="province"
                                            value="{{ old('province', $defaultProvince) }}">
                                        <input type="hidden" id="cod_lat" name="cod_lat"
                                            value="{{ old('cod_lat', $defaultLat) }}">
                                        <input type="hidden" id="cod_lng" name="cod_lng"
                                            value="{{ old('cod_lng', $defaultLng) }}">
                                    </div>

                                    <p id="location-status" class="mt-1 hidden text-[11px] font-medium text-neutral-500">
                                    </p>
                                    <p id="err-kota"
                                        class="mt-1 text-xs text-[var(--color-error-base)] {{ $errors->has('kota') ? '' : 'hidden' }}">
                                        {{ $errors->first('kota') }}</p>
                                </div>

                                {{-- 2. Patokan Lokasi (Teks Bebas) --}}
                                <div>
                                    <label for="patokan_lokasi"
                                        class="block text-xs font-bold text-[var(--color-text-primary)]">
                                        Patokan Lokasi <span
                                            class="text-[10px] font-normal text-neutral-400">(Opsional)</span>
                                    </label>
                                    <input type="text" id="patokan_lokasi" name="patokan_lokasi"
                                        value="{{ old('patokan_lokasi', $product->patokan_lokasi) }}"
                                        placeholder="Contoh: Depan Gd. H. Oemar Said"
                                        class="mt-1 w-full rounded-xl border border-[var(--color-border-default)] bg-[var(--color-surface-page)] px-3 py-2.5 text-xs text-[var(--color-text-primary)] placeholder-[var(--color-text-muted)] outline-none transition focus:border-[var(--color-lime)] focus:bg-white">
                                    <p id="err-patokan_lokasi"
                                        class="mt-1 text-xs text-[var(--color-error-base)] {{ $errors->has('patokan_lokasi') ? '' : 'hidden' }}">
                                        {{ $errors->first('patokan_lokasi') }}</p>
                                </div>
                            </div>

                            <p id="formError"
                                class="hidden rounded-xl border border-[var(--color-error-base)] bg-red-50 p-3 text-xs font-semibold text-[var(--color-error-base)]">
                            </p>

                            <div class="pt-2">
                                <button type="submit"
                                    class="w-full rounded-2xl bg-[var(--color-lime)] py-3.5 text-sm font-extrabold text-[var(--color-charcoal)] shadow-md transition hover:bg-[#a8c942] active:scale-[0.99] cursor-pointer">
                                    Simpan Perubahan
                                </button>
                            </div>

                        </div>
                    </div>
                </div>

            </div>
        </form>
    </div>
@endsection