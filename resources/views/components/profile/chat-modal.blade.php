@props([
    'sellerName',
    'chatProducts' => collect(),
])

<div id="seller-chat-modal"
     class="fixed inset-0 z-50 hidden overflow-y-auto bg-black/60 p-4 backdrop-blur-sm"
     role="dialog" aria-modal="true" aria-labelledby="seller-chat-title">
    <div class="mx-auto mt-[10vh] flex w-full max-w-md flex-col overflow-hidden rounded-2xl bg-white shadow-xl">
        <div class="flex items-center justify-between gap-3 border-b border-neutral-200 p-4">
            <div class="min-w-0">
                <h2 id="seller-chat-title" class="truncate text-sm font-bold text-neutral-900">
                    Chat dengan {{ $sellerName }}
                </h2>
                <p class="mt-0.5 text-[11px] text-neutral-500">Pilih produk yang ingin ditanyakan.</p>
            </div>
            <button type="button" id="seller-chat-close"
                    class="grid size-8 shrink-0 place-items-center rounded-full text-neutral-500 transition hover:bg-neutral-100 hover:text-neutral-900"
                    aria-label="Tutup pilihan produk">
                <svg viewBox="0 0 24 24" class="size-4 fill-none stroke-current stroke-2"><path d="M18 6 6 18M6 6l12 12" stroke-linecap="round"/></svg>
            </button>
        </div>

        <div class="max-h-[55vh] overflow-y-auto p-2">
            @forelse ($chatProducts as $chatProduct)
                <form method="POST" action="{{ route('chat.store') }}">
                    @csrf
                    <input type="hidden" name="product_id" value="{{ $chatProduct->id }}">
                    <button type="submit"
                            class="flex w-full items-center gap-3 rounded-xl px-2 py-2 text-left transition hover:bg-neutral-100 cursor-pointer">
                        <div class="size-12 shrink-0 overflow-hidden rounded-lg bg-neutral-200">
                            @if ($chatProduct->gambar)
                                <img src="{{ asset($chatProduct->gambar) }}" alt="" class="size-full object-cover">
                            @endif
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-xs font-bold text-neutral-900">{{ $chatProduct->judul }}</p>
                            <p class="mt-0.5 text-xs font-extrabold text-neutral-900">Rp {{ number_format($chatProduct->harga, 0, ',', '.') }}</p>
                        </div>
                        <svg viewBox="0 0 24 24" class="size-4 shrink-0 fill-none stroke-current stroke-2 text-neutral-400"><path d="m9 18 6-6-6-6" stroke-linecap="round"/></svg>
                    </button>
                </form>
            @empty
                <p class="px-3 py-6 text-center text-xs text-neutral-500">Penjual belum punya produk aktif.</p>
            @endforelse
        </div>
    </div>
</div>