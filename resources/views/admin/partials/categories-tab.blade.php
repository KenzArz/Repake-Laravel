{{-- Tab Kategori: tambah di atas, edit langsung di tabel (tersimpan ke database). --}}
<div class="space-y-5">
    <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <h2 class="font-heading text-lg font-bold tracking-tight text-neutral-900">Daftar kategori</h2>
            <p class="mt-0.5 text-xs leading-relaxed text-neutral-500">
                Kelompokkan produk agar pembeli lebih mudah menemukan barang yang dicari.
            </p>
        </div>
        <p class="text-xs font-semibold text-neutral-500">{{ $categories->count() }} kategori</p>
    </div>

    @if ($errors->has('icon_file'))
    <div class="rounded-lg border border-danger-tint bg-danger-tint px-3 py-2 text-[11px] font-medium text-danger-shade">
        {{ $errors->first('icon_file') }}
    </div>
    @endif

    {{-- Tambah kategori --}}
    <form method="POST" action="{{ route('admin.categories.store') }}" enctype="multipart/form-data"
        class="rounded-xl border border-neutral-200 bg-white p-4">
        @csrf
        <p class="font-heading text-sm font-bold text-neutral-900">Tambah kategori</p>

        <div class="mt-3 grid items-end gap-3 sm:grid-cols-[minmax(0,1fr)_auto_auto]">
            <div>
                <label for="new-name" class="mb-1 block text-[11px] font-semibold text-neutral-700">Nama kategori</label>
                <input id="new-name" name="name" type="text" required maxlength="100" value="{{ old('name') }}"
                    class="w-full rounded-lg border border-neutral-300 bg-white px-3 py-2 text-sm text-neutral-900 transition placeholder:text-neutral-400 focus:border-lime-500 focus:outline-none focus:ring-2 focus:ring-lime-200"
                    placeholder="cth. Perangkat audio">
                @error('name')
                <p class="mt-1 text-[11px] font-medium text-danger-shade">{{ $message }}</p>
                @enderror
            </div>

            <div data-icon-field>
                <label class="mb-1 block text-[11px] font-semibold text-neutral-700">
                    Ikon <span class="font-normal text-neutral-400">(PNG)</span>
                </label>
                <div class="flex items-center gap-2">
                    <div
                        class="grid size-10 shrink-0 place-items-center rounded-lg border border-neutral-200 bg-neutral-50">
                        <div data-icon-current>
                            @if (old('icon'))
                            <x-category-icon :icon="old('icon')" size="size-5" />
                            @else
                            <svg viewBox="0 0 24 24" class="size-4 fill-none stroke-neutral-400 stroke-2"
                                aria-hidden="true">
                                <rect x="3" y="3" width="18" height="18" rx="2" />
                                <circle cx="9" cy="9" r="1.5" />
                                <path d="m21 15-4.5-4.5L7 20" />
                            </svg>
                            @endif
                        </div>
                        <img data-icon-image alt="" class="hidden size-full rounded-lg object-contain">
                    </div>

                    <label
                        class="inline-flex cursor-pointer items-center gap-1.5 rounded-lg border border-neutral-300 bg-white px-3 py-2 text-[11px] font-semibold text-neutral-700 transition hover:bg-neutral-100 focus-within:ring-2 focus-within:ring-neutral-900 focus-within:ring-offset-2">
                        <svg viewBox="0 0 24 24" class="size-3.5 fill-none stroke-current stroke-2"
                            aria-hidden="true">
                            <path d="M12 16V4m0 0L7 9m5-5 5 5M4 17v2a1 1 0 0 0 1 1h14a1 1 0 0 0 1-1v-2" />
                        </svg>
                        Pilih PNG
                        <input type="file" name="icon_file" accept="image/png" class="sr-only" data-icon-input>
                    </label>
                </div>
            </div>

            <button type="submit"
                class="inline-flex items-center justify-center gap-1.5 rounded-lg bg-lime-400 px-4 py-2.5 text-xs font-bold text-neutral-900 transition hover:bg-lime-500 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-neutral-900 focus-visible:ring-offset-2">
                <svg viewBox="0 0 24 24" class="size-3.5 fill-none stroke-current stroke-2.5" aria-hidden="true">
                    <path d="M12 5v14M5 12h14" />
                </svg>
                Tambah
            </button>
        </div>
    </form>

    @if ($categories->isEmpty())
    <div class="rounded-xl border border-neutral-200 bg-white px-6 py-16 text-center">
        <h2 class="font-heading text-base font-bold text-neutral-900">Belum ada kategori</h2>
        <p class="mx-auto mt-1 max-w-sm text-xs leading-relaxed text-neutral-500">
            Tambahkan kategori pertama lewat form di atas agar produk bisa dikelompokkan.
        </p>
    </div>
    @else
    <div class="overflow-x-auto rounded-xl border border-neutral-200 bg-white">
        <table class="w-full min-w-[40rem] text-left">
            <thead class="border-b border-neutral-200 text-[11px] font-semibold text-neutral-500">
                <tr>
                    <th class="w-12 px-4 py-2.5">No</th>
                    <th class="w-24 px-4 py-2.5">Ikon</th>
                    <th class="px-4 py-2.5">Nama kategori</th>
                    <th class="w-52 px-4 py-2.5 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-neutral-100">
                @foreach ($categories as $category)
                <tr class="align-middle transition hover:bg-lime-50/30">
                    <td class="px-4 py-3 text-xs text-neutral-400">{{ $loop->iteration }}</td>

                    {{-- Ikon + ganti (sejajar horizontal dengan preview) --}}
                    <td class="px-4 py-3">
                        <div class="flex items-center gap-2" data-icon-field>
                            <div
                                class="grid size-9 shrink-0 place-items-center rounded-lg border border-neutral-200 bg-neutral-50">
                                <div data-icon-current>
                                    @if ($category->icon)
                                    <x-category-icon :icon="$category->icon" :alt="$category->name"
                                        size="size-5" />
                                    @else
                                    <span class="text-[10px] font-bold uppercase text-neutral-400">
                                        {{ mb_substr($category->name, 0, 1) }}
                                    </span>
                                    @endif
                                </div>
                                <img data-icon-image alt=""
                                    class="hidden size-full rounded-lg object-contain">
                            </div>

                            <label
                                class="inline-flex cursor-pointer items-center gap-1 rounded-lg border border-neutral-200 px-2 py-1.5 text-[11px] font-semibold text-neutral-600 transition hover:border-neutral-300 hover:bg-neutral-100 hover:text-neutral-900 focus-within:ring-2 focus-within:ring-neutral-900 focus-within:ring-offset-2"
                                aria-label="Ganti ikon {{ $category->name }}">
                                <svg viewBox="0 0 24 24" class="size-3 fill-none stroke-current stroke-2"
                                    aria-hidden="true">
                                    <path d="M3 7a2 2 0 0 1 2-2h2l1.5-2h7L17 5h2a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2Z" />
                                    <circle cx="12" cy="12" r="3" />
                                </svg>
                                Ganti
                                <input type="file" name="icon_file" accept="image/png"
                                    form="category-update-{{ $category->id }}" class="sr-only"
                                    data-icon-input>
                            </label>
                        </div>
                    </td>

                    {{-- Nama yang bisa diedit --}}
                    <td class="px-4 py-3">
                        <form id="category-update-{{ $category->id }}" method="POST"
                            action="{{ route('admin.categories.update', $category) }}"
                            enctype="multipart/form-data">
                            @csrf
                            @method('PUT')
                            <input type="text" name="name" value="{{ $category->name }}" required
                                maxlength="100" aria-label="Nama kategori {{ $category->name }}"
                                class="w-full rounded-lg border border-neutral-200 bg-white px-2.5 py-1.5 text-sm font-semibold text-neutral-900 transition hover:border-neutral-300 focus:border-lime-500 focus:outline-none focus:ring-2 focus:ring-lime-200">
                        </form>
                    </td>

                    <td class="px-4 py-3">
                        <div class="flex items-center justify-end gap-1.5">
                            <button type="submit" form="category-update-{{ $category->id }}"
                                class="inline-flex items-center gap-1 rounded-lg bg-neutral-900 px-2.5 py-1.5 text-[11px] font-bold text-white transition hover:bg-neutral-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-neutral-900 focus-visible:ring-offset-2">
                                <svg viewBox="0 0 24 24" class="size-3 fill-none stroke-current stroke-2.5"
                                    aria-hidden="true">
                                    <path d="M20 6 9 17l-5-5" />
                                </svg>
                                Simpan
                            </button>
                            <form method="POST" action="{{ route('admin.categories.destroy', $category) }}"
                                data-category-delete>
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                    class="inline-flex items-center gap-1 rounded-lg border border-transparent px-2.5 py-1.5 text-[11px] font-bold text-danger transition hover:border-danger-tint hover:bg-danger-tint hover:text-danger-shade focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-danger focus-visible:ring-offset-2">
                                    <svg viewBox="0 0 24 24" class="size-3 fill-none stroke-current stroke-2"
                                        aria-hidden="true">
                                        <path
                                            d="M3 6h18M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2m3 0v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6" />
                                    </svg>
                                    Hapus
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('[data-category-delete]').forEach(function(form) {
            form.addEventListener('submit', function(event) {
                if (!confirm('Hapus kategori ini? Produk terkait akan kehilangan kategori.')) {
                    event.preventDefault();
                }
            });
        });

        document.querySelectorAll('[data-icon-input]').forEach(function(input) {
            input.addEventListener('change', function() {
                const field = input.closest('[data-icon-field]');
                const file = input.files && input.files[0];

                if (!field || !file) return;

                const image = field.querySelector('[data-icon-image]');
                const current = field.querySelector('[data-icon-current]');

                if (!image) return;

                if (image.dataset.objectUrl) {
                    URL.revokeObjectURL(image.dataset.objectUrl);
                }

                image.src = URL.createObjectURL(file);
                image.dataset.objectUrl = image.src;
                image.classList.remove('hidden');
                current?.classList.add('hidden');
            });
        });
    });
</script>
@endpush