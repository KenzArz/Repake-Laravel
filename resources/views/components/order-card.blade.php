@props([
    'order',
    'role' => 'pembelian',
])

@php
    use App\Models\Order;
    use App\Models\Product;

    $isSelling = $role === 'penjualan';
    $counterparty = $isSelling ? $order->buyer : $order->seller;
    $counterpartyLabel = $isSelling ? 'Pembeli' : 'Penjual';
    $counterpartyName = $counterparty?->name ?? 'Pengguna';
    $counterpartyAvatar = $counterparty?->avatar_url ?? asset('images/default-avatar.png');
    $counterpartyUrl = $counterparty?->profileUrl();

    $product = $order->product;
    $productTitle = $product?->judul ?? 'Produk tidak tersedia';
    $productImage = $product?->gambar ? asset($product->gambar) : null;
    $productUrl = $product && Route::has('products.show') ? route('products.show', $product) : null;
    $isNegotiated = $product && (int) $product->harga > (int) $order->harga_final;

    $chatUrl = $order->conversation_id && Route::has('chat.show')
        ? route('chat.show', $order->conversation_id)
        : (Route::has('chat.index') ? route('chat.index') : null);

    $statusConfig = match ($order->status) {
        Order::STATUS_DISEPAKATI => [
            'label' => 'Menunggu COD',
            'classes' => 'border-amber-300 bg-amber-50 text-amber-800',
            'dot' => 'bg-amber-500',
        ],
        Order::STATUS_SELESAI => [
            'label' => 'Selesai',
            'classes' => 'border-lime-300 bg-lime-50 text-lime-800',
            'dot' => 'bg-lime-600',
        ],
        Order::STATUS_BATAL => [
            'label' => 'Dibatalkan',
            'classes' => 'border-red-200 bg-red-50 text-red-700',
            'dot' => 'bg-red-500',
        ],
        default => [
            'label' => ucfirst((string) $order->status),
            'classes' => 'border-neutral-200 bg-neutral-100 text-neutral-700',
            'dot' => 'bg-neutral-400',
        ],
    };

    $canComplete = $isSelling
        && $order->status === Order::STATUS_DISEPAKATI
        && $product?->status === Product::STATUS_DIJUAL;

    // Pembeli bisa menyelesaikan sendiri dengan konfirmasi terima barang.
    $canConfirmReceipt = ! $isSelling && $order->status === Order::STATUS_DISEPAKATI;

    // Penjual bisa menutup iklan setelah transaksi selesai (mis. pembeli lebih
    // dulu konfirmasi, ternyata unit terakhir habis).
    $canMarkStockOut = $isSelling
        && $order->status === Order::STATUS_SELESAI
        && $order->completion_type !== Order::COMPLETION_STOCK_OUT
        && $product?->status === Product::STATUS_DIJUAL;

    $canReview = $order->status === Order::STATUS_SELESAI && !($order->has_reviewed ?? false);
@endphp

<article
    class="rounded-2xl border border-neutral-200 bg-white p-4 sm:p-5 shadow-2xs transition hover:border-neutral-300"
    data-order-card>
    {{-- Header Kartu: Lawan Transaksi, Tanggal & Badge Status --}}
    <div class="flex flex-wrap items-center justify-between gap-2 border-b border-neutral-100 pb-3">
        <div class="flex items-center gap-2 min-w-0">
            <span class="text-[11px] font-medium text-neutral-400">{{ $counterpartyLabel }}:</span>

            @if ($counterpartyUrl)
                <a href="{{ $counterpartyUrl }}" class="group inline-flex items-center gap-2 min-w-0">
                    <img src="{{ $counterpartyAvatar }}" alt="{{ $counterpartyName }}" referrerpolicy="no-referrer"
                        onerror="this.onerror=null; this.src='{{ asset('images/default-avatar.png') }}';"
                        class="size-6 shrink-0 rounded-full border border-neutral-200 object-cover">
                    <span
                        class="truncate text-xs font-bold text-neutral-900 transition group-hover:text-lime-700 group-hover:underline">
                        {{ $counterpartyName }}
                    </span>
                </a>
            @else
                <div class="inline-flex items-center gap-2 min-w-0">
                    <img src="{{ $counterpartyAvatar }}" alt="{{ $counterpartyName }}"
                        class="size-6 shrink-0 rounded-full border border-neutral-200 object-cover">
                    <span class="truncate text-xs font-bold text-neutral-900">{{ $counterpartyName }}</span>
                </div>
            @endif

            <span class="text-neutral-300" aria-hidden="true">•</span>
            <time datetime="{{ $order->created_at?->toIso8601String() }}" data-local-time="date"
                class="shrink-0 text-[11px] text-neutral-500">
                {{ $order->created_at?->copy()->timezone('Asia/Jakarta')->locale('id')->translatedFormat('d M Y') }}
            </time>
        </div>

        <span
            class="inline-flex items-center gap-1.5 rounded-full border px-2.5 py-0.5 text-[11px] font-bold {{ $statusConfig['classes'] }}">
            <span class="size-1.5 rounded-full {{ $statusConfig['dot'] }}"></span>
            <span>{{ $statusConfig['label'] }}</span>
        </span>
    </div>

    {{-- Body Kartu: Info Produk, Harga Kesepakatan & Aksi --}}
    <div class="mt-3.5 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex items-center gap-3.5 min-w-0">
            <div
                class="size-16 sm:size-18 shrink-0 overflow-hidden rounded-xl border border-neutral-200/80 bg-neutral-100">
                @if ($productImage)
                    <img src="{{ $productImage }}" alt="{{ $productTitle }}" class="size-full object-cover">
                @else
                    <div class="grid size-full place-items-center text-neutral-400">
                        <svg viewBox="0 0 24 24" class="size-6 fill-none stroke-current stroke-1.5">
                            <rect x="3" y="3" width="18" height="18" rx="2" />
                            <circle cx="8.5" cy="8.5" r="1.5" />
                            <path d="m21 15-5-5L5 21" />
                        </svg>
                    </div>
                @endif
            </div>

            <div class="min-w-0 flex-1">
                @if ($productUrl)
                    <a href="{{ $productUrl }}"
                        class="line-clamp-1 text-sm font-bold text-neutral-900 transition hover:text-lime-700">
                        {{ $productTitle }}
                    </a>
                @else
                    <p class="line-clamp-1 text-sm font-bold text-neutral-900">{{ $productTitle }}</p>
                @endif

                <div class="mt-1 flex flex-wrap items-baseline gap-x-2 gap-y-0.5">
                    <span class="text-[11px] font-medium text-neutral-500">Harga Deal</span>
                    <span class="text-sm font-extrabold tabular-nums text-neutral-900">
                        Rp {{ number_format($order->harga_final, 0, ',', '.') }}
                    </span>
                    @if ($isNegotiated)
                        <span class="text-xs tabular-nums text-neutral-400 line-through">
                            Rp {{ number_format($product->harga, 0, ',', '.') }}
                        </span>
                    @endif
                </div>

                @if ($order->status === Order::STATUS_SELESAI && $order->completed_at)
                    <p class="mt-1 text-[11px] text-neutral-500">
                        Selesai pada
                        <time datetime="{{ $order->completed_at->toIso8601String() }}" data-local-time="datetime">
                            {{ $order->completed_at->copy()->timezone('Asia/Jakarta')->locale('id')->translatedFormat('d M Y, H.i') }}
                            WIB
                        </time>
                        @if ($order->completion_type === \App\Models\Order::COMPLETION_STOCK_AVAILABLE)
                            · stok masih tersedia
                        @endif
                        @if ($order->completed_by === \App\Models\Order::COMPLETED_BY_BUYER)
                            · dikonfirmasi pembeli
                        @endif
                    </p>
                @elseif (!$isSelling && $order->status === Order::STATUS_DISEPAKATI)
                    <p class="mt-1 text-[11px] text-neutral-500">
                        Sudah COD? Konfirmasi "Barang Diterima" agar transaksi selesai dan bisa diulas.
                    </p>
                @endif
            </div>
        </div>

        {{-- Zona Aksi Kontekstual --}}
        <div
            class="flex flex-wrap items-center justify-end gap-2 shrink-0 border-t border-neutral-100 pt-3 sm:border-t-0 sm:pt-0">
            @if ($chatUrl && $order->status === Order::STATUS_DISEPAKATI)
                <a href="{{ $chatUrl }}"
                    class="inline-flex items-center justify-center gap-1.5 rounded-lg border border-neutral-300 bg-white px-3.5 py-2 text-xs font-semibold text-neutral-700 transition hover:bg-neutral-100">
                    <svg viewBox="0 0 24 24" class="size-3.5 fill-none stroke-current stroke-2">
                        <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z" />
                    </svg>
                    <span>Chat</span>
                </a>
            @endif

            @if ($order->status === Order::STATUS_DISEPAKATI)
                <form method="POST" action="{{ route('orders.update-status', $order) }}">
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="status" value="{{ Order::STATUS_BATAL }}">
                    <button type="button" data-confirm-trigger data-confirm-variant="danger"
                        data-confirm-title="Batalkan Kesepakatan?"
                        data-confirm-message="Kesepakatan transaksi untuk produk '{{ $productTitle }}' bersama {{ $counterpartyName }} akan dibatalkan."
                        data-confirm-btn="Ya, Batalkan"
                        class="rounded-lg px-3 py-2 text-xs font-semibold text-neutral-500 transition hover:bg-red-50 hover:text-red-600 cursor-pointer">
                        Batalkan
                    </button>
                </form>

                @if ($canConfirmReceipt)
                    <form method="POST" action="{{ route('orders.update-status', $order) }}">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="status" value="{{ Order::STATUS_SELESAI }}">
                        <button type="button" data-confirm-trigger data-confirm-variant="primary"
                            data-confirm-title="Konfirmasi Barang Diterima?"
                            data-confirm-message="Pastikan Anda sudah menerima '{{ $productTitle }}' dari {{ $counterpartyName }}. Transaksi akan selesai dan Anda bisa memberi ulasan."
                            data-confirm-btn="Ya, Sudah Diterima"
                            class="rounded-lg bg-emerald-600 px-4 py-2 text-xs font-bold text-white shadow-2xs transition hover:bg-emerald-700 cursor-pointer">
                            Barang Diterima
                        </button>
                    </form>
                @endif

                @if ($canComplete)
                    <button type="button" data-complete-trigger
                        data-complete-action="{{ Route::has('orders.update-status') ? route('orders.update-status', $order) : '#' }}"
                        data-complete-method="PATCH"
                        data-complete-product="{{ $productTitle }}" data-complete-counter="{{ $counterpartyName }}"
                        class="rounded-lg bg-lime-400 px-4 py-2 text-xs font-bold text-neutral-900 shadow-2xs transition hover:bg-lime-500 cursor-pointer">
                        Selesaikan
                    </button>
                @endif
            @elseif ($order->status === Order::STATUS_SELESAI)
                @if ($canMarkStockOut)
                    <form method="POST" action="{{ route('orders.update-status', $order) }}">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="status" value="{{ Order::STATUS_SELESAI }}">
                        <input type="hidden" name="completion_type" value="{{ Order::COMPLETION_STOCK_OUT }}">
                        <button type="button" data-confirm-trigger data-confirm-variant="danger"
                            data-confirm-title="Tandai Stok Habis?"
                            data-confirm-message="Produk '{{ $productTitle }}' akan ditutup dan order lain yang masih menunggu COD otomatis dibatalkan."
                            data-confirm-btn="Ya, Stok Habis"
                            class="rounded-lg border border-neutral-300 bg-white px-3.5 py-2 text-xs font-semibold text-neutral-700 transition hover:bg-neutral-100 cursor-pointer">
                            Tandai Stok Habis
                        </button>
                    </form>
                @endif

                @if ($canReview)
                    <button type="button" data-order-review-toggle
                        class="rounded-lg bg-lime-400 px-4 py-2 text-xs font-bold text-neutral-900 shadow-2xs transition hover:bg-lime-500 cursor-pointer">
                        Beri Ulasan
                    </button>
                @else
                    <span
                        class="inline-flex items-center gap-1 rounded-lg bg-neutral-100 px-3 py-1.5 text-xs font-semibold text-neutral-600">
                        <span class="text-amber-500">★</span>
                        <span>Sudah Diulas</span>
                    </span>
                @endif
            @elseif ($order->status === Order::STATUS_BATAL && $productUrl)
                <a href="{{ $productUrl }}"
                    class="rounded-lg border border-neutral-300 bg-white px-3.5 py-2 text-xs font-semibold text-neutral-700 transition hover:bg-neutral-100">
                    Lihat Produk
                </a>
            @endif
        </div>
    </div>

    {{-- Form Ulasan Inline --}}
    @if ($canReview && Route::has('reviews.store'))
        <form method="POST" action="{{ route('reviews.store', $order) }}" hidden data-order-review-form
            class="mt-4 rounded-xl border border-lime-200 bg-lime-50/80 p-4">
            @csrf
            <div class="flex items-center justify-between gap-2">
                <p class="text-xs font-bold text-neutral-900">
                    Beri ulasan untuk {{ $counterpartyName }}
                </p>
                <span class="text-[11px] text-neutral-500">Wajib pilih bintang</span>
            </div>

            <div class="mt-2.5" data-rating-input>
                <input type="hidden" name="rating" value="" required>
                <div class="flex items-center gap-1">
                    @for ($i = 1; $i <= 5; $i++)
                        <button type="button" data-rating-star="{{ $i }}" aria-label="{{ $i }} bintang"
                            class="text-neutral-300 transition hover:scale-110 cursor-pointer">
                            <svg viewBox="0 0 24 24" class="size-7 fill-current" aria-hidden="true">
                                <path
                                    d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z" />
                            </svg>
                        </button>
                    @endfor
                </div>
            </div>

            <textarea name="komentar" rows="2" maxlength="500"
                placeholder="Ceritakan pengalaman transaksi Anda (opsional)..."
                class="mt-2.5 w-full rounded-lg border border-neutral-300 bg-white p-2.5 text-xs font-medium leading-relaxed text-neutral-900 focus:border-neutral-900 focus:outline-none"></textarea>

            <div class="mt-3 flex items-center justify-end gap-2">
                <button type="button" data-order-review-cancel
                    class="rounded-lg border border-neutral-300 bg-white px-3.5 py-1.5 text-xs font-semibold text-neutral-700 transition hover:bg-neutral-100 cursor-pointer">
                    Batal
                </button>
                <button type="submit"
                    class="rounded-lg bg-lime-500 px-4 py-1.5 text-xs font-bold text-neutral-900 transition hover:bg-lime-600 cursor-pointer">
                    Kirim Ulasan
                </button>
            </div>
        </form>
    @endif
</article>