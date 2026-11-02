@extends('layouts.app')

@section('title', 'Edit Profil - RePake')

@section('content')
<div class="mx-auto max-w-6xl px-4 py-6 sm:px-6 lg:px-8">

    {{-- Breadcrumb Navigation (Desktop) --}}
    <nav class="mb-6 hidden items-center gap-2 text-xs text-neutral-500 md:flex" aria-label="Breadcrumb">
        <a href="{{ route('home') }}" class="hover:text-neutral-900 transition">Beranda</a>
        <span>/</span>
        <a href="{{ route('profile.catalog') }}" class="hover:text-neutral-900 transition">Profil & Katalog</a>
        <span>/</span>
        <span class="text-neutral-900 font-semibold">Edit Profil</span>
    </nav>

    {{-- Header Halaman --}}
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4 border-b border-neutral-200 pb-4">
        <div>
            <h1 class="text-2xl font-extrabold text-neutral-900">Pengaturan Profil</h1>
            <p class="mt-1 text-xs text-neutral-500">Perbarui identitas profil dan detail toko Anda agar pembeli lebih percaya.</p>
        </div>

        <div class="flex items-center gap-3">
            <a href="{{ route('profile.catalog') }}"
               class="rounded-xl border border-neutral-300 bg-white px-4 py-2 text-xs font-semibold text-neutral-700 transition hover:bg-neutral-100">
                Kembali ke Profil
            </a>
            <button type="submit" form="profile-form"
                    class="rounded-xl bg-lime-500 px-5 py-2 text-xs font-bold text-neutral-900 shadow-sm transition hover:bg-lime-600">
                Simpan Perubahan
            </button>
        </div>
    </div>

    {{-- Error validation alerts --}}
    @if ($errors->any())
        <div class="mb-6 rounded-2xl bg-red-50 border border-red-200 p-4 text-xs text-red-700 shadow-sm">
            <div class="flex items-center gap-2 font-bold mb-1">
                <svg viewBox="0 0 24 24" class="size-4 fill-current"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                <span>Mohon periksa kembali formulir Anda:</span>
            </div>
            <ul class="list-disc pl-6 space-y-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Form Edit Profil (2-Column Desktop Grid) --}}
    <form id="profile-form" action="{{ route('profile.update') }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 gap-8 lg:grid-cols-12">

            {{-- Kolom Kiri: Foto Avatar & Panduan (Col-span 4 pada Desktop) --}}
            <div class="lg:col-span-4">
                <div class="rounded-3xl border border-neutral-200 bg-white p-6 shadow-sm text-center">
                    <h3 class="text-sm font-bold text-neutral-900 mb-4">Foto Profil</h3>

                    {{-- Avatar Container --}}
                    <div class="relative mx-auto size-32">
                        <div class="size-full overflow-hidden rounded-full border-4 border-neutral-100 bg-neutral-200 shadow-inner grid place-items-center">
                            <img id="avatar-preview" src="{{ !empty($profile['avatar']) ? asset($profile['avatar']) : '' }}"
                                 alt="{{ $profile['name'] }}"
                                 class="size-full object-cover {{ empty($profile['avatar']) ? 'hidden' : '' }}">

                            <div id="avatar-placeholder" class="{{ !empty($profile['avatar']) ? 'hidden' : '' }} grid place-items-center text-neutral-400">
                                <svg viewBox="0 0 24 24" class="size-20 fill-neutral-400">
                                    <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
                                </svg>
                            </div>
                        </div>

                        {{-- Floating Camera Button --}}
                        <label for="avatar-input"
                               class="absolute bottom-1 right-1 grid size-9 cursor-pointer place-items-center rounded-full bg-lime-500 text-neutral-900 shadow-md transition hover:bg-lime-600 hover:scale-105"
                               title="Pilih Foto Baru">
                            <svg viewBox="0 0 24 24" class="size-4.5 fill-none stroke-current stroke-2 [stroke-linecap:round] [stroke-linejoin:round]">
                                <path d="M14.5 4h-5L7 7H4a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2h-3l-2.5-3z"/>
                                <circle cx="12" cy="13" r="3"/>
                            </svg>
                        </label>
                        <input type="file" id="avatar-input" name="avatar" class="hidden" accept="image/*">
                    </div>

                    <label for="avatar-input"
                           class="mt-3.5 inline-block cursor-pointer rounded-xl bg-neutral-100 px-4 py-1.5 text-xs font-bold text-neutral-800 transition hover:bg-neutral-200">
                        Unggah Foto Baru
                    </label>

                    <p class="mt-4 text-[11px] leading-relaxed text-neutral-500 text-left rounded-2xl bg-neutral-50 border border-neutral-200/80 p-3.5">
                        💡 <strong>Tips:</strong> Disarankan menggunakan foto persegi rasio 1:1 (JPG, PNG, atau WEBP) dengan ukuran maksimal 2 MB agar terlihat tajam di layar PC dan smartphone.
                    </p>

                    {{-- Info Status Akun --}}
                    <div class="mt-4 border-t border-neutral-100 pt-4 text-left space-y-2 text-xs">
                        <div class="flex items-center justify-between text-neutral-600">
                            <span>Status Akun:</span>
                            <span class="inline-flex items-center gap-1 font-bold text-emerald-600">
                                <svg viewBox="0 0 24 24" class="size-3.5 fill-current"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/></svg>
                                Terverifikasi
                            </span>
                        </div>
                        <div class="flex items-center justify-between text-neutral-600">
                            <span>Bergabung Sejak:</span>
                            <span class="font-medium text-neutral-800">Oktober 2026</span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Kolom Kanan: Formulir Input Data Profil (Col-span 8 pada Desktop) --}}
            <div class="lg:col-span-8">
                <div class="rounded-3xl border border-neutral-200 bg-white p-6 sm:p-8 shadow-sm">
                    <h3 class="text-base font-bold text-neutral-900 border-b border-neutral-100 pb-3 mb-6">
                        Data Pribadi & Toko
                    </h3>

                    <div class="grid grid-cols-1 gap-5 md:grid-cols-2">

                        {{-- Nama Lengkap / Username (Span 2) --}}
                        <div class="md:col-span-2">
                            <label for="name" class="mb-1.5 block text-xs font-bold text-neutral-800">
                                Nama Lengkap / Username <span class="text-red-500">*</span>
                            </label>
                            <div class="relative flex items-center">
                                <svg viewBox="0 0 24 24" class="absolute left-3.5 size-5 stroke-neutral-400 stroke-2 fill-none" aria-hidden="true">
                                    <path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>
                                </svg>
                                <input type="text" id="name" name="name" value="{{ old('name', $profile['name']) }}"
                                       class="w-full rounded-xl border border-neutral-300 bg-neutral-50/60 py-3 pl-11 pr-4 text-sm font-medium text-neutral-900 transition focus:border-neutral-900 focus:bg-white focus:outline-none" required>
                            </div>
                            <span class="mt-1 block text-[11px] text-neutral-500">Nama ini akan ditampilkan sebagai nama penjual di seluruh katalog barang Anda.</span>
                        </div>

                        {{-- Email Google (Span 2, Readonly) --}}
                        <div class="md:col-span-2">
                            <label class="mb-1.5 block text-xs font-bold text-neutral-800">
                                Email (Terhubung dengan Google Account)
                            </label>
                            <div class="relative flex items-center">
                                <svg viewBox="0 0 24 24" class="absolute left-3.5 size-5 stroke-neutral-400 stroke-2 fill-none" aria-hidden="true">
                                    <rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/>
                                </svg>
                                <input type="email" value="{{ $profile['email'] }}" disabled
                                       class="w-full cursor-not-allowed rounded-xl border border-neutral-300 bg-neutral-100 py-3 pl-11 pr-11 text-sm font-medium text-neutral-500">
                                <div class="absolute right-3.5 flex items-center gap-1.5 text-xs text-neutral-400" title="Email terikat dengan Google SSO">
                                    <span class="text-[11px]">Terkunci</span>
                                    <svg viewBox="0 0 24 24" class="size-4 stroke-neutral-400 stroke-2 fill-none"><rect width="18" height="11" x="3" y="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                                </div>
                            </div>
                        </div>

                        {{-- Nomor Telepon / WhatsApp (Col 1) --}}
                        <div>
                            <label for="phone" class="mb-1.5 block text-xs font-bold text-neutral-800">
                                Nomor WhatsApp / Telepon
                            </label>
                            <div class="relative flex items-center">
                                <svg viewBox="0 0 24 24" class="absolute left-3.5 size-5 stroke-neutral-400 stroke-2 fill-none" aria-hidden="true">
                                    <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/>
                                </svg>
                                <input type="text" id="phone" name="phone" value="{{ old('phone', $profile['phone']) }}"
                                       placeholder="0812-3456-7890"
                                       class="w-full rounded-xl border border-neutral-300 bg-neutral-50/60 py-3 pl-11 pr-4 text-sm font-medium text-neutral-900 transition focus:border-neutral-900 focus:bg-white focus:outline-none">
                            </div>
                        </div>

                        {{-- Lokasi / Kampus / Domisili (Col 2) --}}
                        <div>
                            <label for="kota" class="mb-1.5 block text-xs font-bold text-neutral-800">
                                Lokasi / Asal Kampus / Domisili
                            </label>
                            <div class="relative flex items-center">
                                <svg viewBox="0 0 24 24" class="absolute left-3.5 size-5 stroke-neutral-400 stroke-2 fill-none" aria-hidden="true">
                                    <path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/>
                                </svg>
                                <input type="text" id="kota" name="kota" value="{{ old('kota', $profile['kota']) }}"
                                       placeholder="Contoh: Bekasi / UNSIKA Karawang"
                                       class="w-full rounded-xl border border-neutral-300 bg-neutral-50/60 py-3 pl-11 pr-4 text-sm font-medium text-neutral-900 transition focus:border-neutral-900 focus:bg-white focus:outline-none">
                            </div>
                        </div>

                        {{-- Bio / Deskripsi Toko (Span 2) --}}
                        <div class="md:col-span-2">
                            <label for="bio" class="mb-1.5 block text-xs font-bold text-neutral-800">
                                Bio / Deskripsi Toko
                            </label>
                            <div class="relative">
                                <svg viewBox="0 0 24 24" class="absolute left-3.5 top-3.5 size-5 stroke-neutral-400 stroke-2 fill-none" aria-hidden="true">
                                    <line x1="4" x2="20" y1="6" y2="6"/><line x1="4" x2="20" y1="12" y2="12"/><line x1="4" x2="14" y1="18" y2="18"/>
                                </svg>
                                <textarea id="bio" name="bio" rows="4"
                                          placeholder="Ceritakan tentang diri Anda, reputasi penjualan, atau jenis barang yang sering Anda pasarkan..."
                                          class="w-full rounded-xl border border-neutral-300 bg-neutral-50/60 py-3 pl-11 pr-4 text-sm font-medium text-neutral-900 transition focus:border-neutral-900 focus:bg-white focus:outline-none leading-relaxed">{{ old('bio', $profile['bio']) }}</textarea>
                            </div>
                        </div>

                    </div>

                    {{-- Tombol Aksi Desktop Bawah --}}
                    <div class="mt-8 flex flex-wrap items-center justify-between gap-4 border-t border-neutral-100 pt-6">
                        <div class="flex items-center gap-3">
                            <button type="submit"
                                    class="rounded-xl bg-lime-500 px-6 py-3 text-sm font-bold text-neutral-900 shadow-sm transition hover:bg-lime-600">
                                Simpan Perubahan
                            </button>
                            <a href="{{ route('profile.catalog') }}"
                               class="rounded-xl border border-neutral-300 bg-white px-5 py-3 text-sm font-semibold text-neutral-700 transition hover:bg-neutral-100">
                                Batal
                            </a>
                        </div>

                        <button type="button" onclick="document.getElementById('logout-form').submit();"
                                class="inline-flex items-center gap-1.5 rounded-xl border border-red-200 bg-red-50/60 px-4 py-3 text-xs font-bold text-red-600 transition hover:bg-red-100">
                            <svg viewBox="0 0 24 24" class="size-4 stroke-current stroke-2 fill-none"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9"/></svg>
                            <span>Keluar dari Akun</span>
                        </button>
                    </div>

                </div>
            </div>

        </div>
    </form>

    {{-- Form Logout tersembunyi --}}
    <form id="logout-form" action="{{ route('logout') }}" method="POST" class="hidden">
        @csrf
    </form>

</div>
@endsection

@push('scripts')
<script>
    document.getElementById('avatar-input')?.addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(evt) {
                const preview = document.getElementById('avatar-preview');
                const placeholder = document.getElementById('avatar-placeholder');
                if (preview) {
                    preview.src = evt.target.result;
                    preview.classList.remove('hidden');
                }
                if (placeholder) {
                    placeholder.classList.add('hidden');
                }
            };
            reader.readAsDataURL(file);
        }
    });
</script>
@endpush

