<?php

namespace App\Services;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Notifications\NewMessage;
use App\Notifications\OfferDecided;
use App\Notifications\OfferReceived;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Operasi domain chat & tawaran harga.
 *
 * Dipakai oleh controller API. Aturan otorisasi & state tawaran identik
 * dengan yang ada di ChatController web (pembeli boleh tawaran, penjual
 * memutuskan, tawaran sekali pakai, dst).
 */
class ConversationService
{
    private const PAGE_SIZE = 100;

    /**
     * @return Collection<int, Conversation>
     */
    public function listFor(string $userId, string $filter = 'semua'): Collection
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
            ->with(['product.coverImage', 'buyer', 'seller', 'lastMessage.images', 'latestOffer'])
            ->withCount(['messages as unread_count' => fn ($query) => $query
                ->whereNull('read_at')
                ->where('type', '!=', Message::TYPE_SYSTEM)
                ->where('sender_id', '!=', $userId)])
            ->orderByDesc('last_message_at')
            ->orderByDesc('id')
            ->get();
    }

    public function start(string $userId, string $productId, ?int $offerAmount = null): Conversation
    {
        $product = Product::query()->findOrFail($productId);
        abort_if($product->user_id === $userId, 403, 'Tidak bisa memulai chat pada produk milik sendiri.');
        abort_if($product->status !== Product::STATUS_DIJUAL, 404);
        abort_if($product->user?->is_banned, 404);

        $attributes = [
            'buyer_id' => $userId,
            'seller_id' => $product->user_id,
            'product_id' => $product->id,
        ];

        try {
            $conversation = Conversation::query()->firstOrCreate(
                $attributes,
                ['last_message_at' => now()],
            );
        } catch (UniqueConstraintViolationException) {
            $conversation = Conversation::query()->where($attributes)->firstOrFail();
        }

        if ($offerAmount !== null) {
            $offer = $this->createOfferMessage($conversation, $userId, $offerAmount);
            $conversation->update(['last_message_at' => $offer->created_at]);
            $conversation->seller()->first()?->notify(new OfferReceived(
                (string) $conversation->buyer?->name,
                $offerAmount,
                $conversation->id,
            ));
        }

        return $conversation;
    }

    public function messages(Conversation $conversation, string $userId): Collection
    {
        $this->markRead($conversation, $userId);

        return $conversation->messages()
            ->with(['sender', 'images'])
            ->orderByDesc('id')
            ->limit(self::PAGE_SIZE)
            ->get()
            ->reverse()
            ->values();
    }

    /**
     * @return array{
     *     messages: Collection<int, Message>,
     *     offers: Collection<int, Message>,
     *     receipts: array{read_upto: int, delivered_upto: int},
     *     presence: array{online: bool, last_seen_at: ?string, label: string},
     *     unread_total: int,
     * }
     */
    public function poll(Conversation $conversation, string $userId, int $after, bool $visible): array
    {
        $conversation->loadMissing(['buyer', 'seller']);

        $newMessages = $conversation->messages()
            ->where('id', '>', $after)
            ->with(['sender', 'images'])
            ->orderBy('id')
            ->get();

        $incomingQuery = $conversation->messages()->where('sender_id', '!=', $userId);

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
            ->with('images')
            ->get();

        return [
            'messages' => $newMessages,
            'offers' => $offers,
            'receipts' => $this->receipts($conversation, $userId),
            'presence' => $this->presence($conversation, $userId),
            'unread_total' => $this->unreadTotal($userId),
        ];
    }

    public function markRead(Conversation $conversation, string $userId): void
    {
        $now = now();

        $query = $conversation->messages()
            ->where('sender_id', '!=', $userId);

        (clone $query)->whereNull('delivered_at')->update(['delivered_at' => $now]);
        (clone $query)->whereNull('read_at')->update(['read_at' => $now]);
    }

    /**
     * @return array{read_upto: int, delivered_upto: int}
     */
    public function receipts(Conversation $conversation, string $myId): array
    {
        $mine = $conversation->messages()->where('sender_id', $myId);

        return [
            'read_upto' => (int) (clone $mine)->whereNotNull('read_at')->max('id'),
            'delivered_upto' => (int) (clone $mine)->whereNotNull('delivered_at')->max('id'),
        ];
    }

    /**
     * @return array{online: bool, last_seen_at: ?string, label: string}
     */
    public function presence(Conversation $conversation, string $myId): array
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

    public function unreadTotal(string $userId): int
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
     * @param  array<string, mixed>  $validated
     * @param  array<int, UploadedFile>  $files
     */
    public function send(Conversation $conversation, string $userId, array $validated, array $files): Message
    {
        $isOffer = ($validated['type'] ?? null) === Message::TYPE_OFFER;

        if ($isOffer) {
            abort_unless($conversation->buyer_id === $userId, 403, 'Hanya pembeli yang bisa mengirim tawaran.');

            $product = $conversation->product;
            abort_if($product === null, 404, 'Produk tidak ditemukan.');
            abort_if($product->status !== Product::STATUS_DIJUAL, 409, 'Produk ini sudah tidak dapat ditawar.');
            abort_if(! $product->is_negotiable, 409, 'Penjual tidak menerima tawaran.');

            $active = Order::where('conversation_id', $conversation->id)
                ->whereIn('status', [Order::STATUS_DISEPAKATI, Order::STATUS_SELESAI])
                ->exists();
            abort_if($active, 409, 'Transaksi sudah berjalan, tawaran baru tidak bisa dikirim.');

            $amount = (int) ($validated['offer_amount'] ?? 0);
            abort_if($product->harga !== null && $amount > $product->harga, 422, 'Tawaran tidak boleh melebihi harga produk.');
        }

        $clientId = $validated['client_id'] ?? null;

        if ($clientId !== null) {
            $existing = $conversation->messages()->where('client_id', $clientId)->first();
            if ($existing !== null) {
                return $existing->loadMissing('sender', 'images');
            }
        }

        $storedPaths = [];

        try {
            foreach ($files as $file) {
                $storedPaths[] = $file->store("chat/{$conversation->id}", 'public');
            }

            $message = DB::transaction(function () use ($conversation, $userId, $validated, $clientId, $isOffer, $storedPaths) {
                $message = $conversation->messages()->create([
                    'sender_id' => $userId,
                    'client_id' => $clientId,
                    'body' => $validated['body'] ?? null,
                    'image' => count($storedPaths) === 1 ? $storedPaths[0] : null,
                    'type' => $isOffer ? Message::TYPE_OFFER : Message::TYPE_TEXT,
                    'offer_amount' => $isOffer ? $validated['offer_amount'] : null,
                    'offer_status' => $isOffer ? Message::OFFER_PENDING : null,
                ]);

                foreach ($storedPaths as $index => $path) {
                    $message->images()->create(['path' => $path, 'urutan' => $index]);
                }

                return $message;
            });
        } catch (UniqueConstraintViolationException) {
            Storage::disk('public')->delete($storedPaths);
            $message = $conversation->messages()->where('client_id', $clientId)->sole();

            return $message->loadMissing('sender', 'images');
        } catch (\Throwable $e) {
            Storage::disk('public')->delete($storedPaths);
            throw $e;
        }

        $conversation->update(['last_message_at' => $message->created_at]);
        $senderName = (string) ($conversation->buyer_id === $userId ? $conversation->buyer?->name : $conversation->seller?->name);
        $this->notifyCounterpart($conversation, $userId, $isOffer, $message, $senderName);

        return $message->loadMissing('sender', 'images');
    }

    public function updateOffer(Conversation $conversation, Message $message, string $userId, int $amount): Message
    {
        $this->authorizeOfferSender($conversation, $message, $userId);
        abort_if($conversation->product !== null && $conversation->product->harga !== null && $amount > $conversation->product->harga, 422, 'Tawaran tidak boleh melebihi harga produk.');
        $message->update(['offer_amount' => $amount]);

        return $message;
    }

    public function cancelOffer(Conversation $conversation, Message $message, string $userId): ?Message
    {
        $this->authorizeOfferSender($conversation, $message, $userId);
        $message->update(['offer_status' => Message::OFFER_CANCELLED]);

        return $this->systemMessage($conversation, $userId, 'Penawaran dibatalkan');
    }

    public function acceptOffer(Conversation $conversation, Message $message, string $userId): ?Message
    {
        $this->authorizeOfferDecision($conversation, $message, $userId);

        $active = Order::where('conversation_id', $conversation->id)
            ->whereIn('status', [Order::STATUS_DISEPAKATI, Order::STATUS_SELESAI])
            ->exists();
        abort_if($active, 409, 'Percakapan ini sudah memiliki transaksi berjalan.');

        DB::transaction(function () use ($conversation, $message): void {
            $locked = Message::query()->lockForUpdate()->findOrFail($message->getKey());
            abort_if($locked->offer_status !== Message::OFFER_PENDING, 409, 'Tawaran ini sudah diproses.');
            $locked->update(['offer_status' => Message::OFFER_ACCEPTED]);

            Order::create([
                'conversation_id' => $conversation->id,
                'buyer_id' => $conversation->buyer_id,
                'seller_id' => $conversation->seller_id,
                'product_id' => $conversation->product_id,
                'harga_final' => $locked->offer_amount,
                'status' => Order::STATUS_DISEPAKATI,
            ]);
        });

        $conversation->buyer()->first()?->notify(new OfferDecided(true, (int) $message->offer_amount, $conversation->id));
        $message->refresh();

        return $this->systemMessage($conversation, $userId, 'Penawaran disetujui');
    }

    public function rejectOffer(Conversation $conversation, Message $message, string $userId): ?Message
    {
        $this->authorizeOfferDecision($conversation, $message, $userId);
        $message->update(['offer_status' => Message::OFFER_REJECTED]);
        $conversation->buyer()->first()?->notify(new OfferDecided(false, (int) $message->offer_amount, $conversation->id));

        return $this->systemMessage($conversation, $userId, 'Penawaran ditolak');
    }

    public function markSold(Conversation $conversation, string $userId, string $completionType = Order::COMPLETION_STOCK_OUT): void
    {
        $order = $conversation->order()->first();
        abort_if($order === null, 404, 'Belum ada kesepakatan untuk produk ini.');

        (new OrderService)->complete($order, $userId, $completionType);
    }

    public function deleteMessage(Message $message, string $userId): void
    {
        abort_unless($message->sender_id === $userId, 403);
        $paths = $message->imagePaths();
        $message->delete();
        if ($paths !== []) {
            Storage::disk('public')->delete($paths);
        }
    }

    public function authorizeParticipant(Conversation $conversation, string $userId): void
    {
        abort_unless($conversation->involves($userId), 404);
    }

    public function latestOffer(Conversation $conversation): ?Message
    {
        return $conversation->latestOffer()->first();
    }

    public function orderFor(Conversation $conversation): ?Order
    {
        return $conversation->order()->first();
    }

    private function createOfferMessage(Conversation $conversation, string $userId, int $amount): Message
    {
        return $conversation->messages()->create([
            'sender_id' => $userId,
            'type' => Message::TYPE_OFFER,
            'offer_amount' => $amount,
            'offer_status' => Message::OFFER_PENDING,
        ]);
    }

    private function systemMessage(Conversation $conversation, string $userId, string $body): Message
    {
        $message = $conversation->messages()->create([
            'sender_id' => $userId,
            'body' => $body,
            'type' => Message::TYPE_SYSTEM,
        ]);
        $conversation->update(['last_message_at' => $message->created_at]);

        return $message;
    }

    private function notifyCounterpart(Conversation $conversation, string $userId, bool $isOffer, Message $message, string $senderName): void
    {
        $recipientId = $conversation->buyer_id === $userId ? $conversation->seller_id : $conversation->buyer_id;
        $recipient = User::find($recipientId);
        if ($recipient === null) {
            return;
        }

        if ($isOffer) {
            $recipient->notify(new OfferReceived(
                $senderName,
                (int) $message->offer_amount,
                $conversation->id,
            ));

            return;
        }

        $recipient->notify(new NewMessage($senderName, $conversation->id));
    }

    private function authorizeOfferSender(Conversation $conversation, Message $message, string $userId): void
    {
        $this->authorizeParticipant($conversation, $userId);
        abort_unless($message->conversation_id === $conversation->id, 404);
        abort_unless($message->sender_id === $userId, 403, 'Hanya pengirim tawaran yang bisa mengubahnya.');
        abort_unless($message->type === Message::TYPE_OFFER, 404);
        abort_if($message->offer_status !== Message::OFFER_PENDING, 409, 'Tawaran ini sudah diproses.');
    }

    private function authorizeOfferDecision(Conversation $conversation, Message $message, string $userId): void
    {
        $this->authorizeParticipant($conversation, $userId);
        abort_unless($conversation->seller_id === $userId, 403, 'Hanya penjual yang bisa memutuskan tawaran.');
        abort_unless($message->conversation_id === $conversation->id, 404);
        abort_unless($message->type === Message::TYPE_OFFER, 404);
        abort_if($message->offer_status !== Message::OFFER_PENDING, 409, 'Tawaran ini sudah diproses.');
    }
}
