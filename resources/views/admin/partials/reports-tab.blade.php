@php
    $statusTone = function ($status) {
        return match ($status) {
            \App\Models\Report::STATUS_PENDING => ['dot' => 'bg-warning', 'text' => 'text-warning-shade'],
            \App\Models\Report::STATUS_REVIEWED => ['dot' => 'bg-info', 'text' => 'text-info-shade'],
            \App\Models\Report::STATUS_RESOLVED => ['dot' => 'bg-success', 'text' => 'text-success-shade'],
            \App\Models\Report::STATUS_REJECTED => ['dot' => 'bg-neutral-400', 'text' => 'text-neutral-600'],
            default => ['dot' => 'bg-neutral-400', 'text' => 'text-neutral-600'],
        };
    };
@endphp

{{-- Tab Laporan: tabel ringkas di desktop, daftar kartu di mobile. --}}
<div class="space-y-4">
    <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <h2 class="font-heading text-lg font-bold tracking-tight text-neutral-900">Laporan produk</h2>
            <p class="mt-0.5 text-xs leading-relaxed text-neutral-500">
                Klik salah satu baris untuk membaca kronologi dan menindaklanjutinya.
            </p>
        </div>
        @if ($reports->isNotEmpty())
            <p class="text-xs font-semibold text-neutral-500">{{ $reports->total() }} laporan</p>
        @endif
    </div>

    @if ($reports->isEmpty())
        <div class="rounded-xl border border-neutral-200 bg-white px-6 py-16 text-center">
            <div class="mx-auto grid size-11 place-items-center rounded-full bg-lime-100 text-lime-900">
                <svg viewBox="0 0 24 24" class="size-5 fill-none stroke-current stroke-2" aria-hidden="true">
                    <path d="M20 6 9 17l-5-5" />
                </svg>
            </div>
            <h3 class="mt-4 font-heading text-base font-bold text-neutral-900">Antrean bersih</h3>
            <p class="mx-auto mt-1 max-w-sm text-xs leading-relaxed text-neutral-500">
                Tidak ada laporan produk yang perlu ditinjau saat ini. Laporan baru akan muncul di sini.
            </p>
        </div>
    @else
        {{-- Desktop: tabel --}}
        <div class="hidden overflow-hidden rounded-xl border border-neutral-200 bg-white md:block">
            <table class="w-full text-left">
                <thead class="border-b border-neutral-200 text-[11px] font-semibold text-neutral-500">
                    <tr>
                        <th class="w-12 px-4 py-2.5">No</th>
                        <th class="px-4 py-2.5">Produk</th>
                        <th class="w-44 px-4 py-2.5">Alasan</th>
                        <th class="w-32 px-4 py-2.5">Status</th>
                        <th class="w-28 px-4 py-2.5">Tanggal</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-neutral-100">
                    @foreach ($reports as $report)
                        @php
                            $tone = $statusTone($report->status);
                        @endphp
                        <tr data-report-row data-report-url="{{ route('admin.reports.show', $report) }}"
                            tabindex="0" role="button"
                            aria-label="Lihat detail laporan produk {{ $report->product?->judul }}"
                            class="cursor-pointer transition hover:bg-lime-50/40 focus:bg-lime-50/60 focus:outline-none">
                            <td class="relative px-4 py-3 text-xs text-neutral-400">
                                @if ($report->status === \App\Models\Report::STATUS_PENDING)
                                    <span class="absolute inset-y-0 left-0 w-0.5 bg-lime-500" aria-hidden="true"></span>
                                @endif
                                {{ ($reports->firstItem() ?? 1) + $loop->index }}
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3">
                                    <div
                                        class="size-11 shrink-0 overflow-hidden rounded-lg bg-neutral-100 ring-1 ring-neutral-200">
                                        @if ($report->product?->gambar)
                                            <img src="{{ asset($report->product->gambar) }}"
                                                alt="{{ $report->product->judul }}" loading="lazy"
                                                class="size-full object-cover" onerror="this.remove()">
                                        @endif
                                    </div>
                                    <div class="min-w-0">
                                        <p class="max-w-64 truncate text-xs font-bold text-neutral-900">
                                            {{ $report->product?->judul ?? 'Produk telah dihapus' }}
                                        </p>
                                        <p class="mt-0.5 max-w-64 truncate text-[11px] text-neutral-500">
                                            {{ $report->product?->seller?->name ?? '—' }}
                                        </p>
                                        @if ($report->product?->trashed())
                                            <p class="text-[11px] font-semibold text-danger-shade">Produk dihapus</p>
                                        @elseif ($report->product?->status === \App\Models\Product::STATUS_DITANGGUIHKAN)
                                            <p class="text-[11px] text-neutral-700">Ditangguhkan</p>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-[11px] font-semibold text-danger-shade">
                                {{ $report->alasan_label }}
                            </td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center gap-1.5 text-[11px] font-bold {{ $tone['text'] }}">
                                    <span class="size-1.5 rounded-full {{ $tone['dot'] }}" aria-hidden="true"></span>
                                    {{ $report->status_label }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-[11px] text-neutral-500">
                                {{ $report->created_at?->locale('id')->translatedFormat('d M Y') }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Mobile: daftar kartu --}}
        <div class="space-y-3 md:hidden">
            @foreach ($reports as $report)
                @php
                    $tone = $statusTone($report->status);
                @endphp
                <article data-report-row data-report-url="{{ route('admin.reports.show', $report) }}" tabindex="0"
                    role="button" aria-label="Lihat detail laporan produk {{ $report->product?->judul }}"
                    class="relative overflow-hidden rounded-xl border border-neutral-200 bg-white p-3.5 transition focus:bg-lime-50/60 focus:outline-none">
                    @if ($report->status === \App\Models\Report::STATUS_PENDING)
                        <span class="absolute inset-y-0 left-0 w-0.5 bg-lime-500" aria-hidden="true"></span>
                    @endif
                    <div class="flex gap-3">
                        <div class="size-14 shrink-0 overflow-hidden rounded-lg bg-neutral-100 ring-1 ring-neutral-200">
                            @if ($report->product?->gambar)
                                <img src="{{ asset($report->product->gambar) }}"
                                    alt="{{ $report->product->judul }}" loading="lazy" class="size-full object-cover"
                                    onerror="this.remove()">
                            @endif
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-xs font-bold text-neutral-900">
                                {{ $report->product?->judul ?? 'Produk telah dihapus' }}
                            </p>
                            <p class="mt-0.5 truncate text-[11px] text-neutral-500">
                                {{ $report->product?->seller?->name ?? '—' }}
                            </p>
                            <p class="truncate text-[11px] text-neutral-400">
                                {{ $report->created_at?->locale('id')->diffForHumans() }}
                            </p>
                            <div class="mt-2 flex flex-wrap items-center gap-x-3 gap-y-1">
                                <span class="text-[11px] font-semibold text-danger-shade">{{ $report->alasan_label }}</span>
                                <span
                                    class="inline-flex items-center gap-1.5 text-[11px] font-bold {{ $tone['text'] }}">
                                    <span class="size-1.5 rounded-full {{ $tone['dot'] }}" aria-hidden="true"></span>
                                    {{ $report->status_label }}
                                </span>
                            </div>
                        </div>
                    </div>
                </article>
            @endforeach
        </div>

        <div class="pt-1">
            {{ $reports->links() }}
        </div>
    @endif
</div>
