@props(['review'])

@php
    $reviewerName = $review['reviewer_name'] ?? 'Pengguna';
    $reviewerAvatar = $review['reviewer_avatar'] ?? asset('images/default-avatar.png');
    $reviewerUrl = $review['reviewer_url'] ?? null;
@endphp

<article class="rounded-2xl border border-neutral-200 bg-white p-5 sm:p-6 shadow-2xs transition hover:shadow-xs"
    data-review-card>
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-2.5">
            @if ($reviewerUrl)
                <a href="{{ $reviewerUrl }}" title="Lihat profil {{ $reviewerName }}"
                    class="size-8 rounded-full overflow-hidden bg-neutral-200 border border-neutral-300 shrink-0 transition hover:scale-105 hover:ring-2 hover:ring-lime-400">
                    <img src="{{ $reviewerAvatar }}" alt="{{ $reviewerName }}" referrerpolicy="no-referrer"
                        onerror="this.onerror=null; this.src='{{ asset('images/default-avatar.png') }}';"
                        class="size-full object-cover">
                </a>
            @else
                <div class="size-8 rounded-full overflow-hidden bg-neutral-200 border border-neutral-300 shrink-0">
                    <img src="{{ $reviewerAvatar }}" alt="{{ $reviewerName }}" referrerpolicy="no-referrer"
                        onerror="this.onerror=null; this.src='{{ asset('images/default-avatar.png') }}';"
                        class="size-full object-cover">
                </div>
            @endif

            <div class="flex flex-col items-baseline">
                <h3 class="text-xs sm:text-sm font-bold text-neutral-900">
                    @if ($reviewerUrl)
                        <a href="{{ $reviewerUrl }}" class="transition-colors hover:text-lime-700 hover:underline">
                            {{ $reviewerName }}
                        </a>
                    @else
                        {{ $reviewerName }}
                    @endif
                </h3>
                <span class="text-xs text-neutral-400 font-normal">{{ $review['time_ago'] }}</span>
            </div>
        </div>

        @if (!empty($review['is_mine']))
            <div class="relative shrink-0" data-review-menu-root>
                <button type="button" data-review-menu-toggle aria-label="Opsi ulasan" aria-expanded="false"
                    class="grid size-7 place-items-center rounded-full text-neutral-400 hover:text-neutral-700 hover:bg-neutral-100 transition cursor-pointer">
                    <svg viewBox="0 0 24 24" class="size-4 fill-current">
                        <circle cx="12" cy="5" r="1.5" />
                        <circle cx="12" cy="12" r="1.5" />
                        <circle cx="12" cy="19" r="1.5" />
                    </svg>
                </button>
                <div data-review-menu
                    class="absolute right-0 top-full z-30 mt-1 hidden w-40 overflow-hidden rounded-xl border border-neutral-200 bg-white py-1 shadow-xl">
                    <button type="button" data-edit-review-toggle
                        class="flex w-full items-center gap-2 px-4 py-2 text-left text-xs font-medium text-neutral-700 transition hover:bg-neutral-100">
                        Edit ulasan
                    </button>
                    <form method="POST" action="{{ route('reviews.destroy', $review['id']) }}"
                        onsubmit="return confirm('Hapus ulasan Anda?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit"
                            class="flex w-full items-center gap-2 px-4 py-2 text-left text-xs font-medium text-red-600 transition hover:bg-red-50">
                            Hapus ulasan
                        </button>
                    </form>
                </div>
            </div>
        @endif
    </div>

    <div class="flex items-center gap-1 mt-2.5 mb-1.5">
        @for ($i = 1; $i <= 5; $i++)
            <svg viewBox="0 0 24 24"
                class="{{ $i <= $review['rating'] ? 'fill-amber-400 text-amber-400' : 'fill-neutral-200 text-neutral-200' }} size-4"
                aria-hidden="true">
                <path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z" />
            </svg>
        @endfor
    </div>

    <p class="text-xs sm:text-sm text-neutral-800 leading-relaxed font-normal">
        {{ $review['comment'] }}
    </p>

    @if (!empty($review['has_product']))
        <div class="mt-3.5 flex items-center gap-3.5 rounded-2xl bg-[#EAF5D3] p-3 border border-lime-200/60">
            <div class="size-14 shrink-0 overflow-hidden rounded-xl bg-white shadow-2xs">
                <img src="{{ asset($review['product_image']) }}" alt="{{ $review['product_title'] }}"
                    class="size-full object-cover"
                    onerror="this.onerror=null; this.src='https://images.unsplash.com/photo-1590658268037-6bf12165a8df?w=200&auto=format&fit=crop&q=80';">
            </div>
            <div class="min-w-0 flex-1 leading-snug">
                <p class="truncate text-xs sm:text-sm font-bold text-neutral-900">{{ $review['product_title'] }}</p>
                <p class="mt-0.5 text-xs sm:text-sm font-extrabold text-neutral-900">{{ $review['product_price'] }}</p>
            </div>
        </div>
    @endif

    @if (!empty($review['is_mine']))
        <form method="POST" action="{{ route('reviews.update', $review['id']) }}" hidden data-edit-review-form
            class="mt-4 rounded-xl border border-lime-200 bg-lime-50 p-4">
            @csrf
            @method('PATCH')
            <p class="text-xs font-bold text-neutral-900">Perbarui ulasan</p>

            <div class="mt-2" data-rating-input>
                <input type="hidden" name="rating" value="{{ $review['rating'] }}" required>
                <div class="flex items-center gap-1">
                    @for ($i = 1; $i <= 5; $i++)
                        <button type="button" data-rating-star="{{ $i }}" aria-label="{{ $i }} bintang"
                            class="text-neutral-300 transition hover:scale-110 cursor-pointer">
                            <svg viewBox="0 0 24 24" class="size-7 fill-current" aria-hidden="true">
                                <path
                                    d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z" />
                            </svg>
                        </button>
                    @endfor
                </div>
            </div>

            <textarea name="komentar" rows="3" maxlength="500" placeholder="Perbarui pengalaman Anda... (opsional)"
                class="mt-2 w-full rounded-lg border border-neutral-300 bg-white p-2.5 text-xs font-medium leading-relaxed text-neutral-900 focus:border-neutral-900 focus:outline-none">{{ $review['comment'] }}</textarea>

            <div class="mt-3 flex items-center justify-end gap-2">
                <button type="button" data-edit-review-cancel
                    class="rounded-lg border border-neutral-300 px-3.5 py-2 text-xs font-semibold text-neutral-700 transition hover:bg-neutral-100">
                    Batal
                </button>
                <button type="submit"
                    class="rounded-lg bg-lime-500 px-4 py-2 text-xs font-bold text-neutral-900 transition hover:bg-lime-600">
                    Simpan
                </button>
            </div>
        </form>
    @endif
</article>