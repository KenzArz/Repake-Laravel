@extends('layouts.app')

@section('title', 'Edit Profil - RePake')

@section('content')
<div class="mx-auto max-w-xl px-4 py-4 md:px-8">

    {{-- Top App Bar Edit Profil --}}
    <div class="mb-6 flex items-center justify-between border-b border-neutral-200 pb-3">
        <div class="flex items-center gap-3">
            <a href="{{ route('profile.catalog') }}" class="grid size-9 place-items-center rounded-full text-neutral-800 transition hover:bg-neutral-200" aria-label="Kembali">
                <svg viewBox="0 0 24 24" class="size-6 fill-none stroke-current stroke-2 [stroke-linecap:round] [stroke-linejoin:round]">
                    <path d="M19 12H5M12 19l-7-7 7-7"/>
                </svg>
            </a>
            <h1 class="text-h4 font-bold text-neutral-900">Edit Profil</h1>
        </div>

        <button type="submit" form="profile-form" class="text-label font-bold text-neutral-900 transition hover:text-lime-700">
            Simpan
        </button>
    </div>

    {{-- Error validation alerts --}}
    @if ($errors->any())
        <div class="mb-4 rounded-xl bg-red-50 border border-red-200 p-4 text-xs text-red-700">
            <ul class="list-disc pl-4 space-y-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Form Edit Profil --}}
    <form id="profile-form" action="{{ route('profile.update') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
        @csrf
        @method('PUT')

        {{-- Foto Profil & Ganti Foto --}}
        <div class="flex flex-col items-center">
            <div class="relative size-28">
                <div class="size-28 overflow-hidden rounded-full border-4 border-white bg-neutral-200 shadow-md grid place-items-center">
                    <img id="avatar-preview" src="{{ !empty($profile['avatar']) ? asset($profile['avatar']) : '' }}"
                         alt="{{ $profile['name'] }}"
                         class="size-full object-cover {{ empty($profile['avatar']) ? 'hidden' : '' }}">

                    <div id="avatar-placeholder" class="{{ !empty($profile['avatar']) ? 'hidden' : '' }} grid place-items-center text-neutral-400">
                        <svg viewBox="0 0 24 24" class="size-16 fill-neutral-400">
                            <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
                        </svg>
                    </div>
                </div>

                {{-- Tombol Kamera Floating --}}
                <label for="avatar-input" class="absolute bottom-0 right-0 grid size-8 cursor-pointer place-items-center rounded-full bg-lime-500 text-neutral-900 shadow-md transition hover:bg-lime-600" title="Ganti Foto">
                    <svg viewBox="0 0 24 24" class="size-4.5 fill-none stroke-current stroke-2 [stroke-linecap:round] [stroke-linejoin:round]">
                        <path d="M14.5 4h-5L7 7H4a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2h-3l-2.5-3z"/>
                        <circle cx="12" cy="13" r="3"/>
                    </svg>
                </label>
                <input type="file" id="avatar-input" name="avatar" class="hidden" accept="image/*">
            </div>

            <label for="avatar-input" class="mt-2.5 cursor-pointer text-caption font-bold text-neutral-900 hover:underline">
                Ganti Foto Profil
            </label>
        </div>

        {{-- Kartu Input Fields --}}
        <div class="rounded-2xl border border-neutral-200 bg-white p-5 shadow-sm space-y-4">
            {{-- Nama Lengkap / Username --}}
            <div>
                <label for="name" class="mb-1.5 block text-xs font-bold text-neutral-800">
                    Nama Lengkap / Username
                </label>
                <div class="relative flex items-center">
                    <svg viewBox="0 0 24 24" class="absolute left-3.5 size-5 stroke-neutral-400 stroke-2 fill-none" aria-hidden="true">
                        <path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>
                    </svg>
                    <input type="text" id="name" name="name" value="{{ old('name', $profile['name']) }}"
                           class="w-full rounded-xl border border-neutral-300 bg-neutral-50/70 py-3 pl-11 pr-4 text-sm font-medium text-neutral-900 transition focus:border-neutral-900 focus:bg-white focus:outline-none" required>
                </div>
            </div>

            {{-- Email (dari Google Account) --}}
            <div>
                <label class="mb-1.5 block text-xs font-bold text-neutral-800">
                    Email (dari Google Account)
                </label>
                <div class="relative flex items-center">
                    <svg viewBox="0 0 24 24" class="absolute left-3.5 size-5 stroke-neutral-400 stroke-2 fill-none" aria-hidden="true">
                        <rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/>
                    </svg>
                    <input type="email" value="{{ $profile['email'] }}" disabled
                           class="w-full cursor-not-allowed rounded-xl border border-neutral-300 bg-neutral-100 py-3 pl-11 pr-11 text-sm font-medium text-neutral-500">
                    <svg viewBox="0 0 24 24" class="absolute right-3.5 size-4.5 stroke-neutral-400 stroke-2 fill-none" aria-hidden="true">
                        <rect width="18" height="11" x="3" y="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                    </svg>
                </div>
            </div>

            {{-- Nomor WhatsApp / Telepon --}}
            <div>
                <label for="phone" class="mb-1.5 block text-xs font-bold text-neutral-800">
                    Nomor WhatsApp / Telepon
                </label>
                <div class="relative flex items-center">
                    <svg viewBox="0 0 24 24" class="absolute left-3.5 size-5 stroke-neutral-400 stroke-2 fill-none" aria-hidden="true">
                        <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/>
                    </svg>
                    <input type="text" id="phone" name="phone" value="{{ old('phone', $profile['phone']) }}"
                           placeholder="Contoh: 0812-3456-7890"
                           class="w-full rounded-xl border border-neutral-300 bg-neutral-50/70 py-3 pl-11 pr-4 text-sm font-medium text-neutral-900 transition focus:border-neutral-900 focus:bg-white focus:outline-none">
                </div>
            </div>

            {{-- Lokasi / Asal Kampus / Domisili --}}
            <div>
                <label for="kota" class="mb-1.5 block text-xs font-bold text-neutral-800">
                    Lokasi / Asal Kampus / Domisili
                </label>
                <div class="relative flex items-center">
                    <svg viewBox="0 0 24 24" class="absolute left-3.5 size-5 stroke-neutral-400 stroke-2 fill-none" aria-hidden="true">
                        <path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/>
                    </svg>
                    <input type="text" id="kota" name="kota" value="{{ old('kota', $profile['kota']) }}"
                           placeholder="Contoh: Bekasi / Karawang"
                           class="w-full rounded-xl border border-neutral-300 bg-neutral-50/70 py-3 pl-11 pr-4 text-sm font-medium text-neutral-900 transition focus:border-neutral-900 focus:bg-white focus:outline-none">
                </div>
            </div>

            {{-- Bio / Deskripsi Toko --}}
            <div>
                <label for="bio" class="mb-1.5 block text-xs font-bold text-neutral-800">
                    Bio / Deskripsi Toko
                </label>
                <div class="relative">
                    <svg viewBox="0 0 24 24" class="absolute left-3.5 top-3.5 size-5 stroke-neutral-400 stroke-2 fill-none" aria-hidden="true">
                        <line x1="4" x2="20" y1="6" y2="6"/><line x1="4" x2="20" y1="12" y2="12"/><line x1="4" x2="14" y1="18" y2="18"/>
                    </svg>
                    <textarea id="bio" name="bio" rows="3"
                              placeholder="Deskripsikan diri atau toko Anda..."
                              class="w-full rounded-xl border border-neutral-300 bg-neutral-50/70 py-3 pl-11 pr-4 text-sm font-medium text-neutral-900 transition focus:border-neutral-900 focus:bg-white focus:outline-none">{{ old('bio', $profile['bio']) }}</textarea>
                </div>
            </div>
        </div>

        {{-- Tombol Aksi Bawah --}}
        <div class="space-y-3 pt-2">
            <button type="submit" class="w-full rounded-xl bg-lime-500 py-3.5 text-center text-label font-bold text-neutral-900 shadow-sm transition hover:bg-lime-600">
                Simpan Perubahan
            </button>

            <button type="button" onclick="document.getElementById('logout-form').submit();"
                    class="flex w-full items-center justify-center gap-2 rounded-xl border border-red-500 bg-white py-3.5 text-label font-bold text-red-600 shadow-sm transition hover:bg-red-50">
                <svg viewBox="0 0 24 24" class="size-5 stroke-current stroke-2 fill-none">
                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9"/>
                </svg>
                <span>Keluar dari Akun</span>
            </button>
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
