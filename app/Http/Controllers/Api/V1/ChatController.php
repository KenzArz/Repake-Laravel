<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreMessageRequest;
use App\Http\Resources\ConversationResource;
use App\Http\Resources\MessageResource;
use App\Http\Resources\OrderResource;
use App\Http\Resources\ReviewResource;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\Order;
use App\Models\Product;
use App\Services\ConversationService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ChatController extends Controller
{
    public function __construct(private readonly ConversationService $service) {}

    /**
     * Daftar percakapan.
     */
    public function index(Request $request): JsonResponse
    {
        $filter = in_array((string) $request->query('filter', 'semua'), ['semua', 'membeli', 'menjual', 'belum'], true)
            ? (string) $request->query('filter')
            : 'semua';

        $conversations = $this->service->listFor((string) $request->user()->id, $filter);

        return ApiResponse::success(
            ConversationResource::collection($conversations),
            meta: ['unread_total' => $this->service->unreadTotal((string) $request->user()->id)],
        );
    }

    public function unreadCount(Request $request): JsonResponse
    {
        return ApiResponse::success(['unread_count' => $this->service->unreadTotal((string) $request->user()->id)]);
    }

    /**
     * Mulai percakapan dari produk (opsional tawaran langsung).
     */
    public function start(Request $request): JsonResponse
    {
        $data = $request->validate([
            'product_id' => ['required', 'uuid', 'exists:products,id'],
            'offer_amount' => ['nullable', 'integer', 'min:1'],
        ]);

        $conversation = $this->service->start(
            (string) $request->user()->id,
            $data['product_id'],
            isset($data['offer_amount']) ? (int) $data['offer_amount'] : null,
        );

        $conversation->load(['product.coverImage', 'buyer', 'seller', 'lastMessage.images', 'latestOffer']);

        return ApiResponse::success(new ConversationResource($conversation), 'Percakapan dimulai.');
    }

    /**
     * Ruang percakapan + pesan terakhir.
     */
    public function show(Request $request, Conversation $conversation): JsonResponse
    {
        $userId = (string) $request->user()->id;
        $this->service->authorizeParticipant($conversation, $userId);

        $conversation->load(['product.coverImage', 'buyer', 'seller', 'latestOffer']);
        $messages = $this->service->messages($conversation, $userId);

        $order = $this->service->orderFor($conversation);
        $order?->load(['product.primaryImage', 'buyer', 'seller']);

        $myReview = $order?->reviewBy($userId);
        $canMarkSold = $order !== null
            && $conversation->seller_id === $userId
            && $order->status === Order::STATUS_DISEPAKATI
            && $conversation->product?->status === Product::STATUS_DIJUAL;

        return ApiResponse::success([
            'conversation' => new ConversationResource($conversation),
            'messages' => MessageResource::collection($messages),
            'order' => $order ? new OrderResource($order) : null,
            'my_review' => $myReview ? new ReviewResource($myReview) : null,
            'can_mark_sold' => $canMarkSold,
            'receipts' => $this->service->receipts($conversation, $userId),
            'presence' => $this->service->presence($conversation, $userId),
        ]);
    }

    /**
     * Polling pesan baru.
     */
    public function poll(Request $request, Conversation $conversation): JsonResponse
    {
        $userId = (string) $request->user()->id;
        $this->service->authorizeParticipant($conversation, $userId);

        $after = max(0, (int) $request->query('after', 0));
        $visible = filter_var($request->query('visible', 'true'), FILTER_VALIDATE_BOOL);

        $result = $this->service->poll($conversation, $userId, $after, $visible);

        return ApiResponse::success([
            'messages' => MessageResource::collection($result['messages']),
            'offers' => MessageResource::collection($result['offers']),
            'receipts' => $result['receipts'],
            'presence' => $result['presence'],
            'unread_total' => $result['unread_total'],
        ]);
    }

    /**
     * Tandai pesan lawan sudah dibaca.
     */
    public function read(Request $request, Conversation $conversation): JsonResponse
    {
        $userId = (string) $request->user()->id;
        $this->service->authorizeParticipant($conversation, $userId);
        $this->service->markRead($conversation, $userId);

        return ApiResponse::success([
            'receipts' => $this->service->receipts($conversation, $userId),
            'unread_total' => $this->service->unreadTotal($userId),
        ]);
    }

    /**
     * Kirim pesan teks/gambar/tawaran.
     */
    public function send(StoreMessageRequest $request, Conversation $conversation): JsonResponse
    {
        $userId = (string) $request->user()->id;
        $this->service->authorizeParticipant($conversation, $userId);

        $files = [
            ...($request->hasFile('image') ? [$request->file('image')] : []),
            ...($request->file('images') ?? []),
        ];

        $message = $this->service->send($conversation, $userId, $request->validated(), $files);

        return ApiResponse::success(new MessageResource($message), 'Pesan terkirim.');
    }

    public function updateOffer(Request $request, Conversation $conversation, Message $message): JsonResponse
    {
        $data = $request->validate(['offer_amount' => ['required', 'integer', 'min:1']]);
        $message = $this->service->updateOffer($conversation, $message, (string) $request->user()->id, (int) $data['offer_amount']);

        return ApiResponse::success(new MessageResource($message), 'Tawaran diperbarui.');
    }

    public function cancelOffer(Request $request, Conversation $conversation, Message $message): JsonResponse
    {
        $system = $this->service->cancelOffer($conversation, $message, (string) $request->user()->id);

        return ApiResponse::success([
            'message' => new MessageResource($message),
            'system' => new MessageResource($system),
        ], 'Tawaran dibatalkan.');
    }

    public function acceptOffer(Request $request, Conversation $conversation, Message $message): JsonResponse
    {
        $system = $this->service->acceptOffer($conversation, $message, (string) $request->user()->id);
        $order = $this->service->orderFor($conversation)?->load(['product.primaryImage', 'buyer', 'seller']);

        return ApiResponse::success([
            'message' => new MessageResource($message),
            'system' => new MessageResource($system),
            'order' => $order ? new OrderResource($order) : null,
        ], 'Tawaran disetujui.');
    }

    public function rejectOffer(Request $request, Conversation $conversation, Message $message): JsonResponse
    {
        $system = $this->service->rejectOffer($conversation, $message, (string) $request->user()->id);

        return ApiResponse::success([
            'message' => new MessageResource($message),
            'system' => new MessageResource($system),
        ], 'Tawaran ditolak.');
    }

    public function sold(Request $request, Conversation $conversation): JsonResponse
    {
        $this->service->markSold($conversation, (string) $request->user()->id);
        $conversation->load(['product.coverImage', 'buyer', 'seller']);
        $order = $this->service->orderFor($conversation)?->load(['product.primaryImage', 'buyer', 'seller']);

        return ApiResponse::success([
            'conversation' => new ConversationResource($conversation),
            'order' => $order ? new OrderResource($order) : null,
        ], 'Produk ditandai terjual.');
    }

    public function destroy(Request $request, Message $message): JsonResponse
    {
        $this->service->deleteMessage($message, (string) $request->user()->id);

        return ApiResponse::success(null, 'Pesan dihapus.');
    }
}
