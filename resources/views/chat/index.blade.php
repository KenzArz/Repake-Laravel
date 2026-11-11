@extends('layouts.app')

@section('title', 'Pesan - RePake')

@section('content')
<div class="mx-auto max-w-7xl sm:px-6 lg:px-6 lg:py-4">
    {{-- Kerangka dua panel yang sama dengan chat.show, agar tidak bergeser antar halaman --}}
    <div class="lg:flex lg:h-[calc(100vh-11rem)] lg:min-h-[560px] lg:overflow-hidden lg:rounded-3xl lg:border lg:border-neutral-200">
        {{-- Kiri: daftar percakapan --}}
        <aside class="w-full bg-neutral-50 p-3 sm:p-4 lg:w-96 lg:shrink-0 lg:overflow-y-auto lg:border-r lg:border-neutral-200">
            @include('chat.partials.sidebar', [
                'conversations' => $conversations,
                'activeId' => null,
                'myId' => auth()->id(),
            ])
        </aside>

        {{-- Kanan: kosong sampai user memilih percakapan --}}
        <section class="hidden flex-1 flex-col items-center justify-center gap-2 bg-white px-6 text-center lg:flex">
            <div class="grid size-16 place-items-center rounded-full bg-neutral-100 text-neutral-400">
                <svg viewBox="0 0 24 24" class="size-8 fill-none stroke-current stroke-2 [stroke-linecap:round] [stroke-linejoin:round]" aria-hidden="true">
                    <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2Z"/>
                </svg>
            </div>
            <p class="text-body font-semibold text-neutral-900">Pilih percakapan untuk mulai ngobrol</p>
            <p class="max-w-72 text-label text-neutral-500">
                Chat bisa dimulai dari tombol "Chat Penjual" di halaman detail barang.
            </p>
        </section>
    </div>
</div>
@endsection
