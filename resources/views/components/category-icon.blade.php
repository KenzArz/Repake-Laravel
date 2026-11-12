@props([
    'icon' => null,
    'alt' => '',
    'size' => 'size-6',
])

@php
    $icon = is_string($icon) ? trim($icon) : '';
    $isImage = $icon !== '' && (
        str_starts_with($icon, 'http://')
        || str_starts_with($icon, 'https://')
        || str_starts_with($icon, '/')
        || preg_match('/\.(svg|png|jpe?g|webp|gif)$/i', $icon) === 1
    );
    $src = $isImage
        ? (str_starts_with($icon, 'http') ? $icon : asset(ltrim($icon, '/')))
        : null;
@endphp

@if ($isImage && $src)
    <img src="{{ $src }}" alt="{{ $alt }}" class="{{ $size }} shrink-0 rounded object-contain" onerror="this.remove()">
@elseif ($icon !== '')
    <span class="{{ $size }} grid shrink-0 place-items-center text-base leading-none" aria-hidden="true">{{ $icon }}</span>
@endif
