@props([
    'profile',
    'isOwner' => true,
    'chatProducts' => collect(),
    'ulasanUrl' => '#ulasan',
])

<div class="relative rounded-2xl border border-neutral-200 bg-white p-4 sm:p-6 md:p-8 shadow-2xs">
    <div class="flex flex-col gap-4 sm:gap-5 md:flex-row md:items-center md:justify-between md:gap-6">
        {{-- Kiri: Avatar + Info User --}}
        <div class="flex items-start sm:items-center gap-3.5 sm:gap-5 min-w-0 {{ $isOwner ? 'pr-9 md:pr-0' : '' }}">
            <div
                class="size-16 sm:size-20 md:size-24 shrink-0 rounded-full bg-neutral-200 overflow-hidden border border-neutral-300 grid place-items-center select-none shadow-inner">
                <img src="{{ !empty($profile['avatar_url']) ? $profile['avatar_url'] : asset('images/default-avatar.png') }}"
                    alt="{{ $profile['name'] ?? 'Profil' }}" referrerpolicy="no-referrer"
                    onerror="this.onerror=null; this.src='{{ asset('images/default-avatar.png') }}';"
                    class="size-full object-cover">
            </div>

            <div class="min-w-0 flex-1">
                <h1
                    class="text-lg sm:text-xl md:text-2xl font-black tracking-tight text-neutral-900 break-words leading-snug">
                    {{ $profile['name'] ?? 'Midou Wijaya' }}
                </h1>

                <p class="text-xs font-bold text-neutral-800 truncate">
                    @<span>{{ $profile['username'] ?? 'midou_kun' }}</span>
                </p>

                <p class="mt-1 text-xs text-neutral-600 line-clamp-2 break-words">
                    {{ $profile['bio'] ?? 'Jual apa aja demi bisa nikahin hiura' }}
                </p>

                <p class="mt-2 flex items-center gap-1 text-xs text-neutral-600">
                    <svg viewBox="0 0 24 24"
                        class="size-3.5 shrink-0 fill-none stroke-current stroke-2 text-neutral-700">
                        <path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z" />
                        <circle cx="12" cy="10" r="3" />
                    </svg>
                    <span class="truncate">{{ $profile['kota'] ?? 'Bekasi' }}</span>
                </p>
            </div>
        </div>

        {{-- Kanan: Rating, 3 Dots Menu, & Tombol Aksi --}}
        <div
            class="flex flex-col sm:flex-row md:flex-col items-stretch sm:items-center md:items-end justify-between gap-3.5 sm:gap-4 border-t border-neutral-100 pt-3.5 md:border-t-0 md:pt-0 shrink-0">
            <div class="flex items-center justify-between md:justify-end gap-4">
                <div class="text-left md:text-right">
                    <a href="{{ $ulasanUrl }}" class="group block">
                        <p
                            class="flex flex-wrap items-center gap-1 text-xs sm:text-sm font-bold text-neutral-900 group-hover:text-lime-700 transition">
                            <span class="text-amber-500">★</span>
                            <span>{{ number_format($profile['rating'] ?? 4.5, 1) }}
                                ({{ $profile['reviews_count'] ?? 67 }} ulasan) · {{ $profile['sold_count'] ?? 10 }}
                                Terjual</span>
                        </p>
                        <p class="text-[11px] text-neutral-500 group-hover:underline">Rating &amp; Ulasan</p>
                    </a>
                </div>

                @if ($isOwner)
                    <div class="absolute right-3.5 top-3.5 sm:right-5 sm:top-5 md:static">
                        <div class="relative">
                            <button type="button" id="profile-options-toggle"
                                class="grid size-8 place-items-center rounded-full text-neutral-700 transition hover:bg-neutral-100 cursor-pointer"
                                aria-label="Menu Opsi">
                                <svg viewBox="0 0 24 24" class="size-4.5 fill-current">
                                    <circle cx="12" cy="5" r="1.6" />
                                    <circle cx="12" cy="12" r="1.6" />
                                    <circle cx="12" cy="19" r="1.6" />
                                </svg>
                            </button>
                            <div id="profile-options-menu"
                                class="absolute right-0 top-9 z-20 hidden w-44 rounded-xl border border-neutral-200 bg-white py-1.5 shadow-xl ring-1 ring-black/5">
                                <a href="{{ route('profile.edit') }}"
                                    class="flex items-center gap-2 px-3 py-2 text-xs font-semibold text-neutral-700 hover:bg-neutral-100 transition">
                                    <svg viewBox="0 0 24 24" class="size-4 stroke-current stroke-2 fill-none">
                                        <path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z" />
                                    </svg>
                                    <span>Edit Profil</span>
                                </a>
                                <form action="{{ route('logout') }}" method="POST">
                                    @csrf
                                    <button type="submit"
                                        class="flex w-full items-center gap-2 px-3 py-2 text-xs font-semibold text-red-600 hover:bg-red-50 transition cursor-pointer">
                                        <svg viewBox="0 0 24 24" class="size-4 stroke-current stroke-2 fill-none">
                                            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9" />
                                        </svg>
                                        <span>Keluar</span>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                @endif
            </div>

            @if ($isOwner)
                <div class="grid grid-cols-2 sm:flex sm:items-center gap-2 w-full sm:w-auto">
                    <a href="{{ route('profile.edit') }}"
                        class="flex items-center justify-center gap-1.5 rounded-lg border border-neutral-300 bg-white px-3.5 sm:px-4 py-2.5 text-xs font-bold text-neutral-800 transition hover:bg-neutral-100 shadow-2xs whitespace-nowrap">
                        <svg viewBox="0 0 24 24" class="size-3.5 shrink-0 stroke-current stroke-2 fill-none">
                            <path d="M12 20h9" />
                            <path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z" />
                        </svg>
                        <span>Edit Profil</span>
                    </a>
                    <a href="{{ Route::has('chat.index') ? route('chat.index') : '#' }}"
                        class="flex items-center justify-center gap-1.5 sm:gap-2 rounded-lg bg-lime-400 px-3.5 sm:px-6 py-2.5 text-xs font-bold text-neutral-900 transition hover:bg-lime-500 shadow-2xs whitespace-nowrap">
                        <svg viewBox="0 0 24 24" class="size-4 shrink-0 fill-none stroke-current stroke-2">
                            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z" />
                        </svg>
                        <span>Chat Penjual</span>
                    </a>
                </div>
            @else
                <div class="w-full sm:w-auto">
                    <button type="button" id="seller-chat-open" @guest data-open-login @endguest @if (auth()->check() && $chatProducts->isEmpty()) disabled title="Penjual belum punya produk aktif" @endif
                        class="flex items-center justify-center gap-2 rounded-lg bg-lime-400 px-6 py-2.5 text-xs font-bold text-neutral-900 transition hover:bg-lime-500 shadow-2xs w-full sm:w-auto whitespace-nowrap disabled:cursor-not-allowed disabled:bg-neutral-200 disabled:text-neutral-500 disabled:hover:bg-neutral-200">
                        <svg viewBox="0 0 24 24" class="size-4 shrink-0 fill-none stroke-current stroke-2">
                            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z" />
                        </svg>
                        <span>Chat Penjual</span>
                    </button>
                </div>
            @endif
        </div>
    </div>
</div>