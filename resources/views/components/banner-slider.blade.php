@props(['images' => []])

<div data-banner-slider
    class="relative flex snap-x snap-mandatory gap-4 overflow-x-auto md:gap-5 no-scrollbar">
    @if (count($images) > 0)
        @foreach ($images as $src)
            <div data-banner-slide
                class="aspect-[16/7] w-full shrink-0 snap-start overflow-hidden rounded-xl bg-neutral-300 md:aspect-[16/9] md:w-[calc(33.333333%_-_0.833333rem)]">
                <img src="{{ $src }}" alt="Banner promo {{ $loop->iteration }}" class="size-full object-cover"
                    onerror="this.remove()">
            </div>
        @endforeach
    @else
        <div class="aspect-[16/7] w-full shrink-0 rounded-xl bg-neutral-300 md:aspect-[16/9] md:w-[calc(33.333333%_-_0.833333rem)]"></div>
        <div class="aspect-[16/7] w-full shrink-0 rounded-xl bg-neutral-300 md:aspect-[16/9] md:w-[calc(33.333333%_-_0.833333rem)]"></div>
        <div class="aspect-[16/7] w-full shrink-0 rounded-xl bg-neutral-300 md:aspect-[16/9] md:w-[calc(33.333333%_-_0.833333rem)]"></div>
    @endif
</div>