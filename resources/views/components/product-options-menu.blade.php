@props([
    'product',
    'buttonClass' => 'size-7',
    'menuClass' => 'top-8',
])

@php
    $isOwner = \Illuminate\Support\Facades\Auth::check()
        && (int) \Illuminate\Support\Facades\Auth::id() === (int) $product->user_id;

    $detailUrl = Route::has('products.show')
        ? route('products.show', $product)
        : url("/products/{$product->id}");

    $editUrl = Route::has('products.edit')
        ? route('products.edit', $product)
        : url("/products/{$product->id}/edit");

    $deleteUrl = Route::has('products.destroy')
        ? route('products.destroy', $product)
        : url("/products/{$product->id}");

    $reportUrl = Route::has('products.report')
        ? route('products.report', $product->id)
        : url("/products/{$product->id}/report");
@endphp

<div {{ $attributes->merge(['class' => 'relative z-20 shrink-0']) }} data-options-wrapper>
    <button type="button" data-options-toggle aria-expanded="false" aria-label="Opsi produk"
        class="{{ $buttonClass }} grid place-items-center rounded-full text-neutral-600 transition hover:bg-neutral-100 hover:text-neutral-900 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-neutral-900 cursor-pointer">
        <svg viewBox="0 0 24 24" class="size-4.5 fill-current" aria-hidden="true">
            <circle cx="12" cy="5" r="1.6" />
            <circle cx="12" cy="12" r="1.6" />
            <circle cx="12" cy="19" r="1.6" />
        </svg>
    </button>

    <div data-options-menu role="menu"
        class="{{ $menuClass }} absolute right-0 z-30 hidden w-52 origin-top-right rounded-xl border border-neutral-200 bg-white py-1.5 shadow-xl ring-1 ring-black/5">
        @if ($isOwner)
            <a href="{{ $editUrl }}" role="menuitem"
                class="flex w-full items-center gap-3 px-4 py-2.5 text-sm font-medium text-neutral-700 transition hover:bg-neutral-100 hover:text-neutral-900">
                <svg viewBox="0 0 24 24" class="size-4.5 shrink-0 fill-none stroke-current stroke-2">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M16.862 4.487a2.1 2.1 0 1 1 2.97 2.97L7.5 19.79l-4 1 1-4L16.862 4.487Z" />
                </svg>
                <span>Edit Produk</span>
            </a>

            <button type="button" role="menuitem" data-delete-url="{{ $deleteUrl }}"
                data-delete-title="{{ $product->judul }}"
                class="flex w-full items-center gap-3 px-4 py-2.5 text-sm font-medium text-red-600 transition hover:bg-red-50 cursor-pointer">
                <svg viewBox="0 0 24 24" class="size-4.5 shrink-0 fill-none stroke-current stroke-2">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M3 6h18M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2m3 0v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6h14Z" />
                </svg>
                <span>Hapus Produk</span>
            </button>

            <div class="my-1.5 border-t border-neutral-100"></div>
        @else
            <button type="button" role="menuitem" data-report-url="{{ $reportUrl }}"
                data-report-title="{{ $product->judul }}"
                class="flex w-full items-center gap-3 px-4 py-2.5 text-sm font-medium text-red-600 transition hover:bg-red-50 cursor-pointer">
                <svg viewBox="0 0 24 24" class="size-4.5 shrink-0 fill-none stroke-current stroke-2">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1zM4 22v-7" />
                </svg>
                <span>Laporkan</span>
            </button>
        @endif

        <button type="button" role="menuitem" data-share-url="{{ $detailUrl }}"
            class="flex w-full items-center gap-3 px-4 py-2.5 text-sm font-medium text-neutral-700 transition hover:bg-neutral-100 hover:text-neutral-900 cursor-pointer">
            <svg data-share-icon viewBox="0 0 24 24" class="size-4.5 shrink-0 fill-none stroke-current stroke-2">
                <circle cx="18" cy="5" r="3" />
                <circle cx="6" cy="12" r="3" />
                <circle cx="18" cy="19" r="3" />
                <line x1="8.59" y1="13.51" x2="15.42" y2="17.49" />
                <line x1="15.41" y1="6.51" x2="8.59" y2="10.49" />
            </svg>
            <svg data-share-check viewBox="0 0 24 24"
                class="hidden size-4.5 shrink-0 fill-none stroke-emerald-600 stroke-2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M20 6 9 17l-5-5" />
            </svg>
            <span data-share-label>Bagikan Link</span>
        </button>
    </div>
</div>

@once
    {{-- Modal Konfirmasi Hapus Produk (dirender 1x per halaman) --}}
    <div id="global-delete-modal"
        class="fixed inset-0 z-50 hidden items-center justify-center bg-neutral-950/60 p-4 backdrop-blur-xs" role="dialog"
        aria-modal="true" aria-labelledby="delete-modal-title">
        <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl">
            <div class="flex items-start justify-between gap-4">
                <div class="flex items-start gap-3.5">
                    <div
                        class="grid size-11 shrink-0 place-items-center rounded-full bg-red-50 text-red-600 ring-4 ring-red-50/70">
                        <svg viewBox="0 0 24 24" class="size-5.5 fill-none stroke-current stroke-2">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M3 6h18M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2m3 0v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6h14Z" />
                        </svg>
                    </div>
                    <div>
                        <h3 id="delete-modal-title" class="text-base font-bold text-neutral-900 sm:text-lg">
                            Hapus Produk Ini?
                        </h3>
                        <p class="mt-1.5 text-sm leading-relaxed text-neutral-600">
                            Produk yang dihapus akan ditarik dari katalog dan tidak bisa dilihat pembeli lagi.
                        </p>
                    </div>
                </div>

                <button type="button" data-close-delete-modal
                    class="grid size-8 shrink-0 place-items-center rounded-full text-neutral-400 transition hover:bg-neutral-100 hover:text-neutral-700 cursor-pointer"
                    aria-label="Tutup modal">
                    <svg viewBox="0 0 24 24" class="size-4.5 fill-none stroke-current stroke-2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M18 6 6 18M6 6l12 12" />
                    </svg>
                </button>
            </div>

            {{-- Sorotan nama produk yang dipilih --}}
            <div class="mt-4 rounded-xl border border-neutral-200 bg-neutral-50 px-3.5 py-2.5">
                <p class="text-xs font-medium text-neutral-500">Produk yang akan dihapus:</p>
                <p data-delete-product-name class="mt-0.5 line-clamp-2 text-sm font-bold text-neutral-900"></p>
            </div>

            <form data-delete-form data-loading-text="Menghapus..." action="#" method="POST"
                class="mt-6 flex flex-col-reverse gap-2.5 border-t border-neutral-100 pt-4 sm:flex-row sm:justify-end">
                @csrf
                @method('DELETE')

                <button type="button" data-close-delete-modal
                    class="w-full rounded-xl border border-neutral-300 bg-white px-4 py-2.5 text-sm font-semibold text-neutral-700 transition hover:bg-neutral-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-neutral-900 sm:w-auto cursor-pointer">
                    Batal
                </button>
                <button type="submit" data-submit-btn
                    class="w-full rounded-xl bg-red-600 px-5 py-2.5 text-sm font-bold text-white shadow-xs transition hover:bg-red-700 disabled:cursor-not-allowed disabled:opacity-60 sm:w-auto cursor-pointer">
                    Ya, Hapus Produk
                </button>
            </form>
        </div>
    </div>

    {{-- Modal Laporkan Produk (dirender 1x per halaman) --}}
    <div id="global-report-modal"
        class="js-report-modal fixed inset-0 z-50 hidden items-center justify-center bg-neutral-950/60 p-4 backdrop-blur-xs"
        role="dialog" aria-modal="true" aria-labelledby="report-modal-title">
        <div class="max-h-[90vh] w-full max-w-lg overflow-y-auto rounded-2xl bg-white p-6 shadow-2xl">
            <div class="flex items-start justify-between gap-4 border-b border-neutral-200 pb-4">
                <div class="flex items-start gap-3">
                    <div
                        class="grid size-10 shrink-0 place-items-center rounded-full bg-red-50 text-red-600 ring-4 ring-red-50/70">
                        <svg viewBox="0 0 24 24" class="size-5 fill-none stroke-current stroke-2">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1zM4 22v-7" />
                        </svg>
                    </div>
                    <div>
                        <h3 id="report-modal-title" class="text-base font-bold text-neutral-900 sm:text-lg">
                            Laporkan Barang Ini
                        </h3>
                        <p class="mt-0.5 text-sm text-neutral-500">
                            Bantu kami menjaga keamanan transaksi di RePake.
                        </p>
                    </div>
                </div>
                <button type="button" data-close-report-modal
                    class="grid size-8 shrink-0 place-items-center rounded-full text-neutral-400 transition hover:bg-neutral-100 hover:text-neutral-700 cursor-pointer"
                    aria-label="Tutup modal">
                    <svg viewBox="0 0 24 24" class="size-4.5 fill-none stroke-current stroke-2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M18 6 6 18M6 6l12 12" />
                    </svg>
                </button>
            </div>

            {{-- Sorotan nama produk yang dilaporkan --}}
            <div class="mt-4 rounded-xl border border-neutral-200 bg-neutral-50 px-3.5 py-2.5">
                <p class="text-xs font-medium text-neutral-500">Barang yang dilaporkan:</p>
                <p data-report-product-name class="mt-0.5 line-clamp-1 text-sm font-bold text-neutral-900"></p>
            </div>

            <form data-report-form data-loading-text="Mengirim Laporan..." action="#" method="POST"
                enctype="multipart/form-data" class="mt-4 space-y-4">
                @csrf

                <div>
                    <label for="global-alasan" class="mb-1.5 block text-sm font-semibold text-neutral-800">
                        Alasan Pelaporan <span class="text-red-500">*</span>
                    </label>
                    <select id="global-alasan" name="alasan" required
                        class="w-full rounded-xl border border-neutral-300 bg-white px-3.5 py-2.5 text-base text-neutral-900 transition focus:border-neutral-900 focus:outline-none focus:ring-2 focus:ring-neutral-900/10 sm:text-sm">
                        <option value="" disabled selected>Pilih alasan laporan...</option>
                        <option value="penipuan">Indikasi Penipuan / Scam</option>
                        <option value="barang_palsu">Barang Tiruan / Palsu (KW tidak sesuai deskripsi)</option>
                        <option value="konten_tidak_pantas">Konten atau Gambar Tidak Pantas</option>
                        <option value="harga_tidak_wajar">Harga Tidak Wajar / Spam</option>
                        <option value="lainnya">Alasan Lainnya</option>
                    </select>
                </div>

                <div>
                    <label for="global-keterangan" class="mb-1.5 block text-sm font-semibold text-neutral-800">
                        Penjelasan Detail <span class="text-red-500">*</span>
                    </label>
                    <textarea id="global-keterangan" name="keterangan" rows="3" required
                        placeholder="Jelaskan secara spesifik kendala atau pelanggaran pada produk ini..."
                        class="w-full rounded-xl border border-neutral-300 bg-white p-3.5 text-base leading-relaxed text-neutral-900 transition focus:border-neutral-900 focus:outline-none focus:ring-2 focus:ring-neutral-900/10 sm:text-sm"></textarea>
                </div>

                <div>
                    <label for="global-bukti" class="mb-1.5 block text-sm font-semibold text-neutral-800">
                        Unggah Bukti Gambar <span class="font-normal text-neutral-500">(Opsional)</span>
                    </label>
                    <input type="file" id="global-bukti" name="bukti" accept="image/*"
                        class="w-full rounded-xl border border-neutral-300 bg-neutral-50 p-2 text-sm text-neutral-600 file:mr-3 file:rounded-lg file:border-0 file:bg-neutral-800 file:px-3 file:py-1.5 file:text-xs file:font-semibold file:text-white hover:file:bg-neutral-900 cursor-pointer">
                    <p class="mt-1 text-xs text-neutral-500">Format gambar JPG, PNG, atau WEBP untuk membantu tim peninjau.
                    </p>
                </div>

                <div class="mt-6 flex flex-col-reverse gap-2.5 border-t border-neutral-100 pt-4 sm:flex-row sm:justify-end">
                    <button type="button" data-close-report-modal
                        class="w-full rounded-xl border border-neutral-300 bg-white px-4 py-2.5 text-sm font-semibold text-neutral-700 transition hover:bg-neutral-100 sm:w-auto cursor-pointer">
                        Batal
                    </button>
                    <button type="submit" data-submit-btn
                        class="w-full rounded-xl bg-red-600 px-5 py-2.5 text-sm font-bold text-white shadow-xs transition hover:bg-red-700 disabled:cursor-not-allowed disabled:opacity-60 sm:w-auto cursor-pointer">
                        Kirim Laporan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        if (!window.__repakeOptionsInit) {
            window.__repakeOptionsInit = true;

            const closeAllMenus = () => {
                document.querySelectorAll("[data-options-menu]").forEach((m) => m.classList.add("hidden"));
                document.querySelectorAll("[data-options-toggle]").forEach((b) => b.setAttribute("aria-expanded", "false"));
            };

            const openModalById = (id, focusSelector = null) => {
                const modal = document.getElementById(id);
                if (!modal) return null;

                if (modal.parentElement !== document.body) {
                    document.body.appendChild(modal);
                }

                modal.classList.remove("hidden");
                modal.classList.add("flex");
                document.body.classList.add("overflow-hidden");

                if (focusSelector) {
                    requestAnimationFrame(() => modal.querySelector(focusSelector)?.focus());
                }

                return modal;
            };

            const closeModalById = (id) => {
                const modal = document.getElementById(id);
                if (!modal) return;
                modal.classList.add("hidden");
                modal.classList.remove("flex");
                document.body.classList.remove("overflow-hidden");
            };

            document.addEventListener("click", async (e) => {
                // 1. Toggle dropdown titik 3
                const toggleBtn = e.target.closest("[data-options-toggle]");
                if (toggleBtn) {
                    e.preventDefault();
                    e.stopPropagation();
                    const wrapper = toggleBtn.closest("[data-options-wrapper]");
                    const menu = wrapper?.querySelector("[data-options-menu]");
                    const isHidden = menu?.classList.contains("hidden");

                    closeAllMenus();
                    if (menu && isHidden) {
                        menu.classList.remove("hidden");
                        toggleBtn.setAttribute("aria-expanded", "true");
                    }
                    return;
                }

                // 2. Salin tautan produk (Bagikan Link)
                const shareBtn = e.target.closest("[data-share-url]");
                if (shareBtn) {
                    e.preventDefault();
                    e.stopPropagation();
                    const url = shareBtn.dataset.shareUrl || window.location.href;
                    try {
                        await navigator.clipboard?.writeText(url);
                        const label = shareBtn.querySelector("[data-share-label]");
                        const shareIcon = shareBtn.querySelector("[data-share-icon]");
                        const checkIcon = shareBtn.querySelector("[data-share-check]");

                        if (label) {
                            const prev = label.textContent;
                            label.textContent = "Link Tersalin!";
                            label.classList.add("text-emerald-700", "font-semibold");
                            shareIcon?.classList.add("hidden");
                            checkIcon?.classList.remove("hidden");

                            setTimeout(() => {
                                label.textContent = prev;
                                label.classList.remove("text-emerald-700", "font-semibold");
                                shareIcon?.classList.remove("hidden");
                                checkIcon?.classList.add("hidden");
                                closeAllMenus();
                            }, 1000);
                        } else {
                            closeAllMenus();
                        }
                    } catch {
                        closeAllMenus();
                    }
                    return;
                }

                // 3. Buka modal Konfirmasi Hapus (fokus otomatis ke tombol Batal demi keamanan)
                const deleteBtn = e.target.closest("[data-delete-url]");
                if (deleteBtn) {
                    e.preventDefault();
                    e.stopPropagation();
                    closeAllMenus();

                    const modal = openModalById("global-delete-modal", "[data-close-delete-modal]");
                    if (!modal) return;

                    const form = modal.querySelector("[data-delete-form]");
                    const titleEl = modal.querySelector("[data-delete-product-name]");

                    if (form && deleteBtn.dataset.deleteUrl) {
                        form.action = deleteBtn.dataset.deleteUrl;
                    }
                    if (titleEl) {
                        titleEl.textContent = deleteBtn.dataset.deleteTitle || "Produk ini";
                    }
                    return;
                }

                // 4. Tutup modal Hapus
                if (e.target.closest("[data-close-delete-modal]") || e.target.id === "global-delete-modal") {
                    closeModalById("global-delete-modal");
                    return;
                }

                // 5. Buka modal Laporkan
                const reportBtn = e.target.closest("[data-report-url]");
                if (reportBtn) {
                    e.preventDefault();
                    e.stopPropagation();
                    closeAllMenus();

                    const modal = openModalById("global-report-modal", "#global-alasan");
                    if (!modal) return;

                    const form = modal.querySelector("[data-report-form]");
                    const titleEl = modal.querySelector("[data-report-product-name]");

                    if (form && reportBtn.dataset.reportUrl) {
                        form.action = reportBtn.dataset.reportUrl;
                        form.reset();
                    }
                    if (titleEl) {
                        titleEl.textContent = reportBtn.dataset.reportTitle || "Produk ini";
                    }
                    return;
                }

                // 6. Tutup modal Laporkan
                if (e.target.closest("[data-close-report-modal]") || e.target.id === "global-report-modal") {
                    closeModalById("global-report-modal");
                    return;
                }

                // 7. Klik di luar menu menutup dropdown
                if (!e.target.closest("[data-options-wrapper]")) {
                    closeAllMenus();
                }
            });

            // 8. Cegah double-submit & beri feedback loading pada tombol modal
            document.addEventListener("submit", (e) => {
                const form = e.target.closest("[data-delete-form], [data-report-form]");
                if (!form) return;

                const submitBtn = form.querySelector("[data-submit-btn]");
                if (submitBtn) {
                    submitBtn.disabled = true;
                    submitBtn.textContent = form.dataset.loadingText || "Memproses...";
                }
            });

            document.addEventListener("keydown", (e) => {
                if (e.key === "Escape") {
                    closeAllMenus();
                    closeModalById("global-delete-modal");
                    closeModalById("global-report-modal");
                }
            });
        }
    </script>
@endonce