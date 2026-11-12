@php
    $filter = $filter ?? 'semua';
    $activeId = $activeId ?? null;
    $filterLabels = [
        'semua' => 'Semua Pesan',
        'membeli' => 'Membeli',
        'menjual' => 'Menjual',
        'belum' => 'Belum Terbaca',
    ];
    $activeLabel = $filterLabels[$filter] ?? 'Semua Pesan';
    $unreadTotal = $conversations->sum('unread_count');
@endphp

<div class="mb-2 px-1">
    <div class="flex items-center justify-between gap-2">
        <div class="relative">
            <button type="button" data-filter-toggle
                    class="flex items-center gap-1 text-[20px] font-extrabold tracking-tight text-neutral-900"
                    aria-expanded="false" aria-controls="chat-filter-menu">
                <span data-filter-label>{{ $activeLabel }}</span>
                <svg viewBox="0 0 24 24" class="size-4 fill-none stroke-current stroke-2 transition-transform" data-filter-chevron aria-hidden="true">
                    <path d="m6 9 6 6 6-6" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                @if ($unreadTotal > 0)
                    <span class="grid h-4 min-w-4 place-items-center rounded-full bg-error px-1 text-[10px] font-bold text-white" data-sidebar-unread>{{ $unreadTotal }}</span>
                @endif
            </button>

            <div id="chat-filter-menu" data-filter-menu
                 class="absolute left-0 top-full z-30 mt-2 hidden w-48 divide-y divide-neutral-200 overflow-hidden rounded-xl border border-neutral-200 bg-white shadow-xl">
                @foreach ($filterLabels as $value => $label)
                    <a href="{{ route('chat.index', $value === 'semua' ? [] : ['filter' => $value]) }}"
                       class="flex items-center justify-between px-4 py-2.5 text-[15px] transition hover:bg-neutral-100 {{ $filter === $value ? 'font-bold text-neutral-900' : 'text-neutral-700' }}">
                        {{ $label }}
                    </a>
                @endforeach
            </div>
        </div>

        <button type="button" data-sidebar-search-toggle
                class="grid size-8 place-items-center rounded-full text-neutral-600 transition hover:bg-neutral-200/70 hover:text-neutral-900"
                aria-label="Cari percakapan">
            <svg viewBox="0 0 24 24" class="size-4.5 fill-none stroke-current stroke-2" aria-hidden="true">
                <circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3" stroke-linecap="round"/>
            </svg>
        </button>
    </div>

    <div class="mt-2 hidden" data-sidebar-search-wrap>
        <input type="search" data-sidebar-search autocomplete="off" placeholder="Cari nama atau produk..."
               class="w-full rounded-xl border border-neutral-300 bg-white px-3 py-2 text-[13px] text-neutral-900 outline-none transition focus:border-lime-500">
    </div>
</div>

<div class="flex flex-col" data-conversation-list>
    @forelse ($conversations as $conversation)
        @php
            $other = $conversation->buyer_id === $myId ? $conversation->seller : $conversation->buyer;
            $last = $conversation->lastMessage;
            $offer = $conversation->latestOffer ?? null;
            $isActive = $activeId !== null && $conversation->id === $activeId;
            $unread = (int) ($conversation->unread_count ?? 0);
            $avatar = $other?->avatar_url ?? asset('images/default-avatar.png');

            $preview = match (true) {
                $last === null => 'Belum ada pesan',
                $last->type === \App\Models\Message::TYPE_OFFER => 'Tawaran Rp '.number_format((int) $last->offer_amount, 0, ',', '.'),
                $last->type === \App\Models\Message::TYPE_SYSTEM => (string) $last->body,
                $last->image !== null => '📷 Gambar',
                default => (string) $last->body,
            };

            if ($last !== null && $last->sender_id === $myId && $last->type !== \App\Models\Message::TYPE_SYSTEM) {
                $preview = 'Anda: '.$preview;
            }

            $searchText = strtolower(trim(($other?->name ?? '').' '.($conversation->product?->judul ?? '').' '.$preview));
        @endphp

        <a href="{{ route('chat.show', $conversation) }}"
           data-conversation-item
           data-search-text="{{ $searchText }}"
           class="block rounded-xl px-2.5 py-2.5 transition {{ $isActive ? 'bg-lime-100' : 'hover:bg-neutral-200/50' }}">
            <div class="flex items-start gap-2.5">
                <img src="{{ $avatar }}" alt="" class="mt-0.5 size-8 shrink-0 rounded-full object-cover">

                <div class="min-w-0 flex-1">
                    <div class="flex items-baseline justify-between gap-2">
                        <p class="truncate text-[15px] font-bold text-neutral-900">{{ $other?->name }}</p>
                        <div class="flex shrink-0 items-center gap-1.5">
                            @if ($unread > 0)
                                <span class="grid h-4 min-w-4 place-items-center rounded-full bg-error px-1 text-[10px] font-bold text-white" title="{{ $unread }} pesan belum dibaca">{{ $unread }}</span>
                            @endif
                            @if ($last !== null)
                                <time datetime="{{ $last->created_at?->toIso8601String() }}" data-local-time
                                      class="text-[12px] text-neutral-500">{{ $last->created_at?->copy()->timezone('Asia/Jakarta')->format('H.i') }}</time>
                            @endif
                        </div>
                    </div>

                    <div class="mt-0.5 flex items-start gap-2">
                        <div class="min-w-0 flex-1">
                            <p class="line-clamp-2 text-[14px] font-bold leading-snug text-neutral-900">{{ $conversation->product?->judul }}</p>
                            <p class="mt-1 truncate text-[13px] text-neutral-500">{{ $preview }}</p>
                        </div>

                        @if ($offer !== null)
                            <span class="mt-0.5 shrink-0">
                                @switch ($offer->offer_status)
                                    @case (\App\Models\Message::OFFER_PENDING)
                                        <span class="grid size-6 place-items-center rounded-full bg-warning text-white" title="Tawaran menunggu keputusan">
                                            <svg viewBox="0 0 24 24" class="size-3.5 fill-none stroke-current stroke-2" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2" stroke-linecap="round"/></svg>
                                        </span>
                                    @break
                                    @case (\App\Models\Message::OFFER_ACCEPTED)
                                        <span class="grid size-6 place-items-center rounded-full bg-success text-white" title="Tawaran disetujui">
                                            <svg viewBox="0 0 24 24" class="size-3.5 fill-none stroke-current stroke-[2.5]" aria-hidden="true"><path d="m5 13 4 4L19 7" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                        </span>
                                    @break
                                    @case (\App\Models\Message::OFFER_REJECTED)
                                        <span class="grid size-6 place-items-center rounded-full bg-error text-white" title="Tawaran ditolak">
                                            <svg viewBox="0 0 24 24" class="size-3.5 fill-none stroke-current stroke-[2.5]" aria-hidden="true"><path d="M18 6 6 18M6 6l12 12" stroke-linecap="round"/></svg>
                                        </span>
                                    @break
                                    @default
                                        <span class="grid size-6 place-items-center rounded-full bg-neutral-400 text-white" title="Tawaran dibatalkan">
                                            <svg viewBox="0 0 24 24" class="size-3.5 fill-none stroke-current stroke-2" aria-hidden="true"><path d="M5 12h14" stroke-linecap="round"/></svg>
                                        </span>
                                @endswitch
                            </span>
                        @endif
                    </div>
                </div>
            </div>
        </a>
    @empty
        <div class="px-4 py-10 text-center">
            <p class="text-[15px] font-bold text-neutral-800">Belum Ada Percakapan</p>
            <p class="mt-1 text-[13px] text-neutral-500">Mulai chat dari halaman detail barang.</p>
        </div>
    @endforelse

    <div class="hidden px-4 py-8 text-center" data-search-empty>
        <p class="text-[14px] font-semibold text-neutral-700">Tidak ada percakapan yang cocok</p>
    </div>
</div>
