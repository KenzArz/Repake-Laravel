@props(['conversations', 'activeId' => null, 'myId'])

<div class="mb-2 flex items-center justify-between px-2 py-2">
    <h2 class="text-h3 font-bold text-neutral-900">Semua Pesan</h2>
    <span class="text-label text-neutral-500">{{ $conversations->count() }}</span>
</div>

<div class="flex flex-col">
    @forelse ($conversations as $conversation)
        @php
            $other = $conversation->buyer_id === $myId ? $conversation->seller : $conversation->buyer;
            $last = $conversation->lastMessage;
            $isActive = $activeId !== null && $conversation->id === $activeId;
        @endphp
        <a href="{{ route('chat.show', $conversation) }}"
           class="rounded-xl px-3 py-3 transition {{ $isActive ? 'bg-lime-100' : 'hover:bg-neutral-100/80' }}">
            <div class="flex items-start gap-3">
                <img src="{{ $other && $other->avatar ? asset($other->avatar) : asset('images/default-avatar.png') }}"
                     alt="" class="size-10 shrink-0 rounded-full object-cover">
                <div class="min-w-0 flex-1">
                    <div class="flex items-baseline justify-between gap-2">
                        <p class="truncate text-body font-bold text-neutral-900">{{ $other?->name }}</p>
                        @if ($last)
                            <span class="shrink-0 text-caption text-neutral-500">{{ $last->created_at->format('H.i') }}</span>
                        @endif
                    </div>
                    <p class="line-clamp-2 text-label font-semibold text-neutral-800">{{ $conversation->product?->judul }}</p>
                    <p class="mt-0.5 truncate text-caption text-neutral-500">
                        @if ($last?->type === \App\Models\Message::TYPE_OFFER)
                            Anda: Tawaran Rp{{ number_format($last->offer_amount, 0, ',', '.') }}
                        @elseif ($last?->image !== null)
                            {{ $last->sender_id === $myId ? 'Anda: ' : '' }}📷 Gambar
                        @elseif ($last?->body)
                            {{ ($last->sender_id === $myId ? 'Anda: ' : '').$last->body }}
                        @else
                            Belum ada pesan
                        @endif
                    </p>
                </div>
                @if ($last?->type === \App\Models\Message::TYPE_OFFER)
                    <span class="mt-1 shrink-0">
                        @switch ($last->offer_status)
                            @case (\App\Models\Message::OFFER_PENDING)
                                <span class="grid size-5 place-items-center rounded-full bg-warning" title="Tawaran menunggu keputusan">
                                    <svg viewBox="0 0 24 24" class="size-3 fill-none stroke-current stroke-2 [stroke-linecap:round]"><path d="M12 6v6l4 2"/></svg>
                                </span>
                            @break
                            @case (\App\Models\Message::OFFER_ACCEPTED)
                                <span class="grid size-5 place-items-center rounded-full bg-success" title="Tawaran diterima">
                                    <svg viewBox="0 0 24 24" class="size-3 fill-none stroke-current stroke-2 [stroke-linecap:round] [stroke-linejoin:round]"><path d="m5 13 4 4L19 7"/></svg>
                                </span>
                            @break
                            @case (\App\Models\Message::OFFER_REJECTED)
                                <span class="grid size-5 place-items-center rounded-full bg-error" title="Tawaran ditolak">
                                    <svg viewBox="0 0 24 24" class="size-3 fill-none stroke-current stroke-2 [stroke-linecap:round]"><path d="M18 6 6 18M6 6l12 12"/></svg>
                                </span>
                            @break
                            @default
                                <span class="grid size-5 place-items-center rounded-full bg-neutral-300 text-white" title="Tawaran dibatalkan">
                                    <svg viewBox="0 0 24 24" class="size-3 fill-none stroke-current stroke-2 [stroke-linecap:round]"><path d="M5 12h14"/></svg>
                                </span>
                        @endswitch
                    </span>
                @endif
            </div>
        </a>
    @empty
        <div class="px-4 py-10 text-center">
            <p class="text-body font-semibold text-neutral-800">Belum Ada Percakapan</p>
            <p class="mt-1 text-label text-neutral-500">Mulai chat dari halaman detail barang.</p>
        </div>
    @endforelse
</div>
