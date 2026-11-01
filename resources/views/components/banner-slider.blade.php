@props(['images' => []])

@php $items = count($images) ? $images : [null, null, null]; @endphp

<div data-banner-slider
     class="no-scrollbar relative -mx-4 flex snap-x snap-mandatory gap-3 overflow-x-auto scroll-smooth px-4 md:mx-0 md:gap-5 md:px-0">
    @foreach ($items as $src)
        <div class="aspect-video w-[85%] shrink-0 snap-start overflow-hidden rounded-lg bg-neutral-400/70 md:w-[calc((100%-2.5rem)/3)]">
            @if ($src)
                <img src="{{ $src }}" alt="Banner promo {{ $loop->iteration }}"
                     class="size-full object-cover" onerror="this.remove()">
            @endif
        </div>
    @endforeach
</div>