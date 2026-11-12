<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMessageRequest;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Notifications\NewMessage;
use App\Notifications\OfferDecided;
use App\Notifications\OfferReceived;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ChatController extends Controller
{
    /**
     * Jumlah pesan terakhir yang dimuat saat ruang chat dibuka.
     */
    private const PAGE_SIZE = 100;

    /**
     * Daftar percakapan user (sebagai pembeli maupun penjual).
     */
    public function index(Request $request): View
    {
        $userId = (int) Auth::id();

        return view('chat.index', [
            'conversations' => $this->conversationsFor($userId, $this->filterFrom($request)),
            'filter' => $this->filterFrom($request),
        ]);
    }

    /**
     * Ruang chat satu percakapan.
     */
    public function show(Conversation $conversation): View
    {
        $this->authorizeParticipant($conversation);

        $conversation->load(['product.coverImage', 'buyer', 'seller']);

        $messages = $conversation->messages()
            ->with('sender')
            ->orderByDesc('id')
            ->limit(self::PAGE_SIZE)
            ->get()
            ->reverse()
            ->values();

        $other = $conversation->counterpartFor((int) Auth::id());
        $latestOffer = $conversation->latestOffer()->first();

        // Membuka ruang chat berarti pesan lawan sudah terbaca.
        $this->markMessagesRead($conversation);

        return view('chat.show', [
            'conversation' => $conversation,
            'messages' => $messages,
            'other' => $other,
            'latestOffer' => $latestOffer,
            'sidebarConversations' => $this->conversationsFor((int) Auth::id()),
            'filter' => 'semua',
        ]);
    }

    /**
     * Mulai percakapan baru dari sebuah produk.
     */
    public function start(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'offer_amount' => ['nullable', 'integer', 'min:1'],
        ]);

        $product = Product::query()->findOrFail($data['product_id']);
        abort_if(
            $product->user_id === (int) Auth::id(),
            403,
            'Tidak bisa memulai chat pada produk milik sendiri.',
        );

        $attributes = [
            'buyer_id' => (int) Auth::id(),
            'seller_id' => $product->user_id,
            'product_id' => $product->id,
        ];

        try {
            $conversation = Conversation::query()->firstOrCreate(
                $attributes,
                ['last_message_at' => now()],
            );
        } catch (UniqueConstraintViolationException) {
            // Dua klik hampir bersamaan: pakai percakapan yang sudah dibuat request lain.
            $conversation = Conversation::query()->where($attributes)->firstOrFail();
        }

        // Tawaran langsung dari halaman detail produk: buat pesan tawaran pertama.
        if (($offerAmount = $data['offer_amount'] ?? null) !== null) {
            $offer = $conversation->messages()->create([
                'sender_id' => (int) Auth::id(),
                'type' => Message::TYPE_OFFER,
                'offer_amount' => $offerAmount,
                'offer_status' => Message::OFFER_PENDING,
            ]);

            $conversation->update(['last_message_at' => $offer->created_at]);

            $conversation->seller()->first()?->notify(new OfferReceived(
                (string) Auth::user()?->name,
                $offerAmount,
                $conversation->id,
            ));
        }

        return redirect()->route('chat.show', $conversation);
    }

    /**
     * Kirim pesan (teks dan/atau gambar).
     */
    public function sendMessage(StoreMessageRequest $request, Conversation $conversation): JsonResponse|RedirectResponse
    {
        $this->authorizeParticipant($conversation);

        $validated = $request->validated();
        $isOffer = ($validated['type'] ?? null) === Message::TYPE_OFFER;

        if ($isOffer) {
            // Tawaran hanya dari pembeli; keputusan ada di tangan penjual.
            abort_unless(
                $conversation->buyer_id === (int) Auth::id(),
                403,
                'Hanya pembeli yang bisa mengirim tawaran.',
            );
        }

        $clientId = $validated['client_id'] ?? null;

        // Request ganda dengan client_id yang sama mengembalikan pesan yang sudah ada.
        if ($clientId !== null) {
            $existing = $conversation->messages()->where('client_id', $clientId)->first();

            if ($existing !== null) {
                return $this->sentResponse($conversation, $existing);
            }
        }

        $imagePath = $request->hasFile('image')
            ? $request->file('image')->store("chat/{$conversation->id}", 'public')
            : null;

        try {
            $message = $conversation->messages()->create([
                'sender_id' => (int) Auth::id(),
                'client_id' => $clientId,
                'body' => $validated['body'] ?? null,
                'image' => $imagePath,
                'type' => $isOffer ? Message::TYPE_OFFER : Message::TYPE_TEXT,
                'offer_amount' => $isOffer ? $validated['offer_amount'] : null,
                'offer_status' => $isOffer ? Message::OFFER_PENDING : null,
            ]);
        } catch (UniqueConstraintViolationException) {
            // Balapan dua request identik: pakai baris yang menang.
            if ($imagePath !== null) {
                Storage::disk('public')->delete($imagePath);
            }

            $message = $conversation->messages()->where('client_id', $clientId)->sole();

            return $this->sentResponse($conversation, $message);
        }

        $conversation->update(['last_message_at' => $message->created_at]);

        $this->notifyCounterpart($conversation, $isOffer, $message);

        return $this->sentResponse($conversation, $message);
    }

    /**
     * Polling: pesan baru, perubahan tawaran, resi baca, dan kehadiran lawan.
     */
    public function poll(Request $request, Conversation $conversation): JsonResponse
    {
        $this->authorizeParticipant($conversation);

        $myId = (int) Auth::id();
        $conversation->loadMissing(['buyer', 'seller']);

        $after = max(0, (int) $request->query('after', 0));
        $visible = $request->boolean('visible', true);

        $newMessages = $conversation->messages()
            ->where('id', '>', $after)
            ->with('sender')
            ->orderBy('id')
            ->get();

        // Penanda "diterima" untuk semua pesan lawan yang sudah sampai ke klien ini;
        // "dibaca" hanya ketika tab benar-benar terlihat.
        $incomingQuery = $conversation->messages()
            ->where('sender_id', '!=', $myId);

        if ($visible) {
            $incomingQuery->where(fn ($query) => $query
                ->whereNull('delivered_at')
                ->orWhereNull('read_at'));
        } else {
            $incomingQuery->whereNull('delivered_at');
        }

        $incoming = $incomingQuery->get();

        if ($incoming->isNotEmpty()) {
            $now = now();

            foreach ($incoming as $message) {
                $message->delivered_at ??= $now;
                if ($visible) {
                    $message->read_at ??= $now;
                }
                $message->saveQuietly();
            }
        }

        $offers = $conversation->messages()
            ->where('type', Message::TYPE_OFFER)
            ->get()
            ->map(fn (Message $message) => [
                'id' => $message->id,
                'version' => $message->updated_at?->getTimestamp() ?? 0,
                'html' => $this->renderMessage($conversation, $message),
            ])
            ->values();

        return response()->json([
            'messages' => $newMessages
                ->map(fn (Message $message) => $this->messageMeta($message) + [
                    'html' => $this->renderMessage($conversation, $message),
                ])
                ->values(),
            'offers' => $offers,
            'offer_summary' => $this->offerSummaryHtml($conversation),
            'receipts' => $this->receipts($conversation, $myId),
            'presence' => $this->presencePayload($conversation, $myId),
        ]);
    }

    /**
     * Tandai pesan lawan sudah dibaca (dipanggil saat tab kembali fokus).
     */
    public function markRead(Conversation $conversation): JsonResponse
    {
        $this->authorizeParticipant($conversation);

        $this->markMessagesRead($conversation);

        return response()->json([
            'receipts' => $this->receipts($conversation, (int) Auth::id()),
            'unread_total' => $this->unreadTotal((int) Auth::id()),
        ]);
    }

    /**
     * Total pesan belum dibaca untuk badge navbar.
     */
    public function unreadCount(): JsonResponse
    {
        return response()->json([
            'total' => $this->unreadTotal((int) Auth::id()),
        ]);
    }

    /**
     * Stream Server-Sent Events untuk satu user: keluar "unread" (badge navbar)
     * dan "activity" (isyarat ruang chat) lewat delta-poll ±1,5 dtk.
     *
     * Delta-poll dipilih agar tetap bekerja di multi-proses/PHP-FPM tanpa queue
     * worker. Client EventSource auto-reconnect; nilainya hanya angka (total/rev),
     * bukan isi pesan.
     */
    public function stream(): StreamedResponse
    {
        $userId = (int) Auth::id();

        return response()->stream(function () use ($userId): void {
            ignore_user_abort(true);
            set_time_limit(0);

            $lastUnread = null;
            $lastRev = null;
            $lastBeat = now();

            $emit = static function (string $event, array $data): void {
                echo 'event: '.$event."\n";
                echo 'data: '.json_encode($data)."\n\n";
            };

            $flush = static function (): void {
                if (ob_get_level() > 0) {
                    ob_flush();
                }
                flush();
            };

            while (!connection_aborted()) {
                $payload = $this->streamPayload($userId);

                if ($payload['unread'] !== $lastUnread) {
                    $lastUnread = $payload['unread'];
                    $emit('unread', ['total' => $payload['unread']]);
                    $flush();
                }

                if ($payload['rev'] !== $lastRev) {
                    $lastRev = $payload['rev'];
                    $emit('activity', ['rev' => $payload['rev']]);
                    $flush();
                }

                if (now()->diffInSeconds($lastBeat) >= 15) {
                    $lastBeat = now();
                    echo ': ping'."\n\n";
                    $flush();
                }

                usleep(1_500_000);
            }
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache, no-store, max-age=0, must-revalidate',
            'Pragma' => 'no-cache',
            'X-Accel-Buffering' => 'no',
            'X-Content-Type-Options' => 'nosniff',
            'Connection' => 'keep-alive',
        ]);
    }

    /**
     * Ringkasan yang dibandingkan delta-poll SSE.
     *
     * @return array{unread: int, rev: int}
     */
    public function streamPayload(int $userId): array
    {
        return [
            'unread' => $this->unreadTotal($userId),
            'rev' => (int) Message::query()
                ->whereHas('conversation', fn ($query) => $query
                    ->where(fn ($conversation) => $conversation
                        ->where('buyer_id', $userId)
                        ->orWhere('seller_id', $userId)))
                ->max('id'),
        ];
    }

    /**
     * Pengirim tawaran mengubah nominal (masih pending).
     */
    public function updateOffer(Request $request, Conversation $conversation, Message $message): JsonResponse|RedirectResponse
    {
        $this->authorizeOfferSender($conversation, $message);

        $data = $request->validate([
            'offer_amount' => ['required', 'integer', 'min:1'],
        ]);

        $message->update(['offer_amount' => $data['offer_amount']]);

        return $this->offerResponse($conversation, $message);
    }

    /**
     * Pengirim tawaran membatalkan (masih pending).
     */
    public function cancelOffer(Conversation $conversation, Message $message): JsonResponse|RedirectResponse
    {
        $this->authorizeOfferSender($conversation, $message);

        $message->update(['offer_status' => Message::OFFER_CANCELLED]);

        $system = $this->systemMessage($conversation, 'Penawaran dibatalkan');

        return $this->offerResponse($conversation, $message, $system);
    }

    /**
     * Penjual menerima tawaran → order dibuat.
     */
    public function acceptOffer(Conversation $conversation, Message $message): JsonResponse|RedirectResponse
    {
        $this->authorizeOfferDecision($conversation, $message);

        DB::transaction(function () use ($conversation, $message): void {
            // Kunci baris agar dua tap "Terima" tidak membuat dua order.
            $locked = Message::query()->lockForUpdate()->findOrFail($message->getKey());

            abort_if(
                $locked->offer_status !== Message::OFFER_PENDING,
                409,
                'Tawaran ini sudah diproses.',
            );

            $locked->update(['offer_status' => Message::OFFER_ACCEPTED]);

            Order::create([
                'buyer_id' => $conversation->buyer_id,
                'seller_id' => $conversation->seller_id,
                'product_id' => $conversation->product_id,
                'harga_final' => $locked->offer_amount,
                'status' => Order::STATUS_DISEPAKATI,
            ]);
        });

        $conversation->buyer()->first()?->notify(
            new OfferDecided(true, (int) $message->offer_amount, $conversation->id),
        );

        $system = $this->systemMessage($conversation, 'Penawaran disetujui');

        return $this->offerResponse($conversation, $message, $system);
    }

    /**
     * Penjual menolak tawaran.
     */
    public function rejectOffer(Conversation $conversation, Message $message): JsonResponse|RedirectResponse
    {
        $this->authorizeOfferDecision($conversation, $message);

        $message->update(['offer_status' => Message::OFFER_REJECTED]);

        $conversation->buyer()->first()?->notify(
            new OfferDecided(false, (int) $message->offer_amount, $conversation->id),
        );

        $system = $this->systemMessage($conversation, 'Penawaran ditolak');

        return $this->offerResponse($conversation, $message, $system);
    }

    /**
     * Hapus pesan — hanya pengirimnya.
     */
    public function destroy(Message $message): RedirectResponse
    {
        abort_unless($message->sender_id === (int) Auth::id(), 403);

        $path = $message->image;
        $message->delete();

        if ($path !== null) {
            Storage::disk('public')->delete($path);
        }

        return back();
    }

    /**
     * Jawaban untuk kirim pesan (HTML siap tempel + meta tawaran).
     *
     * @return JsonResponse|RedirectResponse
     */
    private function sentResponse(Conversation $conversation, Message $message)
    {
        $conversation->loadMissing(['buyer', 'seller']);
        $message->loadMissing('sender');

        if (request()->expectsJson()) {
            return response()->json([
                'message' => $this->messageMeta($message),
                'html' => $this->renderMessage($conversation, $message),
                'offer_summary' => $this->offerSummaryHtml($conversation),
                'receipts' => $this->receipts($conversation, (int) Auth::id()),
            ]);
        }

        return redirect()->route('chat.show', $conversation);
    }

    /**
     * Kirim notifikasi ke lawan bicara (tidak pernah ke pengirim).
     */
    private function notifyCounterpart(Conversation $conversation, bool $isOffer, Message $message): void
    {
        $recipientId = $conversation->buyer_id === (int) Auth::id()
            ? $conversation->seller_id
            : $conversation->buyer_id;

        $recipient = User::find($recipientId);
        if ($recipient === null) {
            return;
        }

        if ($isOffer) {
            $recipient->notify(new OfferReceived(
                (string) Auth::user()?->name,
                (int) $message->offer_amount,
                $conversation->id,
            ));

            return;
        }

        $recipient->notify(new NewMessage(
            (string) Auth::user()?->name,
            $conversation->id,
        ));
    }

    /**
     * Semua percakapan user beserta data pendukung daftar chat.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, Conversation>
     */
    private function conversationsFor(int $userId, string $filter = 'semua')
    {
        return Conversation::query()
            ->where(fn ($query) => $query
                ->where('buyer_id', $userId)
                ->orWhere('seller_id', $userId))
            ->when($filter === 'membeli', fn ($query) => $query->where('buyer_id', $userId))
            ->when($filter === 'menjual', fn ($query) => $query->where('seller_id', $userId))
            ->when($filter === 'belum', fn ($query) => $query->whereHas(
                'messages',
                fn ($messages) => $messages
                    ->whereNull('read_at')
                    ->where('type', '!=', Message::TYPE_SYSTEM)
                    ->where('sender_id', '!=', $userId),
            ))
            ->with(['product.coverImage', 'buyer', 'seller', 'lastMessage', 'latestOffer'])
            ->withCount(['messages as unread_count' => fn ($query) => $query
                ->whereNull('read_at')
                ->where('type', '!=', Message::TYPE_SYSTEM)
                ->where('sender_id', '!=', $userId)])
            ->orderByDesc('last_message_at')
            ->orderByDesc('id')
            ->get();
    }

    private function filterFrom(Request $request): string
    {
        $filter = (string) $request->query('filter', 'semua');

        return in_array($filter, ['semua', 'membeli', 'menjual', 'belum'], true)
            ? $filter
            : 'semua';
    }

    /**
     * Non-peserta diperlakukan tidak tahu percakapan itu ada (404).
     */
    private function authorizeParticipant(Conversation $conversation): void
    {
        abort_unless($conversation->involves((int) Auth::id()), 404);
    }

    /**
     * Pengirim tawaran boleh mengubah/membatalkan selama masih pending.
     */
    private function authorizeOfferSender(Conversation $conversation, Message $message): void
    {
        $this->authorizeParticipant($conversation);
        abort_unless($message->conversation_id === $conversation->id, 404);
        abort_unless($message->sender_id === (int) Auth::id(), 403, 'Hanya pengirim tawaran yang bisa mengubahnya.');
        abort_unless($message->type === Message::TYPE_OFFER, 404);
        abort_if($message->offer_status !== Message::OFFER_PENDING, 409, 'Tawaran ini sudah diproses.');
    }

    /**
     * Keputusan tawaran hanya oleh penjual, pada pesan tawaran yang masih pending.
     */
    private function authorizeOfferDecision(Conversation $conversation, Message $message): void
    {
        $this->authorizeParticipant($conversation);
        abort_unless($conversation->seller_id === (int) Auth::id(), 403, 'Hanya penjual yang bisa memutuskan tawaran.');
        abort_unless($message->conversation_id === $conversation->id, 404);
        abort_unless($message->type === Message::TYPE_OFFER, 404);
        abort_if($message->offer_status !== Message::OFFER_PENDING, 409, 'Tawaran ini sudah diproses.');
    }

    /**
     * Respons setelah keputusan tawaran.
     */
    private function offerResponse(Conversation $conversation, Message $message, ?Message $system = null): JsonResponse|RedirectResponse
    {
        $message->refresh();
        $conversation->loadMissing(['buyer', 'seller']);

        if (request()->expectsJson()) {
            $payload = [
                'message' => $this->messageMeta($message),
                'html' => $this->renderMessage($conversation, $message),
                'offer_summary' => $this->offerSummaryHtml($conversation),
            ];

            if ($system !== null) {
                $payload['system'] = [
                    'id' => $system->id,
                    'html' => $this->renderMessage($conversation, $system),
                ];
            }

            return response()->json($payload);
        }

        return redirect()->route('chat.show', $conversation);
    }

    /**
     * Pesan sistem di tengah alur (contoh: "Penawaran disetujui").
     */
    private function systemMessage(Conversation $conversation, string $body): Message
    {
        $message = $conversation->messages()->create([
            'sender_id' => (int) Auth::id(),
            'body' => $body,
            'type' => Message::TYPE_SYSTEM,
        ]);

        $conversation->update(['last_message_at' => $message->created_at]);

        return $message;
    }

    /**
     * Tandai semua pesan lawan sebagai diterima + dibaca.
     */
    private function markMessagesRead(Conversation $conversation): void
    {
        $now = now();

        $query = $conversation->messages()
            ->where('sender_id', '!=', (int) Auth::id());

        (clone $query)->whereNull('delivered_at')->update(['delivered_at' => $now]);
        (clone $query)->whereNull('read_at')->update(['read_at' => $now]);
    }

    /**
     * Resi baca untuk pesan milik saya sendiri.
     *
     * @return array{read_upto: int, delivered_upto: int}
     */
    private function receipts(Conversation $conversation, int $myId): array
    {
        $mine = $conversation->messages()->where('sender_id', $myId);

        return [
            'read_upto' => (int) (clone $mine)->whereNotNull('read_at')->max('id'),
            'delivered_upto' => (int) (clone $mine)->whereNotNull('delivered_at')->max('id'),
        ];
    }

    /**
     * Data kehadiran lawan bicara.
     *
     * @return array{online: bool, last_seen_at: string|null, label: string}
     */
    private function presencePayload(Conversation $conversation, int $myId): array
    {
        $other = $conversation->counterpartFor($myId);

        if ($other === null) {
            return ['online' => false, 'last_seen_at' => null, 'label' => 'Tidak tersedia'];
        }

        return [
            'online' => $other->isOnline(),
            'last_seen_at' => $other->last_seen_at?->toIso8601String(),
            'label' => $other->isOnline() ? 'Online' : 'Terakhir dilihat',
        ];
    }

    /**
     * Total pesan belum dibaca dari seluruh percakapan.
     */
    private function unreadTotal(int $userId): int
    {
        return Message::query()
            ->whereNull('read_at')
            ->where('type', '!=', Message::TYPE_SYSTEM)
            ->where('sender_id', '!=', $userId)
            ->whereHas('conversation', fn ($query) => $query
                ->where('buyer_id', $userId)
                ->orWhere('seller_id', $userId))
            ->count();
    }

    /**
     * HTML strip produk (badge status + nominal tawaran).
     */
    private function offerSummaryHtml(Conversation $conversation): string
    {
        return view('chat.partials.offer-badge', [
            'offer' => $conversation->latestOffer()->first(),
        ])->render();
    }

    /**
     * Render satu pesan memakai partial Blade yang sama dengan SSR,
     * supaya markup JS dan server tidak pernah berbeda.
     */
    private function renderMessage(Conversation $conversation, Message $message): string
    {
        return view('chat.partials.message', [
            'message' => $message,
            'conversation' => $conversation,
            'myId' => (int) Auth::id(),
        ])->render();
    }

    /**
     * Meta ringkas satu pesan.
     *
     * @return array<string, mixed>
     */
    private function messageMeta(Message $message): array
    {
        return [
            'id' => $message->id,
            'sender_id' => $message->sender_id,
            'body' => $message->body,
            'type' => $message->type,
            'offer_amount' => $message->offer_amount,
            'offer_status' => $message->offer_status,
            'created_at' => $message->created_at?->toIso8601String(),
        ];
    }
}
