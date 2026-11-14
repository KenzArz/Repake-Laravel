@props([
    'reviews' => [],
    'seller',
    'profileUrl',
    'reviewableOrder' => null,
    'reviewRating' => 'semua',
    'reviewSort' => 'terbaru',
])

@php
    $reviewRatingLabels = [
        'semua' => 'Semua Ulasan',
        '5' => '5 Bintang',
        '4' => '4 Bintang',
        '3' => '3 Bintang',
        '2' => '2 Bintang',
        '1' => '1 Bintang',
    ];
    $currentReviewRatingLabel = $reviewRatingLabels[$reviewRating] ?? 'Semua Ulasan';

    $reviewSortLabels = [
        'terbaru' => 'Terbaru',
        'terlama' => 'Terlama',
        'rating_tinggi' => 'Rating Tertinggi',
        'rating_rendah' => 'Rating Terendah',
    ];
    $currentReviewSortLabel = $reviewSortLabels[$reviewSort] ?? 'Terbaru';

    $reviewFilterUrl = fn(array $params): string => $profileUrl
        . (str_contains($profileUrl, '?') ? '&' : '?')
        . http_build_query([
            'tab' => 'ulasan',
            'rating' => $params['rating'] ?? $reviewRating,
            'review_sort' => $params['review_sort'] ?? $reviewSort,
        ]);
@endphp

<section class="mt-6 scroll-mt-24" id="ulasan" data-profile-reviews>
    <div class="flex items-center justify-between gap-3 mb-4">
        <div class="relative">
            <button type="button" id="review-rating-toggle" aria-expanded="false"
                class="group inline-flex items-center gap-1.5 text-xl font-bold tracking-tight text-neutral-900 transition hover:text-lime-700 cursor-pointer">
                <span>{{ $currentReviewRatingLabel }}</span>
                <svg viewBox="0 0 24 24"
                    class="size-4 fill-none stroke-current stroke-[2.5] text-neutral-700 transition-transform duration-200 group-aria-expanded:rotate-180 group-hover:text-lime-700">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m6 9 6 6 6-6" />
                </svg>
            </button>

            <div id="review-rating-menu"
                class="absolute left-0 top-full z-30 mt-1.5 hidden w-44 overflow-hidden rounded-xl border border-neutral-200 bg-white py-1 shadow-xl ring-1 ring-black/5">
                @foreach ($reviewRatingLabels as $ratingKey => $ratingLabel)
                    <a href="{{ $reviewFilterUrl(['rating' => $ratingKey]) }}"
                        class="flex items-center justify-between px-3.5 py-2 text-xs font-semibold transition {{ $reviewRating === (string) $ratingKey ? 'bg-lime-50 text-lime-700' : 'text-neutral-700 hover:bg-neutral-100' }}">
                        <span class="inline-flex items-center gap-1.5">
                            @if ($ratingKey !== 'semua')
                                <span class="text-amber-500">★</span>
                            @endif
                            <span>{{ $ratingLabel }}</span>
                        </span>
                        @if ($reviewRating === (string) $ratingKey)
                            <svg viewBox="0 0 24 24" class="size-3.5 fill-none stroke-current stroke-[2.5]">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m5 13 4 4L19 7" />
                            </svg>
                        @endif
                    </a>
                @endforeach
            </div>
        </div>

        <div class="relative">
            <button type="button" id="review-sort-toggle" aria-expanded="false"
                class="group flex items-center gap-1.5 rounded-full border border-neutral-300 bg-white px-4 py-1.5 text-xs font-semibold text-neutral-800 shadow-2xs transition hover:bg-neutral-100 cursor-pointer">
                <span>Urutkan: <strong>{{ $currentReviewSortLabel }}</strong></span>
                <svg viewBox="0 0 24 24"
                    class="size-3.5 fill-none stroke-current stroke-2 transition-transform duration-200 group-aria-expanded:rotate-180">
                    <path d="m6 9 6 6 6-6" />
                </svg>
            </button>

            <div id="review-sort-menu"
                class="absolute right-0 top-full z-30 mt-1.5 hidden w-44 overflow-hidden rounded-xl border border-neutral-200 bg-white py-1 shadow-xl ring-1 ring-black/5">
                @foreach ($reviewSortLabels as $rSortKey => $rSortLabel)
                    <a href="{{ $reviewFilterUrl(['review_sort' => $rSortKey]) }}"
                        class="flex items-center justify-between px-3.5 py-2 text-xs font-semibold transition {{ $reviewSort === $rSortKey ? 'bg-lime-50 text-lime-700' : 'text-neutral-700 hover:bg-neutral-100' }}">
                        <span>{{ $rSortLabel }}</span>
                        @if ($reviewSort === $rSortKey)
                            <svg viewBox="0 0 24 24" class="size-3.5 fill-none stroke-current stroke-[2.5]">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m5 13 4 4L19 7" />
                            </svg>
                        @endif
                    </a>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Form ulasan penjual -> pembeli --}}
    @if (!empty($reviewableOrder))
        <form method="POST" action="{{ route('reviews.store', $reviewableOrder) }}"
            class="mb-4 rounded-2xl border border-lime-200 bg-lime-50 p-5 shadow-2xs" data-review-form>
            @csrf
            <p class="text-sm font-bold text-neutral-900">Beri ulasan untuk {{ $seller->name }}</p>

            <div class="mt-3" data-rating-input>
                <input type="hidden" name="rating" value="" required>
                <div class="flex items-center gap-1">
                    @for ($i = 1; $i <= 5; $i++)
                        <button type="button" data-rating-star="{{ $i }}" aria-label="{{ $i }} bintang"
                            class="text-neutral-300 transition hover:scale-110 cursor-pointer">
                            <svg viewBox="0 0 24 24" class="size-8 fill-current" aria-hidden="true">
                                <path
                                    d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z" />
                            </svg>
                        </button>
                    @endfor
                </div>
                @error('rating')
                    <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <textarea name="komentar" rows="3" maxlength="500"
                placeholder="Ceritakan pengalaman transaksi Anda (opsional)..."
                class="mt-3 w-full rounded-lg border border-neutral-300 bg-white p-2.5 text-xs font-medium leading-relaxed text-neutral-900 focus:border-neutral-900 focus:outline-none"></textarea>

            <div class="mt-3 flex justify-end">
                <button type="submit"
                    class="rounded-lg bg-lime-500 px-4 py-2 text-xs font-bold text-neutral-900 transition hover:bg-lime-600">
                    Kirim Ulasan
                </button>
            </div>
        </form>
    @endif

    {{-- Daftar Kartu Ulasan --}}
    <div class="space-y-4">
        @forelse ($reviews as $review)
            <x-profile.review-card :review="$review" />
        @empty
            <div class="rounded-2xl border border-neutral-200 bg-white p-8 text-center text-xs text-neutral-500">
                Belum ada ulasan.
            </div>
        @endforelse
    </div>
</section>