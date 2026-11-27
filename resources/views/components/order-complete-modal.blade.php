@once
    {{-- Modal "Selesaikan Transaksi" — dipakai bersama oleh halaman chat dan
         riwayat transaksi. Form dikirim via JS, jadi cukup satu instance per
         halaman; action & teks diisi dari atribut data tombol pemicu. --}}
    <div id="complete-modal"
        class="fixed inset-0 z-50 hidden items-center justify-center p-4" role="dialog" aria-modal="true"
        aria-labelledby="complete-modal-title">
        <div data-complete-backdrop class="fixed inset-0 bg-neutral-900/50 backdrop-blur-xs"></div>

        <div class="relative z-10 w-full max-w-md rounded-2xl border border-neutral-200 bg-white p-5 sm:p-6 shadow-xl">
            <div class="flex items-start gap-3.5">
                <div class="grid size-10 shrink-0 place-items-center rounded-xl bg-lime-100 text-lime-700">
                    <svg viewBox="0 0 24 24" class="size-5 fill-none stroke-current stroke-2">
                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14" />
                        <polyline points="22 4 12 14.01 9 11.01" />
                    </svg>
                </div>
                <div class="min-w-0 flex-1">
                    <h3 id="complete-modal-title" class="text-base font-extrabold text-neutral-900">
                        Selesaikan Transaksi
                    </h3>
                    <p id="complete-modal-message" class="mt-1.5 text-xs leading-relaxed text-neutral-600"></p>
                </div>
            </div>

            <form id="complete-form" method="POST" action="#">
                @csrf
                @method('PATCH')
                <input type="hidden" name="status" value="{{ \App\Models\Order::STATUS_SELESAI }}">

                <div class="mt-4 space-y-2.5">
                    <label
                        class="flex cursor-pointer items-start gap-2.5 rounded-xl border border-neutral-200 p-3 transition has-checked:border-neutral-900 has-checked:bg-lime-50">
                        <input type="radio" name="completion_type" value="{{ \App\Models\Order::COMPLETION_STOCK_OUT }}"
                            class="mt-0.5 accent-neutral-900" checked>
                        <span>
                            <span class="block text-[13px] font-bold text-neutral-900">Stok habis</span>
                            <span class="block text-[11px] text-neutral-500">Produk ditandai habis, iklan ditutup, dan
                                order lain otomatis dibatalkan.</span>
                        </span>
                    </label>

                    <label
                        class="flex cursor-pointer items-start gap-2.5 rounded-xl border border-neutral-200 p-3 transition has-checked:border-neutral-900 has-checked:bg-lime-50">
                        <input type="radio" name="completion_type"
                            value="{{ \App\Models\Order::COMPLETION_STOCK_AVAILABLE }}" class="mt-0.5 accent-neutral-900">
                        <span>
                            <span class="block text-[13px] font-bold text-neutral-900">Stok masih tersedia</span>
                            <span class="block text-[11px] text-neutral-500">Transaksi selesai, tetapi produk tetap
                                tayang dan bisa ditawar pembeli lain.</span>
                        </span>
                    </label>

                    <label class="flex items-start gap-2.5 rounded-xl bg-neutral-50 p-3">
                        <input type="checkbox" id="complete-confirm" class="mt-0.5 accent-neutral-900">
                        <span class="text-xs text-neutral-600">Saya sudah melakukan COD / serah-terima dan menyerahkan
                            barang kepada pembeli.</span>
                    </label>
                </div>

                <div class="mt-5 flex items-center justify-end gap-2.5 border-t border-neutral-100 pt-3">
                    <button type="button" data-complete-cancel
                        class="rounded-xl border border-neutral-300 bg-white px-4 py-2 text-xs font-semibold text-neutral-700 transition hover:bg-neutral-100 cursor-pointer">
                        Kembali
                    </button>
                    <button type="submit" id="complete-submit" disabled
                        class="rounded-xl bg-lime-400 px-4 py-2 text-xs font-bold text-neutral-900 shadow-2xs transition hover:bg-lime-500 disabled:cursor-not-allowed disabled:opacity-50 cursor-pointer">
                        Selesaikan Transaksi
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        if (!window.__repakeCompleteInit) {
            window.__repakeCompleteInit = true;

            const modal = document.getElementById('complete-modal');
            const form = document.getElementById('complete-form');
            const confirmBox = document.getElementById('complete-confirm');
            const submitBtn = document.getElementById('complete-submit');
            const messageEl = document.getElementById('complete-modal-message');

            const closeComplete = () => {
                modal?.classList.add('hidden');
                modal?.classList.remove('flex');
                document.body.classList.remove('overflow-hidden');
            };

            document.addEventListener('click', (e) => {
                const backdrop = e.target.closest('[data-complete-backdrop]');
                if (backdrop || e.target.closest('[data-complete-cancel]')) {
                    closeComplete();
                    return;
                }

                const trigger = e.target.closest('[data-complete-trigger]');
                if (!trigger) return;

                e.preventDefault();

                form.action = trigger.dataset.completeAction || '#';
                const methodInput = form.querySelector('input[name="_method"]');
                if (methodInput) methodInput.value = trigger.dataset.completeMethod || 'PATCH';

                messageEl.textContent =
                    'Pastikan kamu sudah melakukan COD dan menyerahkan barang "' +
                    (trigger.dataset.completeProduct || 'ini') + '" kepada ' +
                    (trigger.dataset.completeCounter || 'pembeli') + '.';

                confirmBox.checked = false;
                submitBtn.disabled = true;

                modal.classList.remove('hidden');
                modal.classList.add('flex');
                document.body.classList.add('overflow-hidden');
            });

            confirmBox?.addEventListener('change', (e) => {
                submitBtn.disabled = !e.target.checked;
            });

            form?.addEventListener('submit', (e) => {
                if (!confirmBox.checked) {
                    e.preventDefault();
                    confirmBox.focus();
                    return;
                }
                submitBtn.disabled = true;
                submitBtn.textContent = 'Memproses...';
            });
        }
    </script>
@endonce