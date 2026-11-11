@extends('layouts.app')

@section('title', 'Kelola Kategori - Admin RePake')

@section('content')
<div class="mx-auto max-w-6xl px-4 py-6 sm:px-6">
    {{-- Header --}}
    <div class="mb-6">
        <h1 class="text-h2 md:text-h1 font-bold tracking-tight text-neutral-900">Kelola Kategori</h1>
        <p class="mt-1 text-label text-neutral-500">Tambah, ubah, atau hapus kategori produk.</p>
    </div>

    {{-- Form Tambah --}}
    <form method="POST" action="{{ route('admin.categories.store') }}"
          class="mb-6 flex flex-col gap-3 rounded-2xl border border-neutral-200 bg-neutral-50 p-4 sm:flex-row sm:items-end">
        @csrf
        <div class="flex-1">
            <label for="new-name" class="mb-1 block text-label font-semibold text-neutral-800">Nama Kategori</label>
            <input id="new-name" name="name" type="text" required maxlength="100"
                   class="w-full rounded-md border border-neutral-300 bg-white px-3 py-2 text-body text-neutral-900 focus:border-lime-500 focus:outline-none focus:ring-1 focus:ring-lime-500"
                   placeholder="cth. Perangkat Audio">
        </div>
        <div class="w-full sm:w-72">
            <label for="new-icon" class="mb-1 block text-label font-semibold text-neutral-800">Ikon (opsional)</label>
            <div class="flex items-center gap-2">
                <img src="" data-icon-preview class="hidden size-6 shrink-0 rounded object-contain" alt="">
                <input id="new-icon" name="icon" type="text" maxlength="100" data-icon-input
                       class="w-full rounded-md border border-neutral-300 bg-white px-3 py-2 text-body text-neutral-900 focus:border-lime-500 focus:outline-none focus:ring-1 focus:ring-lime-500"
                       placeholder="Path ikon, mis. /images/categories/elektronik.svg">
            </div>
        </div>
        <button type="submit"
                class="inline-flex items-center justify-center rounded-md bg-lime-500 px-5 py-2.5 text-label font-semibold text-neutral-900 transition hover:bg-lime-600">
            Tambah
        </button>
    </form>

    {{-- Tabel Kategori --}}
    @if ($categories->isEmpty())
        <div class="my-12 rounded-2xl border border-dashed border-neutral-300 bg-neutral-50 px-6 py-16 text-center">
            <h2 class="text-h4 font-bold text-neutral-800">Belum Ada Kategori</h2>
            <p class="mt-2 text-body text-neutral-500">Tambahkan kategori pertama lewat form di atas.</p>
        </div>
    @else
        <div class="overflow-hidden rounded-2xl border border-neutral-200 bg-white">
            <table class="w-full text-left">
                <thead class="border-b border-neutral-200 bg-neutral-50 text-label font-semibold uppercase tracking-wide text-neutral-500">
                    <tr>
                        <th class="px-4 py-3">Kategori</th>
                        <th class="px-4 py-3">Ikon</th>
                        <th class="px-4 py-3">Jumlah Produk</th>
                        <th class="px-4 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-neutral-200">
                    @foreach ($categories as $category)
                        <tr class="text-body text-neutral-900">
                            <td class="px-4 py-3">
                                <form method="POST" action="{{ route('admin.categories.update', $category) }}"
                                      class="flex items-center gap-2" data-category-row>
                                    @csrf
                                    @method('PUT')
                                    <input type="text" name="name" value="{{ $category->name }}" required maxlength="100"
                                           aria-label="Nama kategori {{ $category->name }}"
                                           class="w-full min-w-40 max-w-64 rounded-md border border-neutral-300 px-3 py-1.5 text-body focus:border-lime-500 focus:outline-none focus:ring-1 focus:ring-lime-500">
                            </td>
                            <td class="px-4 py-3">
                                    <div class="flex items-center gap-2">
                                        <img src="{{ $category->icon }}" data-icon-preview
                                             class="size-6 shrink-0 rounded object-contain" alt=""
                                             onerror="this.remove()">
                                        <input type="text" name="icon" value="{{ $category->icon }}" maxlength="100" data-icon-input
                                               aria-label="Ikon kategori {{ $category->name }}"
                                               class="w-64 rounded-md border border-neutral-300 px-2 py-1.5 text-body focus:border-lime-500 focus:outline-none focus:ring-1 focus:ring-lime-500">
                                    </div>
                            </td>
                            <td class="px-4 py-3 text-neutral-500">{{ $category->products_count }}</td>
                            <td class="px-4 py-3">
                                    <button type="submit"
                                            class="rounded-md bg-neutral-900 px-3 py-1.5 text-label font-semibold text-white transition hover:bg-neutral-700">
                                        Simpan
                                    </button>
                                </form>
                                <form method="POST" action="{{ route('admin.categories.destroy', $category) }}"
                                      class="mt-2 inline-block" data-category-delete>
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                            class="rounded-md border border-red-200 bg-red-50 px-3 py-1.5 text-label font-semibold text-red-600 transition hover:bg-red-100">
                                        Hapus
                                    </button>
                                </form>
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
    document.querySelectorAll('[data-category-delete]').forEach(function (form) {
        form.addEventListener('submit', function (event) {
            if (! confirm('Hapus kategori ini? Produk terkait akan kehilangan kategori.')) {
                event.preventDefault();
            }
        });
    });

    // Preview ikon (input baru & per baris)
    document.querySelectorAll('[data-icon-input]').forEach(function (input) {
        const preview = input.parentElement.querySelector('[data-icon-preview]');
        const update = function () {
            const url = input.value.trim();
            if (url && preview) {
                preview.src = url;
                preview.classList.remove('hidden');
            } else if (preview) {
                preview.src = '';
                preview.classList.add('hidden');
            }
        };
        input.addEventListener('input', update);
        update();
    });
</script>
@endpush
@endsection
