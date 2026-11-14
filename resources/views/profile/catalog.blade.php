@extends('layouts.app')

@section('title', ($profile['name'] ?? 'Midou Wijaya') . ' - Detail User | RePake')
@section('canonical', $profileUrl ?? route('profile.catalog'))

@section('content')
    @php
        $isOwner = $isOwner ?? true;
        $chatProducts = $chatProducts ?? collect();
        $profileUrl = $profileUrl ?? route('profile.catalog');
        $tabUrl = fn(string $value): string => $profileUrl
            . (str_contains($profileUrl, '?') ? '&' : '?')
            . http_build_query(['tab' => $value]);
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

        {{-- Kartu Profil --}}
        <x-profile.header-card
            :profile="$profile"
            :is-owner="$isOwner"
            :chat-products="$chatProducts"
            :ulasan-url="$tabUrl('ulasan')"
        />

        {{-- Navigasi Tab --}}
        <div class="mt-8 border-b border-neutral-200">
            <div class="flex items-center gap-6">
                <a href="{{ $tabUrl('dijual') }}"
                   class="pb-2.5 text-xs font-bold transition {{ $tab === 'dijual' || empty($tab) ? 'border-b-2 border-neutral-900 text-neutral-900' : 'text-neutral-500 hover:text-neutral-800' }}">
                    Produk
                </a>

                @if ($isOwner)
                    <a href="{{ $tabUrl('favorit') }}"
                       class="pb-2.5 text-xs font-bold transition {{ $tab === 'favorit' ? 'border-b-2 border-neutral-900 text-neutral-900' : 'text-neutral-500 hover:text-neutral-800' }}">
                        Favorit (<span id="favorites-count">{{ $favoriteCount }}</span>)
                    </a>
                @endif

                <a href="{{ $tabUrl('ulasan') }}"
                   class="pb-2.5 text-xs font-bold transition {{ $tab === 'ulasan' ? 'border-b-2 border-[#8EAE36] text-neutral-900' : 'text-neutral-500 hover:text-neutral-800' }}">
                    Ulasan ({{ $reviewCount }})
                </a>
            </div>
        </div>

        {{-- Konten Tab --}}
        @if ($tab === 'ulasan')
            <x-profile.reviews-tab
                :reviews="$reviews"
                :seller="$seller"
                :profile-url="$profileUrl"
                :reviewable-order="$reviewableOrder ?? null"
                :review-rating="$reviewRating ?? 'semua'"
                :review-sort="$reviewSort ?? 'terbaru'"
            />
        @else
            <x-profile.products-tab
                :products="$products"
                :tab="$tab"
                :profile-url="$profileUrl"
                :status="$status ?? 'semua'"
                :sort="$sort ?? 'terbaru'"
            />
        @endif

        {{-- Modal Chat Penjual --}}
        @unless ($isOwner)
            <x-profile.chat-modal :seller-name="$profile['name']" :chat-products="$chatProducts" />
        @endunless
    </div>
@endsection

@push('scripts')
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const setupDropdown = (toggleId, menuId) => {
            const btn = document.getElementById(toggleId);
            const dropdown = document.getElementById(menuId);
            if (!btn || !dropdown) return null;

            const close = () => {
                dropdown.classList.add('hidden');
                btn.setAttribute('aria-expanded', 'false');
            };

            btn.addEventListener('click', function (e) {
                e.stopPropagation();
                const isHidden = dropdown.classList.toggle('hidden');
                btn.setAttribute('aria-expanded', String(!isHidden));
            });

            document.addEventListener('click', function (e) {
                if (!dropdown.contains(e.target) && !btn.contains(e.target)) {
                    close();
                }
            });

            return { btn, close };
        };

        setupDropdown('profile-options-toggle', 'profile-options-menu');

        const statusDropdown = setupDropdown('product-status-toggle', 'product-status-menu');
        const sortDropdown = setupDropdown('product-sort-toggle', 'product-sort-menu');
        statusDropdown?.btn.addEventListener('click', () => sortDropdown?.close());
        sortDropdown?.btn.addEventListener('click', () => statusDropdown?.close());

        const reviewRatingDropdown = setupDropdown('review-rating-toggle', 'review-rating-menu');
        const reviewSortDropdown = setupDropdown('review-sort-toggle', 'review-sort-menu');
        reviewRatingDropdown?.btn.addEventListener('click', () => reviewSortDropdown?.close());
        reviewSortDropdown?.btn.addEventListener('click', () => reviewRatingDropdown?.close());

        const favoritesGrid = document.querySelector('[data-favorites-grid]');
        if (favoritesGrid) {
            new MutationObserver(() => {
                if (favoritesGrid.querySelectorAll('[data-favorite-card]').length === 0 && !favoritesGrid.querySelector('[data-favorites-empty]')) {
                    favoritesGrid.innerHTML = '<div data-favorites-empty class="col-span-full my-8 text-center text-xs text-neutral-500">Tidak ada produk dalam daftar ini.</div>';
                }
            }).observe(favoritesGrid, { childList: true });
        }

        const chatOpen = document.getElementById('seller-chat-open');
        const chatModal = document.getElementById('seller-chat-modal');
        const chatClose = document.getElementById('seller-chat-close');

        if (chatOpen && chatModal) {
            chatOpen.addEventListener('click', function () {
                if (chatOpen.hasAttribute('data-open-login')) return;
                chatModal.classList.remove('hidden');
                chatClose?.focus();
            });

            chatClose?.addEventListener('click', function () {
                chatModal.classList.add('hidden');
                chatOpen.focus();
            });

            chatModal.addEventListener('click', function (e) {
                if (e.target === chatModal) chatModal.classList.add('hidden');
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