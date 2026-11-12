@php
    $activeCount = $banners->where('is_active', true)->count();
@endphp

<div class="space-y-4">
    <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <h2 class="font-heading text-lg font-bold tracking-tight text-neutral-900">Banner beranda</h2>
            <p class="mt-0.5 max-w-md text-xs leading-relaxed text-neutral-500">
                Tarik pegangan di kiri untuk mengubah urutan tampil. Di ponsel, gunakan tombol panah.
            </p>
        </div>
        <div class="flex items-center gap-3">
            <p data-banner-status class="text-[11px] font-medium text-neutral-500" aria-live="polite"></p>
            <p class="text-xs font-semibold text-neutral-500">
                {{ $activeCount }} dari {{ $banners->count() }} aktif
            </p>
            <button type="button" data-banner-add-open
                class="inline-flex items-center gap-1.5 rounded-lg bg-lime-400 px-4 py-2.5 text-xs font-bold text-neutral-900 transition hover:bg-lime-500 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-neutral-900 focus-visible:ring-offset-2">
                <svg viewBox="0 0 24 24" class="size-3.5 fill-none stroke-current stroke-2.5" aria-hidden="true">
                    <path d="M12 5v14M5 12h14" />
                </svg>
                Tambah banner
            </button>
        </div>
    </div>

    @if ($activeCount <= 3)
        <div
            class="flex gap-2 rounded-lg border border-warning-tint bg-warning-tint px-3 py-2.5 text-[11px] leading-relaxed text-warning-shade">
            <svg viewBox="0 0 24 24" class="mt-0.5 size-3.5 shrink-0 fill-none stroke-current stroke-2"
                aria-hidden="true">
                <path d="M12 9v4M12 17h.01M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0Z" />
            </svg>
            <p>Minimal 3 banner harus tetap aktif. Tambah banner baru sebelum menonaktifkan atau menghapus banner aktif.</p>
        </div>
    @endif

    @if ($banners->isEmpty())
        <div class="rounded-xl border border-neutral-200 bg-white px-6 py-16 text-center">
            <h3 class="font-heading text-base font-bold text-neutral-900">Belum ada banner</h3>
            <p class="mx-auto mt-1 max-w-sm text-xs leading-relaxed text-neutral-500">
                Klik tombol Tambah banner untuk memasang gambar promo pertama di beranda.
            </p>
        </div>
    @else
        <div data-banner-list data-banner-reorder-url="{{ route('admin.banners.reorder') }}"
            class="divide-y divide-neutral-100 overflow-hidden rounded-xl border border-neutral-200 bg-white">
            @foreach ($banners as $banner)
                <div data-banner-row data-banner-id="{{ $banner->id }}"
                    class="flex items-center gap-3 p-3 transition-colors sm:p-3.5">
                    {{-- Pegangan drag (desktop) --}}
                    <button type="button" data-banner-handle draggable="true"
                        aria-label="Geser banner {{ $banner->title }}. Gunakan panah atas dan bawah pada keyboard."
                        class="-ml-1 hidden size-7 shrink-0 cursor-grab touch-none place-items-center rounded-md text-neutral-300 transition hover:bg-neutral-100 hover:text-neutral-600 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-neutral-900 active:cursor-grabbing md:grid">
                        <svg viewBox="0 0 24 24" class="size-4 fill-current" aria-hidden="true">
                            <circle cx="9" cy="6" r="1.5" />
                            <circle cx="15" cy="6" r="1.5" />
                            <circle cx="9" cy="12" r="1.5" />
                            <circle cx="15" cy="12" r="1.5" />
                            <circle cx="9" cy="18" r="1.5" />
                            <circle cx="15" cy="18" r="1.5" />
                        </svg>
                    </button>

                    {{-- Panah urutan (fallback ponsel) --}}
                    <div class="flex shrink-0 flex-col gap-0.5 md:hidden">
                        <form method="POST" action="{{ route('admin.banners.move', $banner) }}">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="direction" value="up">
                            <button type="submit" data-move-up @disabled($loop->first)
                                aria-label="Naikkan banner {{ $banner->title }}"
                                class="grid size-6 place-items-center rounded-md text-neutral-400 transition hover:bg-neutral-100 hover:text-neutral-700 disabled:cursor-not-allowed disabled:opacity-30">
                                <svg viewBox="0 0 24 24" class="size-3.5 fill-none stroke-current stroke-2.5"
                                    aria-hidden="true">
                                    <path d="m18 15-6-6-6 6" />
                                </svg>
                            </button>
                        </form>
                        <form method="POST" action="{{ route('admin.banners.move', $banner) }}">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="direction" value="down">
                            <button type="submit" data-move-down @disabled($loop->last)
                                aria-label="Turunkan banner {{ $banner->title }}"
                                class="grid size-6 place-items-center rounded-md text-neutral-400 transition hover:bg-neutral-100 hover:text-neutral-700 disabled:cursor-not-allowed disabled:opacity-30">
                                <svg viewBox="0 0 24 24" class="size-3.5 fill-none stroke-current stroke-2.5"
                                    aria-hidden="true">
                                    <path d="m6 9 6 6 6-6" />
                                </svg>
                            </button>
                        </form>
                    </div>

                    {{-- Thumbnail --}}
                    <div
                        class="h-12 w-20 shrink-0 overflow-hidden rounded-lg border border-neutral-200 bg-neutral-100 sm:h-16 sm:w-28">
                        <img src="{{ $banner->image_url }}" alt="{{ $banner->alt_text ?: $banner->title }}"
                            class="size-full object-cover" onerror="this.remove()">
                    </div>

                    {{-- Judul & status --}}
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-xs font-bold text-neutral-900 sm:text-sm">{{ $banner->title }}</p>
                        <p
                            class="mt-0.5 flex items-center gap-1.5 text-[11px] font-semibold {{ $banner->is_active ? 'text-success-shade' : 'text-neutral-500' }}">
                            <span
                                class="size-1.5 rounded-full {{ $banner->is_active ? 'bg-success' : 'bg-neutral-400' }}"
                                aria-hidden="true"></span>
                            {{ $banner->is_active ? 'Tampil di beranda' : 'Disembunyikan' }}
                        </p>
                    </div>

                    {{-- Switch aktif/nonaktif --}}
                    <form method="POST" action="{{ route('admin.banners.toggle', $banner) }}" class="shrink-0">
                        @csrf
                        @method('PATCH')
                        <button type="submit" role="switch"
                            aria-checked="{{ $banner->is_active ? 'true' : 'false' }}"
                            aria-label="{{ $banner->is_active ? 'Nonaktifkan' : 'Aktifkan' }} banner {{ $banner->title }}"
                            class="relative inline-flex h-6 w-11 items-center rounded-full transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-neutral-900 focus-visible:ring-offset-2 {{ $banner->is_active ? 'bg-lime-400' : 'bg-neutral-300' }}">
                            <span
                                class="inline-block size-4.5 rounded-full shadow transition {{ $banner->is_active ? 'translate-x-6 bg-neutral-900' : 'translate-x-1 bg-white' }}"></span>
                        </button>
                    </form>

                    {{-- Hapus --}}
                    <form method="POST" action="{{ route('admin.banners.destroy', $banner) }}"
                        onsubmit="return confirm('Hapus banner ini?')" class="shrink-0">
                        @csrf
                        @method('DELETE')
                        <button type="submit" aria-label="Hapus banner {{ $banner->title }}"
                            class="grid size-8 place-items-center rounded-lg text-danger transition hover:bg-danger-tint hover:text-danger-shade focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-danger">
                            <svg viewBox="0 0 24 24" class="size-4 fill-none stroke-current stroke-2"
                                aria-hidden="true">
                                <path
                                    d="M3 6h18M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2m3 0v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6" />
                            </svg>
                        </button>
                    </form>
                </div>
            @endforeach
        </div>
    @endif
</div>

{{-- Modal tambah banner --}}
<div id="banner-add-modal" class="fixed inset-0 z-[60] hidden p-4 sm:p-6" role="dialog" aria-modal="true"
    aria-labelledby="banner-add-title">
    <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" data-banner-add-backdrop></div>
    <div class="relative mx-auto mt-8 w-full max-w-md overflow-hidden rounded-2xl bg-white shadow-2xl">
        <div class="flex items-center justify-between border-b border-neutral-200 px-5 py-4">
            <h2 id="banner-add-title" class="font-heading text-sm font-bold text-neutral-900">Tambah banner</h2>
            <button type="button" data-banner-add-close
                class="grid size-8 place-items-center rounded-full text-neutral-400 transition hover:bg-neutral-100 hover:text-neutral-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-neutral-900"
                aria-label="Tutup">
                <svg viewBox="0 0 24 24" class="size-4 fill-none stroke-current stroke-2" aria-hidden="true">
                    <path d="M18 6 6 18M6 6l12 12" />
                </svg>
            </button>
        </div>

        <form method="POST" action="{{ route('admin.banners.store') }}" enctype="multipart/form-data"
            class="space-y-3.5 p-5">
            @csrf
            <div>
                <label for="banner-title" class="mb-1 block text-[11px] font-semibold text-neutral-700">Judul banner</label>
                <input id="banner-title" name="title" type="text" required maxlength="100"
                    value="{{ old('title') }}" placeholder="cth. Promo akhir semester"
                    class="w-full rounded-lg border border-neutral-300 px-3 py-2 text-sm text-neutral-900 transition placeholder:text-neutral-400 focus:border-lime-500 focus:outline-none focus:ring-2 focus:ring-lime-200">
                @error('title')
                    <p class="mt-1 text-[11px] font-medium text-danger-shade">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="banner-alt" class="mb-1 block text-[11px] font-semibold text-neutral-700">
                    Teks alternatif <span class="font-normal text-neutral-400">(opsional)</span>
                </label>
                <input id="banner-alt" name="alt_text" type="text" maxlength="150" value="{{ old('alt_text') }}"
                    placeholder="Deskripsi singkat untuk aksesibilitas"
                    class="w-full rounded-lg border border-neutral-300 px-3 py-2 text-sm text-neutral-900 transition placeholder:text-neutral-400 focus:border-lime-500 focus:outline-none focus:ring-2 focus:ring-lime-200">
            </div>

            <div>
                <label for="banner-image" class="mb-1 block text-[11px] font-semibold text-neutral-700">Gambar banner</label>
                <input id="banner-image" name="image" type="file" accept="image/jpeg,image/png,image/webp" required
                    class="w-full rounded-lg border border-neutral-300 bg-neutral-50 p-1.5 text-xs text-neutral-600 file:mr-2 file:rounded file:border-0 file:bg-neutral-900 file:px-2.5 file:py-1 file:text-xs file:font-semibold file:text-white hover:file:bg-neutral-800">
                <p class="mt-1 text-[11px] text-neutral-500">Format JPG, PNG, atau WEBP. Maksimal 2 MB.</p>
                @error('image')
                    <p class="mt-1 text-[11px] font-medium text-danger-shade">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex justify-end gap-2 border-t border-neutral-100 pt-3">
                <button type="button" data-banner-add-close
                    class="rounded-lg border border-neutral-300 px-3.5 py-2 text-xs font-semibold text-neutral-700 transition hover:bg-neutral-100">
                    Batal
                </button>
                <button type="submit"
                    class="rounded-lg bg-lime-400 px-4 py-2 text-xs font-bold text-neutral-900 transition hover:bg-lime-500 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-neutral-900 focus-visible:ring-offset-2">
                    Simpan banner
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Modal tambah banner
            const modal = document.getElementById('banner-add-modal');
            if (modal) {
                let lastFocused = null;

                const open = () => {
                    lastFocused = document.activeElement;
                    modal.classList.remove('hidden');
                    document.body.classList.add('overflow-hidden');
                    modal.querySelector('input[name="title"]')?.focus();
                };
                const close = () => {
                    modal.classList.add('hidden');
                    document.body.classList.remove('overflow-hidden');

                    if (lastFocused instanceof HTMLElement) {
                        lastFocused.focus();
                    }
                };

                document.querySelectorAll('[data-banner-add-open]')
                    .forEach((btn) => btn.addEventListener('click', open));
                document.querySelectorAll('[data-banner-add-close], [data-banner-add-backdrop]')
                    .forEach((el) => el.addEventListener('click', close));

                document.addEventListener('keydown', (event) => {
                    if (event.key === 'Escape' && !modal.classList.contains('hidden')) {
                        close();
                    }
                });
            }

            // Drag and drop urutan banner
            const list = document.querySelector('[data-banner-list]');
            if (!list) return;

            const endpoint = list.dataset.bannerReorderUrl;
            const status = document.querySelector('[data-banner-status]');
            const rows = () => Array.from(list.querySelectorAll('[data-banner-row]'));
            let initialIds = rows().map((row) => row.dataset.bannerId);
            let dragging = null;
            let saving = false;

            const setStatus = (text) => {
                if (!status) return;
                status.textContent = text;

                if (text) {
                    clearTimeout(setStatus.timer);
                    setStatus.timer = setTimeout(() => {
                        status.textContent = '';
                    }, 2600);
                }
            };

            const refreshArrows = () => {
                const items = rows();

                items.forEach((row, index) => {
                    const up = row.querySelector('[data-move-up]');
                    const down = row.querySelector('[data-move-down]');

                    if (up) up.disabled = index === 0;
                    if (down) down.disabled = index === items.length - 1;
                });
            };

            const persist = async () => {
                const currentIds = rows().map((row) => row.dataset.bannerId);

                if (currentIds.join(',') === initialIds.join(',')) {
                    return;
                }

                saving = true;
                setStatus('Menyimpan urutan…');

                try {
                    const response = await fetch(endpoint, {
                        method: 'PATCH',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
                        },
                        body: JSON.stringify({ order: currentIds.map(Number) }),
                    });

                    if (!response.ok) {
                        throw new Error('Gagal menyimpan urutan.');
                    }

                    initialIds = currentIds;
                    refreshArrows();
                    setStatus('Urutan tersimpan');
                } catch (error) {
                    // Kembalikan urutan terakhir yang tersimpan agar tampilan tidak menipu.
                    const previous = new Map(rows().map((row) => [row.dataset.bannerId, row]));
                    initialIds.forEach((id) => {
                        const row = previous.get(id);
                        if (row) list.appendChild(row);
                    });

                    refreshArrows();
                    setStatus('Gagal menyimpan urutan. Coba lagi.');
                } finally {
                    saving = false;
                }
            };

            const rowFromEvent = (event) => event.target.closest('[data-banner-row]');

            list.querySelectorAll('[data-banner-handle]').forEach((handle) => {
                handle.addEventListener('dragstart', (event) => {
                    const row = rowFromEvent(event);
                    if (!row) return;

                    dragging = row;
                    event.dataTransfer.effectAllowed = 'move';
                    event.dataTransfer.setData('text/plain', row.dataset.bannerId);

                    requestAnimationFrame(() => {
                        row.style.opacity = '0.4';
                    });
                });

                handle.addEventListener('dragend', () => {
                    if (!dragging) return;

                    dragging.style.opacity = '';
                    dragging = null;
                    persist();
                });

                // Keyboard: panah atas/bawah pada pegangan.
                handle.addEventListener('keydown', (event) => {
                    if (event.key !== 'ArrowUp' && event.key !== 'ArrowDown') return;

                    const row = rowFromEvent(event);
                    if (!row) return;

                    event.preventDefault();

                    if (event.key === 'ArrowUp' && row.previousElementSibling) {
                        list.insertBefore(row, row.previousElementSibling);
                    } else if (event.key === 'ArrowDown' && row.nextElementSibling) {
                        list.insertBefore(row.nextElementSibling, row);
                    } else {
                        return;
                    }

                    handle.focus();
                    refreshArrows();
                    persist();
                });
            });

            list.addEventListener('dragover', (event) => {
                if (!dragging) return;
                event.preventDefault();
                event.dataTransfer.dropEffect = 'move';

                const target = rowFromEvent(event);
                if (!target || target === dragging) return;

                const rect = target.getBoundingClientRect();
                const after = event.clientY > rect.top + rect.height / 2;

                if (after) {
                    if (dragging.previousElementSibling !== target) {
                        target.after(dragging);
                    }
                } else if (dragging.nextElementSibling !== target) {
                    target.before(dragging);
                }
            });

            list.addEventListener('drop', (event) => {
                if (!dragging) return;
                event.preventDefault();
            });

            // Form panah tetap berfungsi; segarkan status disabled setelah urutan berubah.
            refreshArrows();
        });
    </script>
@endpush
