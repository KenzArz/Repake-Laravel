@extends('layouts.app')

@section('title', 'Edit Profil - RePake')

@section('content')
<div class="mx-auto max-w-6xl px-4 py-6 sm:px-6 lg:px-8">

    {{-- Breadcrumb Navigation (Desktop) --}}
    <nav class="mb-6 hidden items-center gap-2 text-xs text-neutral-500 md:flex" aria-label="Breadcrumb">
        <a href="{{ route('home') }}" class="hover:text-neutral-900 transition">Beranda</a>
        <span>/</span>
        <a href="{{ route('profile.catalog') }}" class="hover:text-neutral-900 transition">Profil &amp; Katalog</a>
        <span>/</span>
        <span class="text-neutral-900 font-semibold">Edit Profil</span>
    </nav>

    {{-- Header Halaman --}}
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4 border-b border-neutral-200 pb-4">
        <div>
            <h1 class="text-2xl font-black tracking-tight text-neutral-900">Pengaturan Profil</h1>
            <p class="mt-1 text-xs text-neutral-500">Perbarui identitas profil dan detail toko Anda agar pembeli lebih percaya.</p>
        </div>

        <div class="flex items-center gap-3">
            <a href="{{ route('profile.catalog') }}"
               class="rounded-xl border border-neutral-300 bg-white px-4 py-2.5 text-xs font-semibold text-neutral-700 transition hover:bg-neutral-100 shadow-2xs">
                Kembali ke Profil
            </a>
            <button type="submit" form="profile-form"
                    class="rounded-xl bg-lime-400 px-5 py-2.5 text-xs font-bold text-neutral-900 shadow-2xs transition hover:bg-lime-500 cursor-pointer">
                Simpan Perubahan
            </button>
        </div>
    </div>

    {{-- Alert Error Validasi Global --}}
    @if ($errors->any())
        <div class="mb-6 rounded-2xl bg-red-50 border border-red-200 p-4 text-xs text-red-700 shadow-2xs">
            <div class="flex items-center gap-2 font-bold mb-1.5">
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

        {{-- Hidden input untuk menandai hapus foto --}}
        <input type="hidden" name="remove_avatar" id="remove-avatar-input" value="0">

        <div class="grid grid-cols-1 gap-8 lg:grid-cols-12">

            {{-- Kolom Kiri: Foto Avatar & Panduan (Col-span 4 pada Desktop) --}}
            <div class="lg:col-span-4">
                <div class="rounded-3xl border border-neutral-200 bg-white p-6 shadow-2xs text-center">
                    <h3 class="text-sm font-bold text-neutral-900 mb-4">Foto Profil</h3>

                    {{-- Avatar Container --}}
                    <div class="relative mx-auto size-32">
                        <div class="size-full overflow-hidden rounded-full border-4 border-neutral-100 bg-neutral-200 shadow-inner grid place-items-center">
                            @php
                                $hasAvatar = !empty($profile['avatar_url']);
                            @endphp

                            {{-- Gambar Foto Profil Aktif / Preview / Siluet Default --}}
                            <img id="avatar-preview"
                                 src="{{ $hasAvatar ? $profile['avatar_url'] : asset('images/default-avatar.png') }}"
                                 alt="{{ $profile['name'] ?? 'Profil' }}"
                                 class="size-full object-cover">
                        </div>

                        {{-- Floating Camera Button --}}
                        <label for="avatar-input"
                               class="absolute bottom-1 right-1 grid size-9 cursor-pointer place-items-center rounded-full bg-lime-400 text-neutral-900 shadow-md transition hover:bg-lime-500 hover:scale-105"
                               title="Pilih Foto Baru">
                            <svg viewBox="0 0 24 24" class="size-4.5 fill-none stroke-current stroke-2 [stroke-linecap:round] [stroke-linejoin:round]">
                                <path d="M14.5 4h-5L7 7H4a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2h-3l-2.5-3z"/>
                                <circle cx="12" cy="13" r="3"/>
                            </svg>
                        </label>
                        <input type="file" id="avatar-input" name="avatar" class="hidden" accept="image/jpeg,image/png,image/jpg,image/webp">
                    </div>

                    {{-- Tombol Unggah & Hapus Foto --}}
                    <div class="mt-4 flex items-center justify-center gap-2">
                        <label for="avatar-input"
                               class="inline-block cursor-pointer rounded-xl bg-neutral-100 px-3.5 py-1.5 text-xs font-bold text-neutral-800 transition hover:bg-neutral-200">
                            Pilih Foto Baru
                        </label>
                        <button type="button" id="btn-remove-avatar"
                                class="inline-block rounded-xl border border-neutral-200 px-3 py-1.5 text-xs font-semibold text-neutral-500 transition hover:bg-red-50 hover:text-red-600 hover:border-red-200 cursor-pointer">
                            Hapus
                        </button>
                    </div>

                    @error('avatar')
                        <p class="mt-2 text-xs font-semibold text-red-600">{{ $message }}</p>
                    @enderror

                    <p class="mt-4 text-[11px] leading-relaxed text-neutral-500 text-left rounded-2xl bg-neutral-50 border border-neutral-200/80 p-3.5">
                        💡 <strong>Tips:</strong> Disarankan foto beresolusi persegi rasio 1:1 (format JPG, PNG, atau WEBP) maksimal 2 MB agar terlihat tajam di katalog barang.
                    </p>

                    {{-- Info Status Akun --}}
                    <div class="mt-4 border-t border-neutral-100 pt-4 text-left space-y-2 text-xs">
                        <div class="flex items-center justify-between text-neutral-600">
                            <span>Status Akun:</span>
                            <span class="inline-flex items-center gap-1 font-bold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-full border border-emerald-200">
                                <svg viewBox="0 0 24 24" class="size-3 fill-current"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/></svg>
                                Mahasiswa UNSIKA
                            </span>
                        </div>
                        <div class="flex items-center justify-between text-neutral-600">
                            <span>Email SSO:</span>
                            <span class="font-medium text-neutral-800 truncate max-w-[140px]">{{ $profile['email'] ?? 'Akun UNSIKA' }}</span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Kolom Kanan: Formulir Input Data Profil (Col-span 8 pada Desktop) --}}
            <div class="lg:col-span-8">
                <div class="rounded-3xl border border-neutral-200 bg-white p-6 sm:p-8 shadow-2xs">
                    <h3 class="text-base font-bold text-neutral-900 border-b border-neutral-100 pb-3 mb-6">
                        Informasi Profil &amp; Toko
                    </h3>

                    <div class="grid grid-cols-1 gap-5 md:grid-cols-2">

                        {{-- Nama Lengkap (Col 1) --}}
                        <div>
                            <label for="name" class="mb-1.5 block text-xs font-bold text-neutral-800">
                                Nama Lengkap <span class="text-red-500">*</span>
                            </label>
                            <div class="relative flex items-center">
                                <svg viewBox="0 0 24 24" class="absolute left-3.5 size-4.5 stroke-neutral-400 stroke-2 fill-none" aria-hidden="true">
                                    <path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>
                                </svg>
                                <input type="text" id="name" name="name"
                                       value="{{ old('name', $profile['name'] ?? '') }}"
                                       class="w-full rounded-xl border {{ $errors->has('name') ? 'border-red-400 focus:border-red-600' : 'border-neutral-300 focus:border-neutral-900' }} bg-neutral-50/60 py-3 pl-11 pr-4 text-sm font-medium text-neutral-900 transition focus:bg-white focus:outline-none"
                                       required>
                            </div>
                            @error('name')
                                <p class="mt-1 text-xs text-red-600 font-medium">{{ $message }}</p>
                            @else
                                <span class="mt-1 block text-[11px] text-neutral-500">Nama lengkap yang tampil di kartu penjual.</span>
                            @enderror
                        </div>

                        {{-- Username (@handle) (Col 2) --}}
                        <div>
                            <label for="username" class="mb-1.5 block text-xs font-bold text-neutral-800">
                                Username (Handle Toko)
                            </label>
                            <div class="relative flex items-center">
                                <span class="absolute left-3.5 text-sm font-bold text-neutral-400">@</span>
                                <input type="text" id="username" name="username"
                                       value="{{ old('username', $profile['username'] ?? 'midou_kun') }}"
                                       placeholder="midou_kun"
                                       class="w-full rounded-xl border {{ $errors->has('username') ? 'border-red-400 focus:border-red-600' : 'border-neutral-300 focus:border-neutral-900' }} bg-neutral-50/60 py-3 pl-9 pr-4 text-sm font-medium text-neutral-900 transition focus:bg-white focus:outline-none">
                            </div>
                            @error('username')
                                <p class="mt-1 text-xs text-red-600 font-medium">{{ $message }}</p>
                            @else
                                <span class="mt-1 block text-[11px] text-neutral-500">Ditampilkan sebagai @handle di profil Anda.</span>
                            @enderror
                        </div>

                        {{-- Email Google (Span 2, Readonly) --}}
                        <div class="md:col-span-2">
                            <label class="mb-1.5 block text-xs font-bold text-neutral-800">
                                Email UNSIKA (Terhubung dengan Google SSO)
                            </label>
                            <div class="relative flex items-center">
                                <svg viewBox="0 0 24 24" class="absolute left-3.5 size-4.5 stroke-neutral-400 stroke-2 fill-none" aria-hidden="true">
                                    <rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/>
                                </svg>
                                <input type="email" value="{{ $profile['email'] ?? 'student@student.unsika.ac.id' }}" disabled
                                       class="w-full cursor-not-allowed rounded-xl border border-neutral-300 bg-neutral-100 py-3 pl-11 pr-20 text-sm font-medium text-neutral-600">
                                <div class="absolute right-3.5 flex items-center gap-1.5 text-xs text-neutral-500">
                                    <span class="rounded bg-neutral-200 px-2 py-0.5 text-[10px] font-bold text-neutral-700">Terkunci</span>
                                </div>
                            </div>
                            <span class="mt-1 block text-[11px] text-neutral-500">Email diatur secara otomatis melalui SSO Google Kampus.</span>
                        </div>

                        {{-- Nomor Telepon / WhatsApp (Col 1) --}}
                        <div>
                            <label for="phone" class="mb-1.5 block text-xs font-bold text-neutral-800">
                                Nomor WhatsApp / Telepon
                            </label>
                            <div class="relative flex items-center">
                                <svg viewBox="0 0 24 24" class="absolute left-3.5 size-4.5 stroke-neutral-400 stroke-2 fill-none" aria-hidden="true">
                                    <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/>
                                </svg>
                                <input type="text" id="phone" name="phone"
                                       value="{{ old('phone', $profile['phone'] ?? '') }}"
                                       placeholder="Contoh: 0812-3456-7890"
                                       class="w-full rounded-xl border {{ $errors->has('phone') ? 'border-red-400 focus:border-red-600' : 'border-neutral-300 focus:border-neutral-900' }} bg-neutral-50/60 py-3 pl-11 pr-4 text-sm font-medium text-neutral-900 transition focus:bg-white focus:outline-none">
                            </div>
                            @error('phone')
                                <p class="mt-1 text-xs text-red-600 font-medium">{{ $message }}</p>
                            @else
                                <span class="mt-1 block text-[11px] text-neutral-500">Untuk koordinasi COD dengan calon pembeli.</span>
                            @enderror
                        </div>

                        {{-- Lokasi / Kampus / Domisili (Col 2) --}}
                        <div>
                            <label for="kota" class="mb-1.5 block text-xs font-bold text-neutral-800">
                                Lokasi / Kampus / Domisili
                            </label>
                            <div class="relative flex items-center">
                                <svg viewBox="0 0 24 24" class="absolute left-3.5 size-4.5 stroke-neutral-400 stroke-2 fill-none" aria-hidden="true">
                                    <path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/>
                                </svg>
                                <input type="text" id="kota" name="kota"
                                       value="{{ old('kota', $profile['kota'] ?? '') }}"
                                       placeholder="Contoh: Telukjambe / Kampus 1 UNSIKA"
                                       class="w-full rounded-xl border {{ $errors->has('kota') ? 'border-red-400 focus:border-red-600' : 'border-neutral-300 focus:border-neutral-900' }} bg-neutral-50/60 py-3 pl-11 pr-4 text-sm font-medium text-neutral-900 transition focus:bg-white focus:outline-none">
                            </div>
                            @error('kota')
                                <p class="mt-1 text-xs text-red-600 font-medium">{{ $message }}</p>
                            @else
                                <span class="mt-1 block text-[11px] text-neutral-500">Lokasi default barang yang Anda jual.</span>
                            @enderror
                        </div>

                        {{-- Bio / Deskripsi Toko (Span 2) --}}
                        <div class="md:col-span-2">
                            <label for="bio" class="mb-1.5 block text-xs font-bold text-neutral-800">
                                Bio / Deskripsi Toko
                            </label>
                            <div class="relative">
                                <textarea id="bio" name="bio" rows="3"
                                          placeholder="Ceritakan tentang diri Anda atau barang yang Anda jual..."
                                          class="w-full rounded-xl border {{ $errors->has('bio') ? 'border-red-400 focus:border-red-600' : 'border-neutral-300 focus:border-neutral-900' }} bg-neutral-50/60 p-4 text-sm font-medium text-neutral-900 transition focus:bg-white focus:outline-none leading-relaxed">{{ old('bio', $profile['bio'] ?? '') }}</textarea>
                            </div>
                            @error('bio')
                                <p class="mt-1 text-xs text-red-600 font-medium">{{ $message }}</p>
                            @else
                                <span class="mt-1 block text-[11px] text-neutral-500">Maksimal 500 karakter. Ditampilkan tepat di bawah nama profil Anda.</span>
                            @enderror
                        </div>

                    </div>

                    {{-- Tombol Aksi Form Bawah --}}
                    <div class="mt-8 flex flex-wrap items-center justify-between gap-4 border-t border-neutral-100 pt-6">
                        <div class="flex items-center gap-3">
                            <button type="submit"
                                    class="rounded-xl bg-lime-400 px-6 py-2.5 text-xs font-bold text-neutral-900 shadow-2xs transition hover:bg-lime-500 cursor-pointer">
                                Simpan Perubahan
                            </button>
                            <a href="{{ route('profile.catalog') }}"
                               class="rounded-xl border border-neutral-300 bg-white px-5 py-2.5 text-xs font-semibold text-neutral-700 transition hover:bg-neutral-100">
                                Batal
                            </a>
                        </div>

                        <button type="button" onclick="document.getElementById('logout-form').submit();"
                                class="inline-flex items-center gap-1.5 rounded-xl border border-red-200 bg-red-50/60 px-4 py-2.5 text-xs font-bold text-red-600 transition hover:bg-red-100 cursor-pointer">
                            <svg viewBox="0 0 24 24" class="size-4 stroke-current stroke-2 fill-none"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                            <span>Keluar dari Akun</span>
                        </button>
                    </div>

                </div>
            </div>

        </div>
    </form>

    {{-- Form Logout Tersembunyi --}}
    <form id="logout-form" action="{{ route('logout') }}" method="POST" class="hidden">
        @csrf
    </form>

</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const avatarInput = document.getElementById('avatar-input');
    const avatarPreview = document.getElementById('avatar-preview');
    const avatarInitial = document.getElementById('avatar-initial');
    const removeInput = document.getElementById('remove-avatar-input');
    const btnRemove = document.getElementById('btn-remove-avatar');

    // Live preview saat memilih gambar
    if (avatarInput) {
        avatarInput.addEventListener('change', function(e) {
            const file = e.target.files && e.target.files[0];
            if (file) {
                // Validasi ukuran frontend (2MB)
                if (file.size > 2 * 1024 * 1024) {
                    alert('Ukuran foto melebihi 2 MB. Silakan pilih foto yang lebih kecil.');
                    avatarInput.value = '';
                    return;
                }

                const reader = new FileReader();
                reader.onload = function(evt) {
                    if (avatarPreview) {
                        avatarPreview.src = evt.target.result;
                    }
                    if (removeInput) {
                        removeInput.value = '0';
                    }
                };
                reader.readAsDataURL(file);
            }
        });
    }

    // Tombol Hapus Foto -> Kembalikan ke Siluet Default
    if (btnRemove) {
        btnRemove.addEventListener('click', function () {
            if (avatarPreview) {
                avatarPreview.src = "{{ asset('images/default-avatar.png') }}";
            }
            if (avatarInput) {
                avatarInput.value = '';
            }
            if (removeInput) {
                removeInput.value = '1';
            }
        });
    }
});
</script>
@endpush
