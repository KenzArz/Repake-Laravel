@php
    $isMine = $message->sender_id === $myId;
    $isOffer = $message->type === \App\Models\Message::TYPE_OFFER;
    $isSystem = $message->type === \App\Models\Message::TYPE_SYSTEM;
    $other = $conversation->buyer_id === $myId ? $conversation->seller : $conversation->buyer;
    $otherAvatar = $other?->avatar_url ?? asset('images/default-avatar.png');
    $receipt = $isMine ? $message->receiptStatus() : null;

    $isPending = $message->offer_status === \App\Models\Message::OFFER_PENDING;
    $isAccepted = $message->offer_status === \App\Models\Message::OFFER_ACCEPTED;
    $isRejected = $message->offer_status === \App\Models\Message::OFFER_REJECTED;

    $amount = 'Rp '.number_format((int) $message->offer_amount, 0, ',', '.');
    $time = $message->created_at?->copy()->timezone('Asia/Jakarta')->format('H.i');

    $imageUrls = array_map(fn ($path) => Storage::url($path), $message->imagePaths());
    $imageCount = count($imageUrls);
    $visibleImages = array_slice($imageUrls, 0, 4);
    $remainingImages = max(0, $imageCount - 4);
@endphp

@if ($isSystem)
    <div class="flex justify-center py-1" data-message-id="{{ $message->id }}" data-sender-id="{{ $message->sender_id }}" data-type="{{ $message->type }}">
        <p class="rounded-full bg-neutral-200 px-4 py-1.5 text-[13px] font-semibold text-neutral-800">{{ $message->body }}</p>
    </div>
@else
    <div class="flex items-start gap-2 {{ $isMine ? 'justify-end' : 'justify-start' }}"
         data-message-id="{{ $message->id }}"
         data-sender-id="{{ $message->sender_id }}"
         data-type="{{ $message->type }}"
         data-version="{{ $message->updated_at?->getTimestamp() ?? 0 }}">
        @unless ($isMine)
            <img src="{{ $otherAvatar }}" alt="" class="mt-0.5 size-6 shrink-0 rounded-full object-cover">
        @endunless

        <div class="flex {{ $imageCount > 0 ? 'max-w-[min(76%,18rem)]' : 'max-w-[min(80%,26rem)]' }} flex-col {{ $isMine ? 'items-end' : 'items-start' }}">
            @if ($isOffer)
                @php
                    $cardTone = $isMine ? 'bg-lime-400' : 'bg-neutral-200';
                @endphp
                <div class="rounded-2xl {{ $cardTone }} p-3.5 {{ $isMine ? 'rounded-br-md' : 'rounded-bl-md' }}"
                     data-offer-card="{{ $message->id }}"
                     data-offer-amount="{{ $message->offer_amount }}"
                     data-offer-status="{{ $message->offer_status }}"
                     data-offer-edit-url="{{ $isPending && $isMine ? route('chat.offers.update', [$conversation, $message]) : '' }}">
                    @if ($isPending)
                        <p class="text-[15px] leading-tight text-neutral-700">Menawar</p>
                        <p class="mt-0.5 text-[22px] font-extrabold leading-tight text-neutral-900" data-offer-amount-text>{{ $amount }}</p>

                        @if ($isMine)
                            <div class="mt-2.5 flex gap-2" data-offer-actions>
                                <button type="button" data-offer-edit
                                        class="rounded-lg border border-neutral-900/50 px-4 py-1.5 text-[13px] font-semibold text-neutral-900 transition hover:bg-white/40">
                                    Edit
                                </button>
                                <form method="POST" action="{{ route('chat.offers.cancel', [$conversation, $message]) }}" data-offer-action>
                                    @csrf
                                    <button type="submit"
                                            class="rounded-lg bg-error px-4 py-1.5 text-[13px] font-semibold text-white transition hover:opacity-90">
                                        Batalkan
                                    </button>
                                </form>
                            </div>
                        @elseif ($conversation->seller_id === $myId)
                            <div class="mt-2.5 flex gap-2" data-offer-actions>
                                <form method="POST" action="{{ route('chat.offers.accept', [$conversation, $message]) }}" data-offer-action>
                                    @csrf
                                    <button type="submit"
                                            class="rounded-lg border border-neutral-900/40 bg-lime-500 px-4 py-1.5 text-[13px] font-semibold text-neutral-900 transition hover:bg-lime-600">
                                        Terima
                                    </button>
                                </form>
                                <form method="POST" action="{{ route('chat.offers.reject', [$conversation, $message]) }}" data-offer-action>
                                    @csrf
                                    <button type="submit"
                                            class="rounded-lg bg-error px-4 py-1.5 text-[13px] font-semibold text-white transition hover:opacity-90">
                                        Tolak
                                    </button>
                                </form>
                            </div>
                        @endif
                    @elseif ($isAccepted || $isRejected)
                        <div class="flex items-start gap-2.5">
                            <span class="mt-1 grid size-5 shrink-0 place-items-center rounded-full text-white {{ $isAccepted ? 'bg-success' : 'bg-error' }}">
                                @if ($isAccepted)
                                    <svg viewBox="0 0 24 24" class="size-3 fill-none stroke-current stroke-[3]" aria-hidden="true"><path d="m5 13 4 4L19 7" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                @else
                                    <svg viewBox="0 0 24 24" class="size-3 fill-none stroke-current stroke-[3]" aria-hidden="true"><path d="M18 6 6 18M6 6l12 12" stroke-linecap="round"/></svg>
                                @endif
                            </span>
                            <div class="min-w-0">
                                <p class="text-[15px] leading-tight text-neutral-900">{{ $isAccepted ? 'Penawaran Disetujui' : 'Penawaran Ditolak' }}</p>
                                <p class="mt-0.5 text-[22px] font-extrabold leading-tight text-neutral-900">{{ $amount }}</p>
                            </div>
                        </div>
                    @else
                        <p class="text-[15px] leading-tight text-neutral-700">Penawaran Dibatalkan</p>
                        <p class="mt-0.5 text-[22px] font-extrabold leading-tight text-neutral-500 line-through">{{ $amount }}</p>
                    @endif
                </div>
            @else
                <div class="rounded-2xl {{ $isMine ? 'bg-lime-400 rounded-br-md' : 'bg-neutral-200 rounded-bl-md' }} {{ $imageCount > 0 ? 'p-1' : 'px-4 py-2' }} text-[15px] leading-[1.35] text-neutral-900">
                    @if ($imageCount > 0)
                        <div class="overflow-hidden rounded-xl"
                             data-lightbox-group="{{ json_encode($imageUrls) }}"
                             data-lightbox-caption="{{ $message->body }}">
                            @if ($imageCount === 1)
                                <button type="button"
                                        class="block cursor-zoom-in"
                                        data-lightbox-index="0"
                                        aria-label="Buka foto">
                                    <img src="{{ $imageUrls[0] }}"
                                         alt="Foto pesan"
                                         class="max-h-72 w-auto max-w-full object-cover"
                                         loading="lazy">
                                </button>
                            @elseif ($imageCount === 3)
                                {{-- 1 foto besar di kiri, 2 foto bertumpuk di kanan. --}}
                                <div class="grid grid-cols-2 gap-[3px] bg-white">
                                    <button type="button"
                                            class="relative row-span-2 h-full cursor-zoom-in bg-neutral-200 after:pointer-events-none after:absolute after:inset-0 after:shadow-[inset_0_0_0_1px_rgba(0,0,0,0.10)]"
                                            data-lightbox-index="0"
                                            aria-label="Buka foto 1 dari {{ $imageCount }}">
                                        <img src="{{ $imageUrls[0] }}" alt="Foto pesan 1"
                                             class="h-full w-full object-cover" loading="lazy">
                                    </button>
                                    @foreach ([1, 2] as $index)
                                        <button type="button"
                                                class="relative cursor-zoom-in bg-neutral-200 after:pointer-events-none after:absolute after:inset-0 after:shadow-[inset_0_0_0_1px_rgba(0,0,0,0.10)]"
                                                data-lightbox-index="{{ $index }}"
                                                aria-label="Buka foto {{ $index + 1 }} dari {{ $imageCount }}">
                                            <img src="{{ $imageUrls[$index] }}" alt="Foto pesan {{ $index + 1 }}"
                                                 class="aspect-square w-full object-cover" loading="lazy">
                                        </button>
                                    @endforeach
                                </div>
                            @else
                                {{-- 2, 4, atau lebih: grid 2x2. Foto ke-4 menampilkan sisa foto. --}}
                                <div class="grid grid-cols-2 gap-[3px] bg-white">
                                    @foreach ($visibleImages as $index => $url)
                                        <button type="button"
                                                class="relative cursor-zoom-in bg-neutral-200 after:pointer-events-none after:absolute after:inset-0 after:shadow-[inset_0_0_0_1px_rgba(0,0,0,0.10)]"
                                                data-lightbox-index="{{ $index }}"
                                                aria-label="Buka foto {{ $index + 1 }} dari {{ $imageCount }}">
                                            <img src="{{ $url }}" alt="Foto pesan {{ $index + 1 }}"
                                                 class="aspect-square w-full object-cover" loading="lazy">
                                            @if ($index === 3 && $remainingImages > 0)
                                                {{-- Scrim gelap + blur tipis supaya angka sisa foto tetap terbaca
                                                     walau fotonya ramai. --}}
                                                <span class="absolute inset-0 z-10 grid select-none place-items-center bg-neutral-950/60 text-[26px] font-black leading-none text-white backdrop-blur-[2px]"
                                                      aria-hidden="true">+{{ $remainingImages }}</span>
                                            @endif
                                        </button>
                                    @endforeach
                                </div>
                            @endif
                        </div>

                        @if ($message->body)
                            <p class="whitespace-pre-line break-words px-2 pb-1 pt-1.5">{{ $message->body }}</p>
                        @endif
                    @else
                        <p class="whitespace-pre-line break-words">{{ $message->body }}</p>
                    @endif
                </div>
            @endif

            {{-- Jam + resi: hanya tampil di pesan terakhir tiap grup (diatur JS). --}}
            <div class="mt-1 flex items-center gap-1 px-1 text-[10.5px] text-neutral-500" data-group-meta>
                <time datetime="{{ $message->created_at?->toIso8601String() }}" data-local-time>{{ $time }}</time>
                @if ($isMine)
                    <span data-receipt="{{ $receipt }}" title="{{ $receipt === 'read' ? 'Dibaca' : ($receipt === 'delivered' ? 'Diterima' : 'Terkirim') }}">
                        @if ($receipt === 'sent')
                            <svg viewBox="0 0 24 24" class="size-3.5 fill-none stroke-current stroke-2" aria-hidden="true"><path d="m5 13 4 4L19 7" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        @else
                            <svg viewBox="0 0 30 24" class="size-4 fill-none stroke-current stroke-2 {{ $receipt === 'read' ? 'text-sky-600' : '' }}" aria-hidden="true">
                                <path d="m2 13 4 4L16 7" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="m10 13 4 4L24 7" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        @endif
                    </span>
                @endif
            </div>
        </div>
    </div>
@endif
