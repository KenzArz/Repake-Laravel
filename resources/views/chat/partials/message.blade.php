@php
    $mine = $message->sender_id === auth()->id();
    $isOffer = $message->type === \App\Models\Message::TYPE_OFFER;
    $other = $conversation->buyer_id === auth()->id() ? $conversation->seller : $conversation->buyer;
    $otherAvatar = $other && $other->avatar ? asset($other->avatar) : asset('images/default-avatar.png');
@endphp
<div class="flex items-start gap-2 {{ $mine ? 'justify-end' : 'justify-start' }}" data-message-id="{{ $message->id }}">
    @unless ($mine)
        <img src="{{ $otherAvatar }}" alt="" class="mt-1 size-7 shrink-0 rounded-full object-cover">
    @endunless
    <div class="max-w-[75%] rounded-2xl px-4 py-2 text-body {{ $mine ? 'bg-lime-400 text-neutral-900' : 'bg-neutral-200 text-neutral-900' }}">
        @if ($isOffer)
            <p class="text-small-label text-neutral-700">Menawar</p>
            <p class="font-bold" data-offer-amount="{{ $message->offer_amount }}">Rp{{ number_format($message->offer_amount, 0, ',', '.') }}</p>
            <p class="text-caption text-neutral-700" data-offer-status="{{ $message->offer_status }}">
                @switch ($message->offer_status)
                    @case (\App\Models\Message::OFFER_PENDING)
                        Menunggu keputusan penjual
                        @break
                    @case (\App\Models\Message::OFFER_ACCEPTED)
                        Diterima ✅
                        @break
                    @case (\App\Models\Message::OFFER_REJECTED)
                        Ditolak ❌
                        @break
                    @default
                        Dibatalkan
                @endswitch
            </p>
            @if ($message->offer_status === \App\Models\Message::OFFER_PENDING && $mine)
                {{-- Pengirim tawaran: Edit & Batalkan sesuai Figma --}}
                <div class="mt-2 flex gap-2" data-offer-actions="{{ $message->id }}">
                    <button type="button" data-offer-edit="{{ $message->id }}"
                            data-offer-update-url="{{ route('chat.offers.update', [$conversation, $message]) }}"
                            class="rounded-md border border-neutral-500 bg-white/40 px-3 py-1 text-small-label font-semibold text-neutral-900 transition hover:bg-white/70">
                        Edit
                    </button>
                    <form method="POST" action="{{ route('chat.offers.cancel', [$conversation, $message]) }}">
                        @csrf
                        <button type="submit"
                                class="rounded-md bg-error px-3 py-1 text-small-label font-semibold transition hover:opacity-90">
                            Batalkan
                        </button>
                    </form>
                </div>
            @elseif ($message->offer_status === \App\Models\Message::OFFER_PENDING && $conversation->seller_id === auth()->id())
                {{-- Penjual: keputusan tawaran --}}
                <div class="mt-2 flex gap-2" data-offer-actions="{{ $message->id }}">
                    <form method="POST" action="{{ route('chat.offers.accept', [$conversation, $message]) }}">
                        @csrf
                        <button type="submit" class="rounded-md bg-neutral-900 px-3 py-1 text-small-label font-semibold text-white transition hover:bg-neutral-700">Terima</button>
                    </form>
                    <form method="POST" action="{{ route('chat.offers.reject', [$conversation, $message]) }}">
                        @csrf
                        <button type="submit" class="rounded-md bg-error px-3 py-1 text-small-label font-semibold transition hover:opacity-90">Tolak</button>
                    </form>
                </div>
            @endif
        @else
            @if ($message->image !== null)
                <img src="{{ Storage::url($message->image) }}" alt="Gambar pesan" class="mb-1 max-h-48 rounded-lg">
            @endif
            @if ($message->body)
                <p class="break-words">{{ $message->body }}</p>
            @endif
        @endif
        <span class="mt-1 block text-right text-caption text-neutral-600/80">{{ $message->created_at?->format('H.i') }}</span>
    </div>
</div>
