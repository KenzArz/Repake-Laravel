@extends('layouts.app')

@section('title', 'Transaksi - RePake')

@section('content')
    @php
        use App\Models\Order;

        $viewOptions = ['aktif' => 'Transaksi Aktif', 'riwayat' => 'Riwayat'];
        $statusOptions = [
            'semua' => 'Semua',
            Order::STATUS_SELESAI => 'Selesai',
            Order::STATUS_BATAL => 'Dibatalkan',
        ];
    @endphp

    <div class="mx-auto max-w-6xl px-4 py-6 sm:px-6">
        {{-- Header Halaman --}}
        <div class="mb-6">
            <h1 class="text-xl sm:text-2xl font-black tracking-tight text-neutral-900">Transaksi</h1>
            <p class="mt-1 text-xs text-neutral-500">
                Pantau kesepakatan menunggu COD dan riwayat transaksi Anda.
            </p>
        </div>

        {{-- Flash Message --}}
        @if (session('success'))
            <div
                class="mb-5 flex items-center justify-between rounded-xl border border-lime-300 bg-lime-50 p-3.5 text-xs font-semibold text-lime-900">
                <div class="flex items-center gap-2">
                    <svg viewBox="0 0 24 24" class="size-4 fill-current text-lime-700">
                        <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z" />
                    </svg>
                    <span>{{ session('success') }}</span>
                </div>
                <button type="button" onclick="this.parentElement.remove()"
                    class="text-lime-700 hover:text-lime-900 cursor-pointer">✕</button>
            </div>
        @endif

        <div class="flex flex-col gap-4">
            {{-- Tingkat 1: Tab Peran (Pembelian vs Penjualan) --}}
            <div class="border-b border-neutral-200">
                <div class="flex items-center gap-6">
                    <a href="{{ route('orders.index', ['role' => 'pembelian', 'view' => $view]) }}"
                        class="inline-flex items-center gap-2 pb-2.5 text-xs font-bold transition {{ $role === 'pembelian' ? 'border-b-2 border-neutral-900 text-neutral-900' : 'text-neutral-500 hover:text-neutral-800' }}">
                        <span>Pembelian</span>
                        @if ($activeBuyingCount > 0)
                            <span class="rounded-full bg-lime-400 px-1.5 py-0.5 text-[10px] font-extrabold text-neutral-900">
                                {{ $activeBuyingCount }}
                            </span>
                        @endif
                    </a>

                    <a href="{{ route('orders.index', ['role' => 'penjualan', 'view' => $view]) }}"
                        class="inline-flex items-center gap-2 pb-2.5 text-xs font-bold transition {{ $role === 'penjualan' ? 'border-b-2 border-neutral-900 text-neutral-900' : 'text-neutral-500 hover:text-neutral-800' }}">
                        <span>Penjualan</span>
                        @if ($activeSellingCount > 0)
                            <span class="rounded-full bg-lime-400 px-1.5 py-0.5 text-[10px] font-extrabold text-neutral-900">
                                {{ $activeSellingCount }}
                            </span>
                        @endif
                    </a>
                </div>
            </div>

            {{-- Tingkat 2: Tab Aktif vs Riwayat --}}
            <div class="flex items-center gap-2">
                @foreach ($viewOptions as $viewKey => $viewLabel)
                    <a href="{{ route('orders.index', ['role' => $role, 'view' => $viewKey]) }}"
                        class="shrink-0 rounded-full border px-4 py-1.5 text-xs font-semibold transition {{ $view === $viewKey ? 'border-neutral-900 bg-neutral-900 text-white' : 'border-neutral-300 bg-white text-neutral-700 hover:bg-neutral-100' }}">
                        {{ $viewLabel }}
                    </a>
                @endforeach
            </div>

            {{-- Filter status hanya untuk Riwayat --}}
            @if ($view === 'riwayat')
                <div class="flex items-center gap-2 overflow-x-auto pb-1">
                    @foreach ($statusOptions as $statusKey => $statusLabel)
                        <a href="{{ route('orders.index', ['role' => $role, 'view' => 'riwayat', 'status' => $statusKey]) }}"
                            class="shrink-0 rounded-full border px-3.5 py-1.5 text-xs font-semibold transition {{ $status === $statusKey ? 'border-neutral-900 bg-neutral-900 text-white' : 'border-neutral-300 bg-white text-neutral-700 hover:bg-neutral-100' }}">
                            {{ $statusLabel }}
                        </a>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Daftar Kartu Transaksi --}}
        <div class="mt-5 space-y-3.5">
            @forelse ($orders as $order)
                <x-order-card :order="$order" :role="$role" />
            @empty
                <div
                    class="my-8 flex flex-col items-center justify-center rounded-2xl border border-dashed border-neutral-300 bg-neutral-50 px-6 py-14 text-center">
                    <div class="grid size-14 place-items-center rounded-full bg-neutral-200 text-neutral-500">
                        <svg viewBox="0 0 24 24" class="size-7 fill-none stroke-current stroke-1.75" aria-hidden="true">
                            <path d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2" />
                            <rect x="9" y="3" width="6" height="4" rx="1" />
                            <path d="M9 12h6M9 16h4" />
                        </svg>
                    </div>
                    @if ($view === 'aktif')
                        <h2 class="mt-4 text-sm font-bold text-neutral-900">Belum Ada Transaksi Aktif</h2>
                        <p class="mt-1.5 max-w-sm text-xs leading-relaxed text-neutral-500">
                            @if ($role === 'penjualan')
                                Kesepakatan yang menunggu COD akan muncul di sini saat pembeli menyepakati tawaran.
                            @else
                                Kesepakatan yang tawarannya sudah disetujui penjual akan muncul di sini. Lanjutkan
                                transaksinya lewat chat.
                            @endif
                        </p>
                    @else
                        <h2 class="mt-4 text-sm font-bold text-neutral-900">Belum Ada Riwayat Transaksi</h2>
                        <p class="mt-1.5 max-w-sm text-xs leading-relaxed text-neutral-500">
                            @if ($status !== 'semua')
                                Tidak ada transaksi dengan status "{{ $statusOptions[$status] ?? $status }}".
                            @else
                                Transaksi yang sudah diselesaikan atau dibatalkan akan tercatat di sini.
                            @endif
                        </p>
                    @endif
                </div>
            @endforelse
        </div>

        {{-- Pagination --}}
        @if ($orders->hasPages())
            <div class="mt-6">
                {{ $orders->links() }}
            </div>
        @endif
    </div>

    {{-- Modal Konfirmasi Pembatalan --}}
    <div id="order-confirm-modal" role="dialog" aria-modal="true" aria-labelledby="order-confirm-title"
        class="fixed inset-0 z-50 hidden items-center justify-center p-4">

        <div data-modal-backdrop class="fixed inset-0 bg-neutral-900/50 backdrop-blur-xs transition-opacity"></div>

        <div class="relative z-10 w-full max-w-md rounded-2xl border border-neutral-200 bg-white p-5 sm:p-6 shadow-xl">
            <div class="flex items-start gap-3.5">
                <div id="order-confirm-icon-wrap"
                    class="grid size-10 shrink-0 place-items-center rounded-xl bg-lime-100 text-lime-700">
                    <svg id="order-confirm-icon-primary" viewBox="0 0 24 24"
                        class="size-5 fill-none stroke-current stroke-2">
                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14" />
                        <polyline points="22 4 12 14.01 9 11.01" />
                    </svg>
                    <svg id="order-confirm-icon-danger" viewBox="0 0 24 24"
                        class="hidden size-5 fill-none stroke-current stroke-2">
                        <circle cx="12" cy="12" r="10" />
                        <line x1="12" y1="8" x2="12" y2="12" />
                        <line x1="12" y1="16" x2="12.01" y2="16" />
                    </svg>
                </div>

                <div class="min-w-0 flex-1">
                    <h3 id="order-confirm-title" class="text-base font-extrabold text-neutral-900">
                        Konfirmasi Transaksi
                    </h3>
                    <p id="order-confirm-message" class="mt-1.5 text-xs leading-relaxed text-neutral-600"></p>
                </div>
            </div>

            <div class="mt-6 flex items-center justify-end gap-2.5">
                <button type="button" data-modal-cancel
                    class="rounded-xl border border-neutral-300 bg-white px-4 py-2 text-xs font-semibold text-neutral-700 transition hover:bg-neutral-100 cursor-pointer">
                    Kembali
                </button>
                <button type="button" id="order-confirm-submit"
                    class="rounded-xl bg-lime-400 px-4 py-2 text-xs font-bold text-neutral-900 shadow-2xs transition hover:bg-lime-500 cursor-pointer">
                    Konfirmasi
                </button>
            </div>
        </div>
    </div>

    {{-- Modal Selesaikan Transaksi (dipakai tombol pada kartu order penjual) --}}
    <x-order-complete-modal />
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // 1. Toggle form ulasan & rating bintang
            document.querySelectorAll('[data-order-card]').forEach(function (card) {
                const toggleBtn = card.querySelector('[data-order-review-toggle]');
                const cancelBtn = card.querySelector('[data-order-review-cancel]');
                const form = card.querySelector('[data-order-review-form]');

                if (toggleBtn && form) {
                    toggleBtn.addEventListener('click', function () {
                        form.hidden = !form.hidden;
                    });
                }

                if (cancelBtn && form) {
                    cancelBtn.addEventListener('click', function () {
                        form.hidden = true;
                    });
                }

                const ratingWrap = card.querySelector('[data-rating-input]');
                if (ratingWrap) {
                    const input = ratingWrap.querySelector('input[name="rating"]');
                    const stars = ratingWrap.querySelectorAll('[data-rating-star]');

                    const paintStars = (val) => {
                        stars.forEach((btn) => {
                            const starVal = Number(btn.dataset.ratingStar);
                            btn.classList.toggle('text-amber-400', starVal <= val);
                            btn.classList.toggle('text-neutral-300', starVal > val);
                        });
                    };

                    stars.forEach((btn) => {
                        btn.addEventListener('click', function () {
                            const val = Number(btn.dataset.ratingStar);
                            input.value = String(val);
                            paintStars(val);
                        });
                    });
                }
            });

            // 2. Modal Konfirmasi Pembatalan
            const modal = document.getElementById('order-confirm-modal');
            if (!modal) return;

            const titleEl = document.getElementById('order-confirm-title');
            const messageEl = document.getElementById('order-confirm-message');
            const submitBtn = document.getElementById('order-confirm-submit');
            const iconWrap = document.getElementById('order-confirm-icon-wrap');
            const iconPrimary = document.getElementById('order-confirm-icon-primary');
            const iconDanger = document.getElementById('order-confirm-icon-danger');
            const cancelBtns = modal.querySelectorAll('[data-modal-cancel], [data-modal-backdrop]');

            let activeForm = null;

            const openModal = (trigger) => {
                activeForm = trigger.closest('form');
                if (!activeForm) return;

                const variant = trigger.dataset.confirmVariant || 'danger';
                const isDanger = variant === 'danger';

                titleEl.textContent = trigger.dataset.confirmTitle || 'Konfirmasi';
                messageEl.textContent = trigger.dataset.confirmMessage || '';
                submitBtn.textContent = trigger.dataset.confirmBtn || 'Ya, Lanjutkan';

                iconWrap.className = isDanger
                    ? 'grid size-10 shrink-0 place-items-center rounded-xl bg-red-100 text-red-600'
                    : 'grid size-10 shrink-0 place-items-center rounded-xl bg-lime-100 text-lime-700';

                iconPrimary.classList.toggle('hidden', isDanger);
                iconDanger.classList.toggle('hidden', !isDanger);

                submitBtn.className = isDanger
                    ? 'rounded-xl bg-red-600 px-4 py-2 text-xs font-bold text-white shadow-2xs transition hover:bg-red-700 cursor-pointer'
                    : 'rounded-xl bg-lime-400 px-4 py-2 text-xs font-bold text-neutral-900 shadow-2xs transition hover:bg-lime-500 cursor-pointer';

                modal.classList.remove('hidden');
                modal.classList.add('flex');
                document.body.classList.add('overflow-hidden');
                submitBtn.focus();
            };

            const closeModal = () => {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
                document.body.classList.remove('overflow-hidden');
                activeForm = null;
            };

            document.querySelectorAll('[data-confirm-trigger]').forEach((btn) => {
                btn.addEventListener('click', () => openModal(btn));
            });

            cancelBtns.forEach((el) => {
                el.addEventListener('click', closeModal);
            });

            submitBtn.addEventListener('click', () => {
                if (!activeForm) return;
                submitBtn.disabled = true;
                submitBtn.textContent = 'Memproses...';
                activeForm.submit();
            });

            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape' && !modal.classList.contains('hidden')) {
                    closeModal();
                }
            });
        });
    </script>
@endpush