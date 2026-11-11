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
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ChatController extends Controller
{
    /**
     * Daftar percakapan user (sebagai pembeli maupun penjual).
     */
    public function index(): View
    {
        return view('chat.index', [
            'conversations' => $this->conversationsFor((int) Auth::id()),
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
            ->orderBy('id')
            ->get();
        $pendingOffer = $conversation->messages()
            ->where('type', Message::TYPE_OFFER)
            ->where('offer_status', Message::OFFER_PENDING)
            ->orderByDesc('id')
            ->first();
        $other = $conversation->buyer_id === (int) Auth::id()
            ? $conversation->seller
            : $conversation->buyer;

        return view('chat.show', [
            'conversation' => $conversation,
            'messages' => $messages,
            'other' => $other,
            'pendingOffer' => $pendingOffer,
            'sidebarConversations' => $this->conversationsFor((int) Auth::id()),
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

        $conversation = Conversation::query()->firstOrCreate(
            [
                'buyer_id' => (int) Auth::id(),
                'seller_id' => $product->user_id,
                'product_id' => $product->id,
            ],
            ['last_message_at' => now()],
        );

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
        $imagePath = $request->hasFile('image')
            ? $request->file('image')->store("chat/{$conversation->id}", 'public')
            : null;

        $isOffer = ($validated['type'] ?? null) === Message::TYPE_OFFER;

        if ($isOffer) {
            // Tawaran hanya dari pembeli; keputusan ada di tangan penjual.
            abort_unless(
                $conversation->buyer_id === (int) Auth::id(),
                403,
                'Hanya pembeli yang bisa mengirim tawaran.',
            );
        }

        $message = $conversation->messages()->create([
            'sender_id' => (int) Auth::id(),
            'body' => $validated['body'] ?? null,
            'image' => $imagePath,
            'type' => $isOffer ? Message::TYPE_OFFER : Message::TYPE_TEXT,
            'offer_amount' => $isOffer ? $validated['offer_amount'] : null,
            'offer_status' => $isOffer ? Message::OFFER_PENDING : null,
        ]);

        $conversation->update(['last_message_at' => $message->created_at]);

        $this->notifyCounterpart($conversation, $isOffer, $message);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => $this->payload($message->load('sender')),
            ]);
        }

        return redirect()->route('chat.show', $conversation);
    }

    /**
     * Polling: pesan dengan id lebih besar dari ?after= (JSON).
     */
    public function poll(Request $request, Conversation $conversation): JsonResponse
    {
        $this->authorizeParticipant($conversation);

        $messages = $conversation->messages()
            ->where('id', '>', (int) $request->query('after', 0))
            ->with('sender')
            ->orderBy('id')
            ->get()
            ->map($this->payload(...));

        return response()->json(['messages' => $messages]);
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

        return $this->offerResponse($conversation, $message);
    }

    /**
     * Penjual menerima tawaran → order dibuat.
     */
    public function acceptOffer(Conversation $conversation, Message $message): JsonResponse|RedirectResponse
    {
        $this->authorizeOfferDecision($conversation, $message);

        DB::transaction(function () use ($conversation, $message): void {
            $message->update(['offer_status' => Message::OFFER_ACCEPTED]);

            Order::create([
                'buyer_id' => $conversation->buyer_id,
                'seller_id' => $conversation->seller_id,
                'product_id' => $conversation->product_id,
                'harga_final' => $message->offer_amount,
                'status' => Order::STATUS_DISEPAKATI,
            ]);
        });

        $conversation->buyer()->first()?->notify(
            new OfferDecided(true, (int) $message->offer_amount, $conversation->id),
        );

        return $this->offerResponse($conversation, $message);
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

        return $this->offerResponse($conversation, $message);
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
     * @return Collection<int, Conversation>
     */
    private function conversationsFor(int $userId)
    {
        return Conversation::query()
            ->where(fn ($query) => $query
                ->where('buyer_id', $userId)
                ->orWhere('seller_id', $userId))
            ->with(['product.coverImage', 'buyer', 'seller', 'lastMessage'])
            ->orderByDesc('last_message_at')
            ->orderByDesc('id')
            ->get();
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
    private function offerResponse(Conversation $conversation, Message $message): JsonResponse|RedirectResponse
    {
        $message->refresh();

        if (request()->expectsJson()) {
            return response()->json(['message' => $this->payload($message)]);
        }

        return redirect()->route('chat.show', $conversation);
    }

    /**
     * Bentuk JSON satu pesan untuk polling dan respons kirim.
     *
     * @return array<string, mixed>
     */
    private function payload(Message $message): array
    {
        return [
            'id' => $message->id,
            'sender_id' => $message->sender_id,
            'sender_name' => $message->sender->name,
            'body' => $message->body,
            'image' => $message->image !== null ? Storage::url($message->image) : null,
            'type' => $message->type,
            'offer_amount' => $message->offer_amount,
            'offer_status' => $message->offer_status,
            'created_at' => $message->created_at?->format('H:i'),
        ];
    }
}
