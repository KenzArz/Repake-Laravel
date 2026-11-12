@php
    $status = $offer?->offer_status;

    [$label, $tone] = match ($status) {
        \App\Models\Message::OFFER_PENDING => ['Ditawar', 'bg-warning'],
        \App\Models\Message::OFFER_ACCEPTED => ['Disetujui', 'bg-success'],
        \App\Models\Message::OFFER_REJECTED => ['Ditolak', 'bg-error'],
        \App\Models\Message::OFFER_CANCELLED => ['Dibatalkan', 'bg-neutral-400'],
        default => [null, null],
    };
@endphp

@if ($offer !== null && $label !== null)
    <div class="shrink-0 text-right" data-offer-badge="{{ $status }}">
        <span class="inline-block rounded-full {{ $tone }} px-2.5 py-0.5 text-[11px] font-semibold text-white">{{ $label }}</span>
        <p class="mt-1 text-[16px] font-extrabold leading-tight text-neutral-900">
            Rp {{ number_format((int) $offer->offer_amount, 0, ',', '.') }}
        </p>
    </div>
@endif
