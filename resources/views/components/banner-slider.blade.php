@props(['images' => []])

<div data-banner-slider class="grid grid-cols-1 gap-4 md:grid-cols-3 md:gap-5">
    @if (count($images) > 0)
        @foreach ($images as $src)
            <div class="aspect-[16/7] md:aspect-[16/9] overflow-hidden rounded-xl bg-neutral-300">
                <img src="{{ $src }}" alt="Banner promo {{ $loop->iteration }}" class="size-full object-cover" onerror="this.remove()">
            </div>
        @endforeach
    @else
        <div class="h-36 md:h-44 rounded-xl bg-neutral-300"></div>
        <div class="h-36 md:h-44 rounded-xl bg-neutral-300"></div>
        <div class="h-36 md:h-44 rounded-xl bg-neutral-300"></div>
    @endif
</div>