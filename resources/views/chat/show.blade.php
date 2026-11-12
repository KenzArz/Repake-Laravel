@extends('layouts.app')

@section('title', 'Chat - RePake')

@section('content')
<div class="mx-auto max-w-7xl sm:px-6 lg:px-6 lg:py-4">
    {{-- Kerangka dua panel yang sama dengan chat.index, agar lebar panel stabil --}}
    <div class="bg-neutral-50 lg:flex lg:h-[calc(100vh-11rem)] lg:min-h-[560px] lg:overflow-hidden lg:rounded-2xl lg:border lg:border-neutral-200">
        {{-- Kiri: daftar percakapan (desktop) --}}
        <aside class="hidden lg:block lg:w-[400px] lg:shrink-0 lg:overflow-y-auto lg:border-r lg:border-neutral-200 lg:p-3">
            @include('chat.partials.sidebar', [
                'conversations' => $sidebarConversations,
                'activeId' => $conversation->id,
                'myId' => auth()->id(),
                'filter' => $filter ?? 'semua',
            ])
        </aside>

        {{-- Kanan: ruang chat --}}
        <section id="chat-app"
                 class="relative flex h-[calc(100vh-9rem)] flex-1 flex-col overflow-hidden bg-neutral-50 pb-20 md:pb-0 lg:h-auto"
                 data-my-id="{{ auth()->id() }}"
                 data-seller-id="{{ $conversation->seller_id }}"
                 data-conversation-id="{{ $conversation->id }}"
                 data-send-url="{{ route('chat.messages.store', $conversation) }}"
                 data-poll-url="{{ route('chat.messages.poll', $conversation) }}"
                 data-read-url="{{ route('chat.read', $conversation) }}"
                 data-other-avatar="{{ $other?->avatar_url ?? asset('images/default-avatar.png') }}"
                 data-product-price="{{ (int) ($conversation->product?->harga ?? 0) }}"
                 data-empty-label="Belum ada pesan. Sapa penjualnya dulu!">

            {{-- Header --}}
            <header class="flex shrink-0 items-center gap-3 border-b border-neutral-200 px-3 py-2.5 sm:px-4 sm:py-3">
                <a href="{{ route('chat.index') }}"
                   class="grid size-8 shrink-0 place-items-center rounded-full text-neutral-600 transition hover:bg-neutral-200/70 hover:text-neutral-900 lg:hidden"
                   aria-label="Kembali ke daftar pesan">
                    <svg viewBox="0 0 24 24" class="size-5 fill-none stroke-current stroke-2" aria-hidden="true"><path d="m15 18-6-6 6-6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </a>

                <img src="{{ $other?->avatar_url ?? asset('images/default-avatar.png') }}"
                     alt="" class="size-10 shrink-0 rounded-full object-cover">

                <div class="min-w-0 flex-1">
                    <p class="truncate text-[17px] font-extrabold leading-tight text-neutral-900 sm:text-[18px]">{{ $other?->name }}</p>
                    <p class="truncate text-[12px] leading-tight text-neutral-500"
                       data-presence
                       data-online="{{ $other?->isOnline() ? '1' : '0' }}"
                       data-last-seen-at="{{ $other?->last_seen_at?->toIso8601String() }}">
                        {{ $other?->isOnline() ? 'Online' : 'Terakhir dilihat' }}
                    </p>
                </div>

                <div class="relative shrink-0">
                    <button type="button" data-chat-menu-toggle
                            class="grid size-9 place-items-center rounded-full text-neutral-600 transition hover:bg-neutral-200/70 hover:text-neutral-900"
                            aria-label="Opsi percakapan" aria-expanded="false">
                        <svg viewBox="0 0 24 24" class="size-5 fill-current" aria-hidden="true">
                            <circle cx="12" cy="5" r="1.9"/><circle cx="12" cy="12" r="1.9"/><circle cx="12" cy="19" r="1.9"/>
                        </svg>
                    </button>

                    <div data-chat-menu class="absolute right-0 top-full z-30 mt-2 hidden w-52 overflow-hidden rounded-xl border border-neutral-200 bg-white py-1 shadow-xl">
                        <button type="button" data-mark-read class="flex w-full items-center gap-2 px-4 py-2.5 text-left text-[13px] font-medium text-neutral-700 transition hover:bg-neutral-100">
                            Tandai sudah dibaca
                        </button>
                        @if ($conversation->product)
                            <a href="{{ route('products.show', $conversation->product->id) }}" class="flex items-center gap-2 px-4 py-2.5 text-[13px] font-medium text-neutral-700 transition hover:bg-neutral-100">
                                Lihat produk
                            </a>
                        @endif
                        <button type="button" data-scroll-latest class="flex w-full items-center gap-2 px-4 py-2.5 text-left text-[13px] font-medium text-neutral-700 transition hover:bg-neutral-100">
                            Ke pesan terbaru
                        </button>
                    </div>
                </div>
            </header>

            {{-- Strip produk: menempel di atas, tidak ikut scroll --}}
            <a href="{{ $conversation->product ? route('products.show', $conversation->product->id) : '#' }}"
               class="flex shrink-0 items-center gap-3 border-b border-neutral-200 px-3 py-2.5 transition hover:bg-neutral-100/60 sm:px-4">
                <div class="size-11 shrink-0 overflow-hidden rounded-md bg-neutral-200">
                    @if ($conversation->product?->coverImage)
                        <img src="{{ $conversation->product->coverImage->path_url }}" alt="" class="size-full object-cover">
                    @endif
                </div>
                <div class="min-w-0 flex-1">
                    <p class="truncate text-[15px] font-bold leading-tight text-neutral-900">{{ $conversation->product?->judul }}</p>
                    <p class="mt-0.5 text-[13px] leading-tight text-neutral-600">
                        Rp {{ number_format((int) ($conversation->product?->harga ?? 0), 0, ',', '.') }}
                    </p>
                </div>
                <div id="chat-offer-summary">
                    @include('chat.partials.offer-badge', ['offer' => $latestOffer])
                </div>
            </a>

            {{-- Area pesan --}}
            <div id="chat-messages"
                 class="flex flex-1 flex-col gap-2 overflow-y-auto px-3 py-4 sm:px-4"
                 data-chat-box>
                @forelse ($messages as $message)
                    @include('chat.partials.message', ['message' => $message, 'conversation' => $conversation, 'myId' => auth()->id()])
                @empty
                    <p class="my-auto text-center text-[14px] text-neutral-400" data-empty-state>
                        Belum ada pesan. Sapa penjualnya dulu!
                    </p>
                @endforelse
            </div>

            {{-- Input bar --}}
            <form method="POST" action="{{ route('chat.messages.store', $conversation) }}"
                  enctype="multipart/form-data"
                  data-chat-form
                  class="shrink-0 border-t border-neutral-200 bg-white px-3 py-2.5 sm:px-4 sm:py-3">
                @csrf
                <input type="file" accept="image/jpeg,image/png,image/webp,image/gif" class="hidden" data-chat-image aria-label="Lampirkan gambar">

                <div class="flex items-end gap-2.5">
                    <div class="flex min-w-0 flex-1 items-center gap-2 rounded-full border border-neutral-300 bg-white py-1.5 pl-2 pr-1.5 transition focus-within:border-neutral-400">
                        <span class="hidden size-7 shrink-0 place-items-center rounded-lg border border-neutral-900/40 text-[10px] font-extrabold leading-none text-neutral-800 sm:grid">Rp</span>
                        <span class="hidden shrink-0 text-[14px] font-bold text-neutral-800" data-offer-prefix>Rp.</span>

                        <textarea rows="1" maxlength="2000" autocomplete="off"
                                  class="max-h-28 min-w-0 flex-1 resize-none bg-transparent py-1 text-[15px] leading-snug text-neutral-900 outline-none placeholder:text-neutral-400"
                                  placeholder="Ketik pesan" data-chat-input></textarea>

                        <button type="button" data-chat-image-btn
                                class="grid size-8 shrink-0 place-items-center rounded-full text-neutral-500 transition hover:bg-neutral-100 hover:text-neutral-900"
                                aria-label="Lampirkan gambar" title="Lampirkan gambar">
                            <svg viewBox="0 0 24 24" class="size-4.5 fill-none stroke-current stroke-2" aria-hidden="true">
                                <path d="m21.44 11.05-9.19 9.19a6 6 0 0 1-8.49-8.49l8.57-8.57A4 4 0 1 1 18 8.84l-8.59 8.57a2 2 0 0 1-2.83-2.83l8.49-8.48" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </button>

                        <button type="button" data-offer-mode-toggle
                                class="grid size-8 shrink-0 place-items-center rounded-full text-neutral-700 transition hover:bg-neutral-100"
                                aria-pressed="false" aria-label="Mode tawar harga" title="Ajukan tawaran harga">
                            <span class="text-[13px] font-extrabold" data-offer-mode-icon>$</span>
                            <svg viewBox="0 0 24 24" class="hidden size-4 fill-none stroke-current stroke-2" data-offer-mode-close aria-hidden="true">
                                <path d="M18 6 6 18M6 6l12 12" stroke-linecap="round"/>
                            </svg>
                        </button>
                    </div>

                    <button type="submit" data-chat-submit
                            class="grid size-11 shrink-0 place-items-center rounded-full bg-lime-500 text-neutral-900 transition hover:bg-lime-600 disabled:cursor-not-allowed disabled:opacity-60"
                            aria-label="Kirim pesan">
                        <svg viewBox="0 0 24 24" class="size-5 fill-current" aria-hidden="true">
                            <path d="M2.5 2.6a.6.6 0 0 1 .8-.7l18 8.4a.6.6 0 0 1 0 1.1l-18 8.4a.6.6 0 0 1-.8-.7l1.7-7.3a.6.6 0 0 1 .5-.4l8.9-1.4a.35.35 0 0 0 0-.7L5.2 8.7a.6.6 0 0 1-.5-.4Z"/>
                        </svg>
                    </button>
                </div>
            </form>
        </section>
    </div>
</div>

{{-- Pratinjau gambar sebelum dikirim (ala WhatsApp) --}}
<dialog id="chat-image-preview" data-image-preview
        class="h-full max-h-none w-full max-w-none bg-transparent p-0 backdrop:bg-black/90">
    <div class="flex h-full w-full flex-col bg-neutral-950">
        <div class="flex items-center justify-between px-4 py-3 text-white">
            <button type="button" data-preview-cancel class="grid size-9 place-items-center rounded-full transition hover:bg-white/10" aria-label="Batal">
                <svg viewBox="0 0 24 24" class="size-5 fill-none stroke-current stroke-2" aria-hidden="true"><path d="M18 6 6 18M6 6l12 12" stroke-linecap="round"/></svg>
            </button>
            <span class="text-[13px] font-semibold text-white/80">Pratinjau gambar</span>
            <span class="size-9"></span>
        </div>

        <div class="flex min-h-0 flex-1 items-center justify-center p-3">
            <img alt="Pratinjau gambar" data-preview-img class="max-h-full max-w-full object-contain">
        </div>

        <div class="flex items-end gap-2 px-3 pb-4 pt-2">
            <div class="flex min-w-0 flex-1 items-center rounded-full bg-white/10 px-4 py-2.5">
                <textarea rows="1" data-preview-caption placeholder="Tambah keterangan..."
                          class="max-h-24 w-full resize-none bg-transparent text-[14px] text-white outline-none placeholder:text-white/50"></textarea>
            </div>
            <button type="button" data-preview-send
                    class="grid size-11 shrink-0 place-items-center rounded-full bg-lime-500 text-neutral-900 transition hover:bg-lime-600 disabled:opacity-60"
                    aria-label="Kirim gambar">
                <svg viewBox="0 0 24 24" class="size-5 fill-current" aria-hidden="true">
                    <path d="M2.5 2.6a.6.6 0 0 1 .8-.7l18 8.4a.6.6 0 0 1 0 1.1l-18 8.4a.6.6 0 0 1-.8-.7l1.7-7.3a.6.6 0 0 1 .5-.4l8.9-1.4a.35.35 0 0 0 0-.7L5.2 8.7a.6.6 0 0 1-.5-.4Z"/>
                </svg>
            </button>
        </div>
    </div>
</dialog>

{{-- Lightbox gambar yang sudah terkirim --}}
<dialog id="chat-lightbox" data-lightbox
        class="h-full max-h-none w-full max-w-none bg-transparent p-0 backdrop:bg-black/90">
    <div class="flex h-full w-full flex-col bg-neutral-950">
        <div class="flex items-center justify-between px-4 py-3 text-white">
            <span class="text-[13px] font-semibold text-white/80">Foto</span>
            <button type="button" data-lightbox-close class="grid size-9 place-items-center rounded-full transition hover:bg-white/10" aria-label="Tutup">
                <svg viewBox="0 0 24 24" class="size-5 fill-none stroke-current stroke-2" aria-hidden="true"><path d="M18 6 6 18M6 6l12 12" stroke-linecap="round"/></svg>
            </button>
        </div>
        <div class="flex min-h-0 flex-1 items-center justify-center p-2">
            <img alt="Gambar pesan" data-lightbox-img class="max-h-full max-w-full object-contain">
        </div>
    </div>
</dialog>

{{-- Modal ubah nominal tawaran --}}
<dialog id="chat-offer-modal" data-offer-modal
        class="w-[calc(100%-2rem)] max-w-sm rounded-2xl bg-transparent p-0 backdrop:bg-neutral-900/50">
    <form data-offer-form class="rounded-2xl bg-white p-5">
        <h2 class="text-[17px] font-extrabold text-neutral-900">Ubah tawaran</h2>
        <p class="mt-1 text-[13px] text-neutral-500">
            Harga penjual Rp {{ number_format((int) ($conversation->product?->harga ?? 0), 0, ',', '.') }}
        </p>

        <label class="mt-4 flex items-center gap-2 rounded-xl border border-neutral-300 px-3 py-2.5 focus-within:border-neutral-900">
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
@endsection
