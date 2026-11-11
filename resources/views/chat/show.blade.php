@extends('layouts.app')

@section('title', 'Chat - RePake')

@section('content')
<div class="mx-auto max-w-7xl sm:px-6 lg:px-6 lg:py-4">
    {{-- Kerangka dua panel yang sama dengan chat.index, agar lebar panel stabil --}}
    <div class="lg:flex lg:h-[calc(100vh-11rem)] lg:min-h-[560px] lg:overflow-hidden lg:rounded-3xl lg:border lg:border-neutral-200">
        {{-- Kiri: daftar percakapan (desktop) --}}
        <aside class="hidden bg-neutral-50 p-3 sm:p-4 lg:block lg:w-96 lg:shrink-0 lg:overflow-y-auto lg:border-r lg:border-neutral-200">
            @include('chat.partials.sidebar', [
                'conversations' => $sidebarConversations,
                'activeId' => $conversation->id,
                'myId' => auth()->id(),
            ])
        </aside>

        {{-- Kanan: ruang chat --}}
        <section class="flex min-h-[calc(100vh-160px)] flex-1 flex-col overflow-hidden bg-white lg:min-h-0">
            {{-- Header: lawan bicara --}}
            <div class="flex items-center gap-3 border-b border-neutral-200 px-4 py-3">
                <a href="{{ route('chat.index') }}" class="rounded-md p-1.5 text-neutral-500 transition hover:bg-neutral-100 hover:text-neutral-900 lg:hidden" aria-label="Kembali ke daftar pesan">
                    <svg viewBox="0 0 24 24" class="size-5 fill-none stroke-current stroke-2 [stroke-linecap:round] [stroke-linejoin:round]" aria-hidden="true">
                        <path d="m15 18-6-6 6-6"/>
                    </svg>
                </a>
                <img src="{{ $other && $other->avatar ? asset($other->avatar) : asset('images/default-avatar.png') }}"
                     alt="" class="size-10 rounded-full object-cover">
                <div class="min-w-0">
                    <p class="truncate text-body font-bold text-neutral-900">{{ $other?->name }}</p>
                </div>
            </div>

            {{-- Strip produk + status tawaran --}}
            <a href="{{ $conversation->product ? route('products.show', $conversation->product->id) : '#' }}"
               class="flex items-center gap-3 border-b border-neutral-200 px-4 py-3 transition hover:bg-neutral-50">
                <div class="size-12 shrink-0 overflow-hidden rounded-lg bg-neutral-100">
                    @if ($conversation->product?->coverImage)
                        <img src="{{ $conversation->product->coverImage->path_url }}" alt="" class="size-full object-cover">
                    @endif
                </div>
                <div class="min-w-0 flex-1">
                    <p class="truncate text-label font-semibold text-neutral-900">{{ $conversation->product?->judul }}</p>
                    <p class="text-label text-neutral-600">Rp {{ number_format($conversation->product?->harga ?? 0, 0, ',', '.') }}</p>
                </div>
                @if ($pendingOffer)
                    <div class="shrink-0 text-right">
                        <span class="inline-block rounded-full bg-warning px-2.5 py-0.5 text-small-label font-semibold">Ditawar</span>
                        <p class="mt-0.5 text-label font-bold text-neutral-900">Rp {{ number_format($pendingOffer->offer_amount, 0, ',', '.') }}</p>
                    </div>
                @endif
            </a>

            {{-- Area pesan --}}
            <div id="chat-messages"
                 class="flex flex-1 flex-col gap-2 overflow-y-auto bg-neutral-50 p-4"
                 data-chat-box
                 data-my-id="{{ auth()->id() }}"
                 data-seller-id="{{ $conversation->seller_id }}"
                 data-conversation-id="{{ $conversation->id }}"
                 data-poll-url="{{ route('chat.messages.poll', $conversation) }}"
                 data-offer-decision-url="{{ url('/chat/'.$conversation->id.'/messages/:message/:decision') }}"
                 data-offer-cancel-url="{{ url('/chat/'.$conversation->id.'/messages/:message/cancel') }}"
                 data-other-avatar="{{ $other && $other->avatar ? asset($other->avatar) : asset('images/default-avatar.png') }}">
                @forelse ($messages as $message)
                    @include('chat.partials.message', ['message' => $message, 'conversation' => $conversation])
                @empty
                    <p class="my-auto text-center text-body text-neutral-400" data-empty-state>
                        Belum ada pesan. Sapa penjualnya dulu!
                    </p>
                @endforelse
            </div>

            {{-- Input bar: pil + tombol kirim bulat (Figma) --}}
            <form method="POST" action="{{ route('chat.messages.store', $conversation) }}"
                  class="flex items-center gap-3 border-t border-neutral-200 bg-white px-4 py-3"
                  data-chat-form enctype="multipart/form-data">
                @csrf
                <div class="flex flex-1 items-center gap-2 rounded-full border border-neutral-300 bg-white px-2 py-1.5">
                    <button type="button"
                            class="grid size-8 shrink-0 place-items-center rounded-full border border-neutral-300 text-neutral-600 transition hover:border-neutral-500 hover:text-neutral-900"
                            data-chat-offer-btn data-offer-url="{{ route('chat.messages.store', $conversation) }}"
                            aria-label="Kirim tawaran harga" title="Kirim tawaran harga">
                        <svg viewBox="0 0 24 24" class="size-4 fill-none stroke-current stroke-2 [stroke-linecap:round]" aria-hidden="true">
                            <circle cx="12" cy="12" r="9"/><path d="M14.5 9.5A2.5 2.5 0 0 0 12 8H11a2 2 0 0 0 0 4h2a2 2 0 0 1 0 4h-1a2.5 2.5 0 0 1-2.5-2.5"/><path d="M12 6.5v11"/>
                        </svg>
                    </button>
                    <input type="text" name="body" autocomplete="off" maxlength="2000"
                           class="min-w-0 flex-1 bg-transparent text-body text-neutral-900 outline-none"
                           placeholder="Tulis pesan atau nominal tawar..." data-chat-input>
                    <button type="button"
                            class="grid size-8 shrink-0 place-items-center rounded-full text-neutral-500 transition hover:bg-neutral-100 hover:text-neutral-900"
                            aria-label="Lampirkan gambar" title="Lampirkan gambar" data-chat-image-btn>
                        <svg viewBox="0 0 24 24" class="size-4.5 fill-none stroke-current stroke-2 [stroke-linecap:round] [stroke-linejoin:round]" aria-hidden="true">
                            <path d="m21.44 11.05-9.19 9.19a6 6 0 0 1-8.49-8.49l8.57-8.57A4 4 0 1 1 18 8.84l-8.59 8.57a2 2 0 0 1-2.83-2.83l8.49-8.48"/>
                        </svg>
                    </button>
                    <input type="file" name="image" accept="image/jpeg,image/png,image/webp"
                           class="hidden" data-chat-image aria-label="Lampirkan gambar">
                </div>
                <button type="submit"
                        class="grid size-11 shrink-0 place-items-center rounded-full bg-lime-500 text-neutral-900 transition hover:bg-lime-600"
                        aria-label="Kirim pesan">
                    <svg viewBox="0 0 24 24" class="size-5 fill-none stroke-current stroke-2 [stroke-linecap:round] [stroke-linejoin:round]" aria-hidden="true">
                        <path d="m22 2-7 20-4-9-9-4Z"/><path d="M22 2 11 13"/>
                    </svg>
                </button>
            </form>
        </section>
    </div>
</div>

@push('scripts')
<script>
    (function () {
        var box = document.querySelector('[data-chat-box]');
        var form = document.querySelector('[data-chat-form]');
        var input = form.querySelector('[data-chat-input]');
        var imageInput = form.querySelector('[data-chat-image]');
        var imageBtn = form.querySelector('[data-chat-image-btn]');
        if (!box || !form) return;

        var myId = Number(box.dataset.myId);
        var sellerId = box.dataset.sellerId === undefined ? null : Number(box.dataset.sellerId);
        var conversationId = box.dataset.conversationId;
        var otherAvatar = box.dataset.otherAvatar;
        var sendUrl = form.getAttribute('action');
        var pollUrl = box.dataset.pollUrl;
        var offerDecisionUrl = box.dataset.offerDecisionUrl;
        var offerCancelUrl = box.dataset.offerCancelUrl;
        var csrfToken = document.querySelector('meta[name="csrf-token"]').content;

        var lastId = 0;
        var idNodes = box.querySelectorAll('[data-message-id]');
        if (idNodes.length) lastId = Number(idNodes[idNodes.length - 1].dataset.messageId);

        function scrollToBottom() { box.scrollTop = box.scrollHeight; }
        scrollToBottom();

        function escapeHtml(value) {
            var div = document.createElement('div');
            div.textContent = value == null ? '' : String(value);
            return div.innerHTML;
        }

        function offerStatusText(status) {
            return {
                pending: 'Menunggu keputusan penjual',
                accepted: 'Diterima ✅',
                rejected: 'Ditolak ❌',
                cancelled: 'Dibatalkan',
            }[status] || '';
        }

        function el(tag, className, text) {
            var node = document.createElement(tag);
            if (className) node.className = className;
            if (text !== undefined) node.textContent = text;
            return node;
        }

        function offerButtons(m, container) {
            var pending = m.offer_status === 'pending';
            var mine = Number(m.sender_id) === myId;

            if (pending && mine) {
                var edit = el('button', 'rounded-md border border-neutral-500 bg-white/40 px-3 py-1 text-small-label font-semibold text-neutral-900 transition hover:bg-white/70', 'Edit');
                edit.type = 'button';
                edit.addEventListener('click', function () { editOffer(m); });
                var cancel = el('button', 'rounded-md bg-error px-3 py-1 text-small-label font-semibold transition hover:opacity-90', 'Batalkan');
                cancel.type = 'button';
                cancel.addEventListener('click', function () { cancelOffer(m, container); });
                container.appendChild(edit);
                container.appendChild(cancel);
            } else if (pending && myId === sellerId) {
                ['accept', 'reject'].forEach(function (decision) {
                    var btn = el('button', 'rounded-md px-3 py-1 text-small-label font-semibold text-white transition ' + (decision === 'accept' ? 'bg-neutral-900 hover:bg-neutral-700' : 'bg-error hover:opacity-90'), decision === 'accept' ? 'Terima' : 'Tolak');
                    btn.type = 'button';
                    btn.addEventListener('click', function () { decideOffer(m, decision); });
                    container.appendChild(btn);
                });
            }
        }

        function renderMessage(m) {
            var mine = Number(m.sender_id) === myId;
            var wrapper = el('div', 'flex items-start gap-2 ' + (mine ? 'justify-end' : 'justify-start'));
            wrapper.setAttribute('data-message-id', m.id);

            if (!mine) {
                var avatar = el('img');
                avatar.src = otherAvatar;
                avatar.alt = '';
                avatar.className = 'mt-1 size-7 shrink-0 rounded-full object-cover';
                wrapper.appendChild(avatar);
            }

            var bubble = el('div', 'max-w-[75%] rounded-2xl px-4 py-2 text-body ' + (mine ? 'bg-lime-400 text-neutral-900' : 'bg-neutral-200 text-neutral-900'));

            if (m.type === 'offer') {
                bubble.appendChild(el('p', 'text-small-label text-neutral-700', 'Menawar'));
                var amount = el('p', 'font-bold', 'Rp' + Number(m.offer_amount).toLocaleString('id-ID'));
                amount.setAttribute('data-offer-amount', m.offer_amount);
                bubble.appendChild(amount);
                var status = el('p', 'text-caption text-neutral-700', offerStatusText(m.offer_status));
                status.setAttribute('data-offer-status', m.offer_status);
                bubble.appendChild(status);
                var actions = el('div', 'mt-2 flex gap-2');
                actions.setAttribute('data-offer-actions', m.id);
                offerButtons(m, actions);
                bubble.appendChild(actions);
            } else {
                if (m.image) {
                    var img = el('img');
                    img.src = m.image;
                    img.alt = 'Gambar pesan';
                    img.className = 'mb-1 max-h-48 rounded-lg';
                    bubble.appendChild(img);
                }
                if (m.body) {
                    var p = el('p', 'break-words');
                    p.innerHTML = escapeHtml(m.body);
                    bubble.appendChild(p);
                }
            }

            bubble.appendChild(el('span', 'mt-1 block text-right text-caption text-neutral-600/80', m.created_at || ''));
            wrapper.appendChild(bubble);
            return wrapper;
        }

        function appendMessage(m) {
            var empty = box.querySelector('[data-empty-state]');
            if (empty) empty.remove();
            box.appendChild(renderMessage(m));
            lastId = Math.max(lastId, Number(m.id));
            scrollToBottom();
        }

        function postForm(data) {
            return fetch(sendUrl, { method: 'POST', body: data, headers: { 'Accept': 'application/json' } });
        }

        form.addEventListener('submit', function (event) {
            event.preventDefault();
            var body = input.value.trim();
            var hasImage = imageInput.files && imageInput.files.length > 0;
            if (!body && !hasImage) return;

            var data = new FormData();
            data.append('_token', csrfToken);
            data.append('body', body);
            if (hasImage) data.append('image', imageInput.files[0]);

            postForm(data)
                .then(function (response) {
                    if (!response.ok) throw new Error('send');
                    return response.json();
                })
                .then(function (payload) {
                    if (payload.message) appendMessage(payload.message);
                    input.value = '';
                    imageInput.value = '';
                })
                .catch(function () { alert('Gagal mengirim pesan. Coba lagi.'); });
        });

        imageBtn.addEventListener('click', function () { imageInput.click(); });

        var offerBtn = form.querySelector('[data-chat-offer-btn]');
        if (offerBtn) {
            offerBtn.addEventListener('click', function () {
                var raw = prompt('Masukkan harga tawaran (angka, tanpa titik):');
                if (raw === null) return;
                var amount = parseInt(raw.replace(/[^0-9]/g, ''), 10);
                if (!amount || amount < 1) { alert('Nominal tawaran tidak valid.'); return; }

                var data = new FormData();
                data.append('_token', csrfToken);
                data.append('type', 'offer');
                data.append('offer_amount', amount);
                fetch(offerBtn.dataset.offerUrl, { method: 'POST', body: data, headers: { 'Accept': 'application/json' } })
                    .then(function (response) {
                        if (!response.ok) throw new Error('offer');
                        return response.json();
                    })
                    .then(function (payload) {
                        if (payload.message) appendMessage(payload.message);
                    })
                    .catch(function () { alert('Gagal mengirim tawaran. Coba lagi.'); });
            });
        }

        function decideOffer(m, decision) {
            fetch(offerDecisionUrl.replace(':message', m.id).replace(':decision', decision), {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
            })
                .then(function (response) {
                    if (!response.ok) throw new Error('offer');
                    return response.json();
                })
                .then(function (payload) {
                    if (payload.message) updateOfferNode(payload.message);
                })
                .catch(function () { alert('Gagal memproses tawaran.'); });
        }

        function editOffer(m) {
            var raw = prompt('Ubah nominal tawaran (angka, tanpa titik):');
            if (raw === null) return;
            var amount = parseInt(raw.replace(/[^0-9]/g, ''), 10);
            if (!amount || amount < 1) { alert('Nominal tawaran tidak valid.'); return; }

            var url = box.querySelector('[data-offer-edit="' + m.id + '"]').dataset.offerUpdateUrl;
            fetch(url, {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                body: JSON.stringify({ offer_amount: amount }),
            })
                .then(function (response) {
                    if (!response.ok) throw new Error('offer');
                    return response.json();
                })
                .then(function (payload) {
                    if (payload.message) updateOfferNode(payload.message);
                })
                .catch(function () { alert('Gagal mengubah tawaran.'); });
        }

        function cancelOffer(m, container) {
            fetch(offerCancelUrl.replace(':message', m.id), {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
            })
                .then(function (response) {
                    if (!response.ok) throw new Error('offer');
                    return response.json();
                })
                .then(function (payload) {
                    if (payload.message) updateOfferNode(payload.message);
                })
                .catch(function () { alert('Gagal membatalkan tawaran.'); });
        }

        function updateOfferNode(m) {
            var node = box.querySelector('[data-message-id="' + m.id + '"]');
            if (!node) return;
            var amount = node.querySelector('[data-offer-amount]');
            if (amount) {
                amount.textContent = 'Rp' + Number(m.offer_amount).toLocaleString('id-ID');
                amount.setAttribute('data-offer-amount', m.offer_amount);
            }
            var status = node.querySelector('[data-offer-status]');
            if (status) {
                status.textContent = offerStatusText(m.offer_status);
                status.setAttribute('data-offer-status', m.offer_status);
            }
            var actions = node.querySelector('[data-offer-actions]');
            if (actions) actions.remove();
        }

        function poll() {
            fetch(pollUrl + '?after=' + lastId, { headers: { 'Accept': 'application/json' } })
                .then(function (response) {
                    if (!response.ok) throw new Error('poll');
                    return response.json();
                })
                .then(function (payload) {
                    (payload.messages || []).forEach(appendMessage);
                })
                .catch(function () { /* coba lagi di tick berikutnya */ });
        }
        setInterval(poll, 3000);
    })();
</script>
@endpush
@endsection
