@php
    $product = $report->product;
    $seller = $product?->seller;
    $productExists = $product && ! $product->trashed();
    $productSuspended = $productExists && $product->status === \App\Models\Product::STATUS_DITANGGUIHKAN;
    $sellerBannable = $seller && ! $seller->isAdmin();
    $reportOpen = in_array($report->status, [
        \App\Models\Report::STATUS_PENDING,
        \App\Models\Report::STATUS_REVIEWED,
    ], true);
    $statusTone = match ($report->status) {
        \App\Models\Report::STATUS_PENDING => ['dot' => 'bg-warning', 'text' => 'text-warning-shade'],
        \App\Models\Report::STATUS_REVIEWED => ['dot' => 'bg-info', 'text' => 'text-info-shade'],
        \App\Models\Report::STATUS_RESOLVED => ['dot' => 'bg-success', 'text' => 'text-success-shade'],
        \App\Models\Report::STATUS_REJECTED => ['dot' => 'bg-neutral-400', 'text' => 'text-neutral-600'],
        default => ['dot' => 'bg-neutral-400', 'text' => 'text-neutral-600'],
    };
@endphp

<div class="md:grid md:grid-cols-[minmax(0,1fr)_14rem] md:gap-6">
    {{-- Konteks laporan: apa yang dilaporkan dan mengapa --}}
    <div class="space-y-5">
        <div class="flex gap-3">
            <div class="size-16 shrink-0 overflow-hidden rounded-xl border border-neutral-200 bg-neutral-100">
                @if ($product?->gambar)
                    <img src="{{ asset($product->gambar) }}" alt="{{ $product->judul }}"
                        class="size-full object-cover" onerror="this.remove()">
                @endif
            </div>
            <div class="min-w-0 flex-1">
                <p class="text-sm font-bold leading-snug text-neutral-900">
                    {{ $product?->judul ?? 'Produk telah dihapus' }}
                </p>
                @if ($product)
                    <p class="mt-0.5 text-sm font-extrabold text-neutral-900">
                        Rp {{ number_format((int) $product->harga, 0, ',', '.') }}
                    </p>
                @endif
                <p class="mt-1 truncate text-[11px] text-neutral-500">
                    Dijual oleh {{ $seller?->name ?? '—' }}
                </p>
                @if ($productExists)
                    <a href="{{ $product->detailUrl() }}" target="_blank" rel="noopener noreferrer"
                        class="mt-1 inline-flex items-center gap-1 text-[11px] font-semibold text-neutral-700 underline underline-offset-2 hover:text-neutral-900">
                        Lihat produk
                        <svg viewBox="0 0 24 24" class="size-3 fill-none stroke-current stroke-2" aria-hidden="true">
                            <path d="M7 17 17 7M7 7h10v10" />
                        </svg>
                    </a>
                @endif
            </div>
        </div>

        <div class="border-t border-neutral-100 pt-4">
            <p class="text-xs font-semibold text-neutral-500">Alasan dilaporkan</p>
            <p class="mt-1 text-sm font-bold text-danger-shade">{{ $report->alasan_label }}</p>
            <p class="mt-2 text-xs leading-relaxed text-neutral-600">{{ $report->keterangan }}</p>
        </div>

        @if ($report->bukti_url)
            <div class="border-t border-neutral-100 pt-4">
                <p class="mb-2 text-xs font-semibold text-neutral-500">Bukti gambar</p>
                <a href="{{ $report->bukti_url }}" target="_blank" rel="noopener noreferrer" class="block">
                    <img src="{{ $report->bukti_url }}" alt="Bukti laporan"
                        class="max-h-56 w-full rounded-lg border border-neutral-200 bg-neutral-50 object-contain">
                </a>
            </div>
        @endif

        <div class="border-t border-neutral-100 pt-4">
            <p class="text-xs text-neutral-500">
                Dilaporkan oleh
                <span class="font-semibold text-neutral-800">{{ $report->reporter?->name ?? '—' }}</span>
            </p>
            <p class="mt-0.5 text-[11px] text-neutral-400">
                {{ $report->created_at?->locale('id')->translatedFormat('d M Y, H:i') }}
            </p>
        </div>
    </div>

    {{-- Konteks keputusan: status dan tindakan --}}
    <aside class="mt-6 border-t border-neutral-200 pt-5 md:mt-0 md:border-l md:border-t-0 md:pl-5 md:pt-0">
        <p class="text-xs font-semibold text-neutral-500">Status laporan</p>
        <div class="mt-1.5 flex items-center justify-between gap-2">
            <span class="inline-flex items-center gap-1.5 text-xs font-bold {{ $statusTone['text'] }}">
                <span class="size-1.5 rounded-full {{ $statusTone['dot'] }}" aria-hidden="true"></span>
                {{ $report->status_label }}
            </span>

            @if ($report->status === \App\Models\Report::STATUS_PENDING)
                <form method="POST" action="{{ route('admin.reports.moderate', $report) }}">
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="action" value="review">
                    <button type="submit"
                        class="text-[11px] font-semibold text-neutral-500 underline underline-offset-2 transition hover:text-neutral-900">
                        Tandai ditinjau
                    </button>
                </form>
            @endif
        </div>

        <div class="mt-5">
            <p class="text-xs font-semibold text-neutral-500">Tindakan</p>

            <div class="mt-2 space-y-2">
                @if ($productExists)
                    @if ($productSuspended)
                        <form method="POST" action="{{ route('admin.reports.moderate', $report) }}">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="action" value="restore_product">
                            <button type="submit"
                                class="w-full rounded-lg bg-lime-400 px-3 py-2.5 text-[11px] font-bold text-neutral-900 transition hover:bg-lime-500 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-neutral-900 focus-visible:ring-offset-2">
                                Aktifkan Produk
                            </button>
                        </form>
                    @else
                        <form method="POST" action="{{ route('admin.reports.moderate', $report) }}"
                            onsubmit="return confirm('Suspend produk ini? Produk akan hilang dari katalog.')">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="action" value="suspend_product">
                            <button type="submit"
                                class="w-full rounded-lg bg-danger px-3 py-2.5 text-[11px] font-bold text-white transition hover:bg-danger-shade focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-danger focus-visible:ring-offset-2">
                                Suspend Produk
                            </button>
                        </form>
                    @endif
                @endif

                @if ($sellerBannable)
                    @if ($seller->is_banned)
                        <form method="POST" action="{{ route('admin.reports.moderate', $report) }}">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="action" value="unban_seller">
                            <button type="submit"
                                class="w-full rounded-lg border border-neutral-300 bg-white px-3 py-2.5 text-[11px] font-bold text-neutral-700 transition hover:bg-neutral-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-neutral-900 focus-visible:ring-offset-2">
                                Aktifkan Akun Penjual
                            </button>
                        </form>
                    @else
                        <form method="POST" action="{{ route('admin.reports.moderate', $report) }}"
                            onsubmit="return confirm('Ban akun penjual ini? Semua produknya akan disembunyikan.')">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="action" value="ban_seller">
                            <button type="submit"
                                class="w-full rounded-lg border border-danger-tint bg-white px-3 py-2.5 text-[11px] font-bold text-danger-shade transition hover:bg-danger-tint focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-danger focus-visible:ring-offset-2">
                                Ban Penjual
                            </button>
                        </form>
                    @endif
                @endif

                @if ($reportOpen)
                    <form method="POST" action="{{ route('admin.reports.moderate', $report) }}">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="action" value="resolve">
                        <button type="submit"
                            class="w-full rounded-lg border border-neutral-300 bg-white px-3 py-2.5 text-[11px] font-bold text-neutral-700 transition hover:bg-neutral-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-neutral-900 focus-visible:ring-offset-2">
                            Selesaikan Laporan
                        </button>
                    </form>

                    <form method="POST" action="{{ route('admin.reports.moderate', $report) }}"
                        onsubmit="return confirm('Tolak laporan ini?')">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="action" value="reject">
                        <button type="submit"
                            class="w-full rounded-lg px-3 py-2 text-[11px] font-semibold text-neutral-500 transition hover:bg-neutral-100 hover:text-neutral-900">
                            Tolak Laporan
                        </button>
                    </form>
                @endif
            </div>

            @if ($reportOpen && ($productExists || $sellerBannable))
                <p class="mt-3 text-[11px] leading-relaxed text-neutral-400">
                    Suspend produk atau ban penjual akan menutup laporan ini.
                </p>
            @endif
        </div>
    </aside>
</div>
