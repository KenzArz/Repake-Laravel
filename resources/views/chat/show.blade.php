@extends('layouts.app')

@section('title', 'Chat - RePake')

@section('hide-footer', '1')
@section('app-shell', '1')

@section('content')
    <div class="mx-auto flex min-h-0 w-full max-w-[1440px] flex-1 overflow-hidden bg-neutral-50 md:pl-8 xl:pl-12">
        {{-- Kiri: daftar percakapan (partial bersama dengan chat.index) --}}
        @include('chat.partials.conversation-aside', [
            'conversations' => $sidebarConversations,
            'activeId' => $conversation->id,
            'myId' => auth()->id(),
            'filter' => $filter ?? 'semua',
            'isRoom' => true,
        ])

        {{-- Kanan: ruang chat --}}
        <section id="chat-app" class="relative flex min-h-0 flex-1 flex-col overflow-hidden bg-neutral-50"
            data-my-id="{{ auth()->id() }}" data-seller-id="{{ $conversation->seller_id }}"
            data-conversation-id="{{ $conversation->id }}" data-send-url="{{ route('chat.messages.store', $conversation) }}"
            data-poll-url="{{ route('chat.messages.poll', $conversation) }}"
            data-read-url="{{ route('chat.read', $conversation) }}"
            data-other-avatar="{{ $other?->avatar_url ?? asset('images/default-avatar.png') }}"
            data-product-price="{{ (int) ($conversation->product?->harga ?? 0) }}"
            data-empty-label="Belum ada pesan. Sapa penjualnya dulu!">

            {{-- Header --}}
            <header
                class="flex shrink-0 items-center gap-2.5 border-b border-neutral-200 bg-white px-3 py-2 sm:gap-3 sm:px-4 sm:py-3">
                <a href="{{ route('chat.index') }}"
                    class="grid size-8 shrink-0 place-items-center rounded-full text-neutral-600 transition hover:bg-neutral-200/70 hover:text-neutral-900 md:hidden"
                    aria-label="Kembali ke daftar pesan">
                    <svg viewBox="0 0 24 24" class="size-5 fill-none stroke-current stroke-2" aria-hidden="true">
                        <path d="m15 18-6-6 6-6" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                </a>

                @php
                    // Tautan ke profil publik lawan bicara; akun banned tidak ditautkan.
                    $counterpartUrl = ($other !== null && !$other->is_banned) ? $other->profileUrl() : null;
                @endphp

                @if ($counterpartUrl)
                    <a href="{{ $counterpartUrl }}" class="shrink-0 rounded-full transition hover:ring-2 hover:ring-lime-400"
                        title="Lihat profil {{ $other->name }}">
                        <img src="{{ $other?->avatar_url ?? asset('images/default-avatar.png') }}" alt=""
                            class="size-9 shrink-0 rounded-full object-cover sm:size-10">
                    </a>
                @else
                    <img src="{{ $other?->avatar_url ?? asset('images/default-avatar.png') }}" alt=""
                        class="size-9 shrink-0 rounded-full object-cover sm:size-10">
                @endif

                <div class="min-w-0 flex-1">
                    <p class="truncate text-[15px] font-extrabold leading-tight text-neutral-900 sm:text-[18px]">
                        @if ($counterpartUrl)
                            <a href="{{ $counterpartUrl }}" class="hover:underline">{{ $other?->name }}</a>
                        @else
                            {{ $other?->name }}
                        @endif
                    </p>
                    <p class="truncate text-[11.5px] leading-tight text-neutral-500 sm:text-[12px]" data-presence
                        data-online="{{ $other?->isOnline() ? '1' : '0' }}"
                        data-last-seen-at="{{ $other?->last_seen_at?->toIso8601String() }}">
                        {{ $other?->isOnline() ? 'Online' : 'Terakhir dilihat' }}
                    </p>
                </div>

                <div class="relative shrink-0">
                    <button type="button" data-chat-menu-toggle
                        class="grid size-8 place-items-center rounded-full text-neutral-600 transition hover:bg-neutral-200/70 hover:text-neutral-900 sm:size-9"
                        aria-label="Opsi percakapan" aria-expanded="false">
                        <svg viewBox="0 0 24 24" class="size-5 fill-current" aria-hidden="true">
                            <circle cx="12" cy="5" r="1.9" />
                            <circle cx="12" cy="12" r="1.9" />
                            <circle cx="12" cy="19" r="1.9" />
                        </svg>
                    </button>

                    <div data-chat-menu
                        class="absolute right-0 top-full z-30 mt-2 hidden w-52 overflow-hidden rounded-xl border border-neutral-200 bg-white py-1 shadow-xl">
                        <button type="button" data-mark-read
                            class="flex w-full items-center gap-2 px-4 py-2.5 text-left text-[13px] font-medium text-neutral-700 transition hover:bg-neutral-100">
                            Tandai sudah dibaca
                        </button>
                        @if ($conversation->product)
                            <a href="{{ $conversation->product->detailUrl() }}"
                                class="flex items-center gap-2 px-4 py-2.5 text-[13px] font-medium text-neutral-700 transition hover:bg-neutral-100">
                                Lihat produk
                            </a>
                        @endif
                        <button type="button" data-scroll-latest
                            class="flex w-full items-center gap-2 px-4 py-2.5 text-left text-[13px] font-medium text-neutral-700 transition hover:bg-neutral-100">
                            Ke pesan terbaru
                        </button>
                    </div>
                </div>
            </header>

            {{-- Bagian tengah: produk, aksi transaksi, dan pesan. Disembunyikan
            saat pratinjau lampiran terbuka agar panel dapat tinggi maksimal. --}}
            <div data-attach-hide class="flex min-h-0 flex-1 flex-col">

                @php
                    $hasOrderBar = $order !== null
                        && in_array($order->status, [\App\Models\Order::STATUS_DISEPAKATI, \App\Models\Order::STATUS_SELESAI], true);

                    // Ulasan hanya dibuka setelah transaksi selesai (bukan saat menunggu COD).
                    $reviewUrl = null;
                    if ($order !== null && $order->status === \App\Models\Order::STATUS_SELESAI) {
                        $isBuyer = (string) $conversation->buyer_id === (string) auth()->id();
                        $product = $conversation->product;

                        if ($isBuyer && $product !== null) {
                            $reviewUrl = $product->detailUrl() . '#ulasan-produk';
                        } elseif (!$isBuyer && $conversation->buyer !== null) {
                            $reviewUrl = $conversation->buyer->profileUrl();
                            $sep = str_contains($reviewUrl, '?') ? '&' : '?';
                            $reviewUrl .= $sep . 'tab=ulasan&order=' . $order->id;
                        }
                    }

                    // Pembeli boleh menawar hanya sebelum ada transaksi berjalan
                    // dan produk masih bisa ditawar.
                    $isBuyerHere = (string) $conversation->buyer_id === (string) auth()->id();
                    $canOffer = $isBuyerHere
                        && $conversation->product?->status === \App\Models\Product::STATUS_DIJUAL
                        && $conversation->product?->is_negotiable
                        && ($order === null || $order->status === \App\Models\Order::STATUS_BATAL);

                    // Jumlah tombol aksi di bar kanan (ulasan + selesaikan);
                    // padding dipadatkan bila lebih dari satu agar tidak meluber.
                    $actionCount = ($reviewUrl !== null ? 1 : 0) + ($canMarkSold ? 1 : 0);
                @endphp

                {{-- Strip produk + aksi transaksi digabung dalam 1 bar kompak --}}
                <div
                    class="flex shrink-0 flex-wrap items-center justify-between gap-2 border-b border-neutral-200 {{ $hasOrderBar ? 'bg-lime-50/70' : 'bg-white' }} px-3 py-2 sm:flex-nowrap sm:gap-3 sm:px-4 sm:py-2.5">
                    <a href="{{ $conversation->product ? $conversation->product->detailUrl() : '#' }}"
                        class="group flex min-w-0 flex-1 items-center gap-2.5 sm:gap-3">
                        <div class="size-12 shrink-0 overflow-hidden rounded-md bg-neutral-200 sm:size-11">
                            @if ($conversation->product?->coverImage)
                                <img src="{{ $conversation->product->coverImage->path_url }}" alt=""
                                    class="size-full object-cover">
                            @endif
                        </div>
                        <div class="min-w-0 flex-1">
                            <p
                                class="truncate text-[16px] font-bold leading-tight text-neutral-900 group-hover:underline sm:text-[15px]">
                                {{ $conversation->product?->judul }}
                            </p>
                            <p class="mt-0.5 text-[14px] leading-tight text-neutral-600 sm:text-[13px]">
                                Rp {{ number_format((int) ($conversation->product?->harga ?? 0), 0, ',', '.') }}
                            </p>
                        </div>
                    </a>

                    <div class="flex shrink-0 items-center gap-2">
                        <div id="chat-offer-summary">
                            @include('chat.partials.offer-badge', ['offer' => $latestOffer])
                        </div>

                        @if ($hasOrderBar && ($reviewUrl || $canMarkSold))
                            <div class="flex items-center gap-1.5 border-l border-neutral-200/80 pl-2.5">
                                @if ($reviewUrl)
                                    <a href="{{ $reviewUrl }}"
                                        class="inline-flex items-center justify-center whitespace-nowrap rounded-lg bg-emerald-600 px-2.5 py-1.5 text-[12px] font-bold text-white transition hover:bg-emerald-700 sm:px-3.5 sm:py-2 sm:text-xs">
                                        {{ $myReview ? 'Edit' : 'Nilai' }}
                                    </a>
                                @endif

                                @if ($canMarkSold)
                                    <button type="button" data-complete-trigger
                                        data-complete-action="{{ route('chat.sold', $conversation) }}"
                                        data-complete-method="POST"
                                        data-complete-product="{{ $conversation->product?->judul }}"
                                        data-complete-counter="{{ $other?->name }}"
                                        class="inline-flex w-full items-center justify-center whitespace-nowrap rounded-lg border border-neutral-800 bg-white {{ $actionCount > 1 ? 'px-2.5 py-1 text-[12px] sm:px-3.5 sm:py-2 sm:text-xs' : 'px-3.5 py-2 text-xs' }} font-bold text-neutral-900 transition hover:bg-neutral-900 hover:text-white cursor-pointer">
                                        Selesaikan
                                    </button>
                                @endif
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Area pesan --}}
                <div id="chat-stage" class="relative flex min-h-0 flex-1 flex-col">
                    <div id="chat-messages" class="flex flex-1 flex-col gap-2 overflow-y-auto px-3 py-4 sm:px-4"
                        data-chat-box>
                        @php
                            $orderedMessages = $messages->values();
                        @endphp
                        @forelse ($orderedMessages as $idx => $message)
                            @php
                                $nextMsg = $orderedMessages->get($idx + 1);
                                $sameMinute = $message->created_at
                                    && $nextMsg?->created_at
                                    && intdiv($message->created_at->getTimestamp(), 60) === intdiv($nextMsg->created_at->getTimestamp(), 60);

                                $hideMeta = $nextMsg
                                    && $nextMsg->type !== \App\Models\Message::TYPE_SYSTEM
                                    && (string) $nextMsg->sender_id === (string) $message->sender_id
                                    && $sameMinute;
                            @endphp
                            @include('chat.partials.message', [
                                'message' => $message,
                                'conversation' => $conversation,
                                'myId' => auth()->id(),
                                'hideMeta' => $hideMeta,
                            ])
                        @empty
                            <p class="my-auto text-center text-[14px] text-neutral-400" data-empty-state>
                                Belum ada pesan. Sapa penjualnya dulu!
                            </p>
                        @endforelse
                    </div>
                </div>
            </div>{{-- /area yang digantikan panel pratinjau --}}

            {{-- Pratinjau lampiran ala WhatsApp: mengambil area pesan, header + input tetap terlihat. --}}
            <div data-attach-panel class="hidden min-h-0 flex-1 flex-col bg-white">
                {{-- Bar aksi di atas: tutup, penghitung, edit, dan hapus foto aktif. --}}
                <div class="flex shrink-0 items-center gap-2 border-b border-neutral-200 px-2.5 py-2.5 sm:px-3">
                    <button type="button" data-attach-close
                        class="grid size-9 shrink-0 place-items-center rounded-full bg-neutral-100 text-neutral-700 transition hover:bg-neutral-200 hover:text-neutral-900 focus-visible:outline focus-visible:outline-2 focus-visible:outline-neutral-900"
                        aria-label="Buang semua foto">
                        <svg viewBox="0 0 24 24" class="size-4.5 fill-none stroke-current stroke-2" aria-hidden="true">
                            <path d="M18 6 6 18M6 6l12 12" stroke-linecap="round" />
                        </svg>
                    </button>

                    <span
                        class="rounded-full bg-neutral-100 px-2.5 py-1 text-[11px] font-semibold leading-none text-neutral-600"
                        data-attach-counter>1 / 1</span>

                    <div class="ml-auto flex shrink-0 items-center gap-1.5">
                        <button type="button" data-attach-edit-active
                            class="inline-flex items-center gap-1.5 rounded-full bg-neutral-900 px-3 py-1.5 text-[12px] font-bold text-white transition hover:bg-neutral-800">
                            <svg viewBox="0 0 24 24" class="size-3.5 fill-none stroke-current stroke-2" aria-hidden="true">
                                <path d="m14.5 3.5 6 6L8 22H2v-6Z" stroke-linejoin="round" />
                            </svg>
                            Edit
                        </button>
                        <button type="button" data-attach-remove-active
                            class="inline-flex items-center gap-1.5 rounded-full bg-red-50 px-3 py-1.5 text-[12px] font-semibold text-red-600 transition hover:bg-red-100">
                            <svg viewBox="0 0 24 24" class="size-3.5 fill-none stroke-current stroke-2" aria-hidden="true">
                                <path d="M3 6h18M8 6V4h8v2M6 6l1 14h10l1-14" stroke-linecap="round"
                                    stroke-linejoin="round" />
                            </svg>
                            Hapus
                        </button>
                    </div>
                </div>

                {{-- Pratinjau besar: memakai seluruh sisa tinggi area pesan. --}}
                <div
                    class="relative flex min-h-0 flex-1 items-center justify-center overflow-hidden bg-white px-2 py-2 sm:px-4 sm:py-4">
                    <img alt="Pratinjau foto" data-attach-img class="max-h-full max-w-full object-contain">
                </div>
            </div>

            {{-- Input bar --}}
            <form method="POST" action="{{ route('chat.messages.store', $conversation) }}" enctype="multipart/form-data"
                data-chat-form class="shrink-0 border-t border-neutral-200 bg-white px-3 py-2.5 sm:px-4 sm:py-3">
                @csrf
                <input type="file" accept="image/jpeg,image/png,image/webp,image/gif" multiple class="hidden"
                    data-chat-image aria-label="Lampirkan gambar">

                {{-- Strip lampiran: thumbnail foto terpilih + tombol tambah, di tengah. --}}
                <div data-attach-strip class="mb-2 hidden overflow-x-auto pb-1">
                    <div data-attach-list class="mx-auto flex w-max items-center gap-2 px-0.5" role="list"></div>
                </div>

                <div class="flex items-end gap-2.5">
                    <div
                        class="flex min-w-0 flex-1 items-center gap-2 rounded-full border border-neutral-300 bg-white py-1.5 pl-4 pr-1.5 transition focus-within:border-neutral-400">
                        <span class="hidden shrink-0 text-[14px] font-bold text-neutral-800" data-offer-prefix>Rp.</span>

                        <textarea rows="1" maxlength="2000" autocomplete="off"
                            class="max-h-28 min-w-0 flex-1 resize-none bg-transparent py-1 text-[15px] leading-snug text-neutral-900 outline-none placeholder:text-neutral-400"
                            placeholder="Ketik pesan" data-chat-input></textarea>

                        <button type="button" data-chat-image-btn
                            class="grid size-8 shrink-0 place-items-center rounded-full text-neutral-500 transition hover:bg-neutral-100 hover:text-neutral-900"
                            aria-label="Lampirkan gambar" title="Lampirkan gambar">
                            <svg viewBox="0 0 24 24" class="size-4.5 fill-none stroke-current stroke-2" aria-hidden="true">
                                <path
                                    d="m21.44 11.05-9.19 9.19a6 6 0 0 1-8.49-8.49l8.57-8.57A4 4 0 1 1 18 8.84l-8.59 8.57a2 2 0 0 1-2.83-2.83l8.49-8.48"
                                    stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                        </button>

                        @if ($canOffer)
                            <button type="button" data-offer-mode-toggle
                                class="grid size-8 shrink-0 place-items-center rounded-full text-neutral-700 transition hover:bg-neutral-100"
                                aria-pressed="false" aria-label="Mode tawar harga" title="Ajukan tawaran harga">
                                <span class="text-[13px] font-extrabold" data-offer-mode-icon>$</span>
                                <svg viewBox="0 0 24 24" class="hidden size-4 fill-none stroke-current stroke-2"
                                    data-offer-mode-close aria-hidden="true">
                                    <path d="M18 6 6 18M6 6l12 12" stroke-linecap="round" />
                                </svg>
                            </button>
                        @endif
                    </div>

                    <button type="submit" data-chat-submit
                        class="grid size-11 shrink-0 place-items-center rounded-full bg-lime-500 text-neutral-900 transition hover:bg-lime-600 disabled:cursor-not-allowed disabled:opacity-60"
                        aria-label="Kirim pesan">
                        <svg viewBox="0 0 24 24" class="size-5 fill-current" aria-hidden="true">
                            <path
                                d="M2.5 2.6a.6.6 0 0 1 .8-.7l18 8.4a.6.6 0 0 1 0 1.1l-18 8.4a.6.6 0 0 1-.8-.7l1.7-7.3a.6.6 0 0 1 .5-.4l8.9-1.4a.35.35 0 0 0 0-.7L5.2 8.7a.6.6 0 0 1-.5-.4Z" />
                        </svg>
                    </button>
                </div>
            </form>
        </section>
    </div>

    {{-- Editor foto: crop, blur area sensitif, dan spidol warna. --}}
    <dialog id="chat-image-editor" data-image-editor
        class="m-auto w-[calc(100%-1rem)] max-w-5xl rounded-2xl bg-transparent p-0 backdrop:bg-neutral-900/70">
        <div class="flex h-[min(92dvh,900px)] flex-col overflow-hidden rounded-2xl bg-white">
            <header class="flex shrink-0 items-center justify-between gap-2 border-b border-neutral-200 px-3 py-2.5">
                <button type="button" data-editor-cancel
                    class="rounded-lg px-3 py-1.5 text-[13px] font-semibold text-neutral-600 transition hover:bg-neutral-100">
                    Batal
                </button>
                <p class="text-[13px] font-bold text-neutral-900">Edit foto</p>
                <button type="button" data-editor-save
                    class="rounded-lg bg-lime-500 px-3.5 py-1.5 text-[13px] font-bold text-neutral-900 transition hover:bg-lime-600 disabled:cursor-not-allowed disabled:opacity-60">
                    Selesai
                </button>
            </header>

            {{-- Toolbar di atas kanvas supaya area foto dapat tinggi maksimal. --}}
            <div class="shrink-0 space-y-2 border-b border-neutral-200 px-3 py-2.5">
                <div class="flex items-center gap-1.5 overflow-x-auto pb-0.5">
                    <button type="button" data-editor-tool="crop" aria-pressed="false" title="Crop foto"
                        class="flex shrink-0 items-center gap-1.5 rounded-lg px-2.5 py-1.5 text-[12px] font-semibold text-neutral-700 transition hover:bg-neutral-100 aria-pressed:bg-neutral-900 aria-pressed:text-white">
                        <svg viewBox="0 0 24 24" class="size-4 fill-none stroke-current stroke-2" aria-hidden="true">
                            <path d="M6 2v14a2 2 0 0 0 2 2h14M18 22V8a2 2 0 0 0-2-2H2" stroke-linecap="round"
                                stroke-linejoin="round" />
                        </svg>
                        Crop
                    </button>
                    <button type="button" data-editor-tool="blur" aria-pressed="false" title="Blur bagian sensitif"
                        class="flex shrink-0 items-center gap-1.5 rounded-lg px-2.5 py-1.5 text-[12px] font-semibold text-neutral-700 transition hover:bg-neutral-100 aria-pressed:bg-neutral-900 aria-pressed:text-white">
                        <svg viewBox="0 0 24 24" class="size-4 fill-none stroke-current stroke-2" aria-hidden="true">
                            <path d="M12 3s6 6.4 6 10a6 6 0 0 1-12 0c0-3.6 6-10 6-10Z" stroke-linejoin="round" />
                        </svg>
                        Blur
                    </button>
                    <button type="button" data-editor-tool="pen" aria-pressed="false" title="Spidol warna"
                        class="flex shrink-0 items-center gap-1.5 rounded-lg px-2.5 py-1.5 text-[12px] font-semibold text-neutral-700 transition hover:bg-neutral-100 aria-pressed:bg-neutral-900 aria-pressed:text-white">
                        <svg viewBox="0 0 24 24" class="size-4 fill-none stroke-current stroke-2" aria-hidden="true">
                            <path d="m14.5 3.5 6 6L8 22H2v-6Z" stroke-linejoin="round" />
                        </svg>
                        Spidol
                    </button>

                    <span class="mx-0.5 h-6 w-px shrink-0 bg-neutral-200"></span>

                    <button type="button" data-editor-undo title="Urungkan"
                        class="grid size-8 shrink-0 place-items-center rounded-lg text-neutral-600 transition hover:bg-neutral-100 disabled:opacity-40"
                        aria-label="Urungkan">
                        <svg viewBox="0 0 24 24" class="size-4 fill-none stroke-current stroke-2" aria-hidden="true">
                            <path d="m9 14-5-5 5-5" stroke-linecap="round" stroke-linejoin="round" />
                            <path d="M4 9h10a6 6 0 0 1 0 12h-3" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                    </button>
                    <button type="button" data-editor-reset
                        class="shrink-0 rounded-lg px-2.5 py-1.5 text-[12px] font-semibold text-neutral-600 transition hover:bg-neutral-100">
                        Reset
                    </button>
                </div>

                {{-- Pengaturan kuas: ukuran + 8 warna preset (tanpa color picker). --}}
                <div data-editor-brush class="hidden flex-wrap items-center gap-2.5">
                    <input type="range" min="2" max="20" value="6" step="1" data-editor-size
                        class="h-1 w-24 shrink-0 cursor-pointer accent-neutral-900" aria-label="Ukuran kuas">
                    <div data-editor-colors class="flex flex-wrap items-center gap-1.5">
                        @foreach ([
                                ['#4B4B4B', 'Abu tua'],
                                ['#A6A6A6', 'Abu muda'],
                                ['#FFFFFF', 'Putih'],
                                ['#29B6F6', 'Biru'],
                                ['#4CAF50', 'Hijau'],
                                ['#AB7BFF', 'Ungu'],
                                ['#FF9800', 'Oranye'],
                                ['#F44336', 'Merah'],
                            ] as [$hex, $label])
                            <button type="button" data-editor-color="{{ $hex }}" title="{{ $label }}"
                                aria-label="Warna {{ $label }}"
                                class="size-7 shrink-0 rounded-full border border-black/15 transition hover:scale-105 aria-pressed:ring-2 aria-pressed:ring-neutral-900 aria-pressed:ring-offset-2"
                                style="background-color: {{ $hex }}"></button>
                        @endforeach
                    </div>
                </div>

                {{-- Pengaturan crop: rasio + terapkan. --}}
                <div data-editor-crop class="hidden items-center gap-1.5 overflow-x-auto">
                    <button type="button" data-crop-aspect="free"
                        class="shrink-0 rounded-lg border border-neutral-300 px-2.5 py-1.5 text-[12px] font-semibold text-neutral-700 transition hover:bg-neutral-100 aria-pressed:border-neutral-900 aria-pressed:bg-neutral-900 aria-pressed:text-white">
                        Bebas
                    </button>
                    <button type="button" data-crop-aspect="1"
                        class="shrink-0 rounded-lg border border-neutral-300 px-2.5 py-1.5 text-[12px] font-semibold text-neutral-700 transition hover:bg-neutral-100 aria-pressed:border-neutral-900 aria-pressed:bg-neutral-900 aria-pressed:text-white">
                        1:1
                    </button>
                    <button type="button" data-crop-aspect="1.3333"
                        class="shrink-0 rounded-lg border border-neutral-300 px-2.5 py-1.5 text-[12px] font-semibold text-neutral-700 transition hover:bg-neutral-100 aria-pressed:border-neutral-900 aria-pressed:bg-neutral-900 aria-pressed:text-white">
                        4:3
                    </button>
                    <button type="button" data-crop-aspect="1.7778"
                        class="shrink-0 rounded-lg border border-neutral-300 px-2.5 py-1.5 text-[12px] font-semibold text-neutral-700 transition hover:bg-neutral-100 aria-pressed:border-neutral-900 aria-pressed:bg-neutral-900 aria-pressed:text-white">
                        16:9
                    </button>
                    <button type="button" data-crop-apply
                        class="ml-auto shrink-0 rounded-lg bg-lime-500 px-3 py-1.5 text-[12px] font-bold text-neutral-900 transition hover:bg-lime-600">
                        Terapkan crop
                    </button>
                </div>
            </div>

            <div data-editor-viewport
                class="relative flex min-h-0 flex-1 items-center justify-center overflow-hidden bg-neutral-950 p-3 sm:p-4">
                <div data-editor-stage class="relative inline-block leading-none">
                    <canvas data-editor-canvas class="block touch-none select-none"></canvas>
                    <canvas data-editor-overlay
                        class="absolute inset-0 hidden h-full w-full touch-none select-none"></canvas>
                </div>
            </div>
        </div>
    </dialog>

    {{-- Lightbox gambar yang sudah terkirim: layar penuh putih, panah kiri/kanan. --}}
    <dialog id="chat-lightbox" data-lightbox
        class="h-full max-h-none w-full max-w-none bg-transparent p-0 backdrop:bg-white/95">
        <div class="flex h-full w-full flex-col bg-white text-neutral-900">
            <div class="flex shrink-0 items-center justify-between gap-3 px-4 py-3">
                <span class="text-[13px] font-semibold text-neutral-500" data-lightbox-counter>Foto</span>
                <button type="button" data-lightbox-close
                    class="grid size-9 place-items-center rounded-full text-neutral-600 transition hover:bg-neutral-100 hover:text-neutral-900"
                    aria-label="Tutup">
                    <svg viewBox="0 0 24 24" class="size-5 fill-none stroke-current stroke-2" aria-hidden="true">
                        <path d="M18 6 6 18M6 6l12 12" stroke-linecap="round" />
                    </svg>
                </button>
            </div>
            <div class="relative flex min-h-0 flex-1 items-center justify-center px-3 pb-3 sm:px-16 sm:pb-5">
                <button type="button" data-lightbox-prev
                    class="absolute left-2 z-10 grid size-11 place-items-center rounded-full bg-neutral-100 text-neutral-700 shadow-sm transition hover:bg-neutral-200 hover:text-neutral-900 disabled:opacity-30 sm:left-5"
                    aria-label="Foto sebelumnya">
                    <svg viewBox="0 0 24 24" class="size-5 fill-none stroke-current stroke-2" aria-hidden="true">
                        <path d="m15 18-6-6 6-6" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                </button>
                <img alt="Gambar pesan" data-lightbox-img class="max-h-full max-w-full rounded-lg object-contain">
                <button type="button" data-lightbox-next
                    class="absolute right-2 z-10 grid size-11 place-items-center rounded-full bg-neutral-100 text-neutral-700 shadow-sm transition hover:bg-neutral-200 hover:text-neutral-900 disabled:opacity-30 sm:right-5"
                    aria-label="Foto berikutnya">
                    <svg viewBox="0 0 24 24" class="size-5 fill-none stroke-current stroke-2" aria-hidden="true">
                        <path d="m9 18 6-6-6-6" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                </button>
            </div>
            <p data-lightbox-caption
                class="hidden shrink-0 px-6 pb-4 text-center text-[13px] leading-snug text-neutral-500"></p>
        </div>
    </dialog>

    {{-- Modal ubah nominal tawaran --}}
    <dialog id="chat-offer-modal" data-offer-modal
        class="m-auto w-[calc(100%-2rem)] max-w-sm rounded-2xl bg-transparent p-0 backdrop:bg-neutral-900/50">
        <form data-offer-form class="rounded-2xl bg-white p-5">
            <h2 class="text-[17px] font-extrabold text-neutral-900">Ubah tawaran</h2>
            <p class="mt-1 text-[13px] text-neutral-500">
                Harga penjual Rp {{ number_format((int) ($conversation->product?->harga ?? 0), 0, ',', '.') }}
            </p>

            <label
                class="mt-4 flex items-center gap-2 rounded-xl border border-neutral-300 px-3 py-2.5 focus-within:border-neutral-900">
                <span class="text-[13px] font-bold text-neutral-800">Rp</span>
                <input type="text" inputmode="numeric" autocomplete="off" data-offer-input
                    class="min-w-0 flex-1 bg-transparent text-[15px] font-bold text-neutral-900 outline-none"
                    placeholder="0">
            </label>

            <p class="mt-2 hidden text-[12px] font-medium text-error" data-offer-error></p>

            <div class="mt-5 flex gap-2">
                <button type="button" data-offer-modal-cancel
                    class="flex-1 rounded-xl border border-neutral-300 py-2.5 text-[13px] font-semibold text-neutral-800 transition hover:bg-neutral-100">
                    Batal
                </button>
                <button type="submit"
                    class="flex-1 rounded-xl bg-lime-500 py-2.5 text-[13px] font-bold text-neutral-900 transition hover:bg-lime-600">
                    Simpan
                </button>
            </div>
        </form>
    </dialog>

    {{-- Modal Selesaikan Transaksi (dipakai tombol aksi penjual di chat) --}}
    <x-order-complete-modal />
@endsection