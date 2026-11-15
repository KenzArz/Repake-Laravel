@extends('layouts.app')

@section('title', 'Pesan - RePake')

@section('hide-footer', '1')

@section('app-shell', '1')

@section('content')
<div class="mx-auto flex min-h-0 w-full max-w-[1440px] flex-1 overflow-hidden bg-neutral-50 md:pl-8 xl:pl-12">
    {{-- Kiri: daftar percakapan (partial bersama dengan chat.show) --}}
    @include('chat.partials.conversation-aside', [
        'conversations' => $conversations,
        'activeId' => null,
        'myId' => auth()->id(),
        'filter' => $filter ?? 'semua',
        'isRoom' => false,
    ])

    {{-- Kanan: kosong sampai user memilih percakapan --}}
    <section class="hidden flex-1 flex-col items-center justify-center gap-2 px-6 text-center md:flex">
        <div class="grid size-14 place-items-center rounded-full bg-neutral-200/70 text-neutral-500">
            <svg viewBox="0 0 24 24" class="size-7 fill-none stroke-current stroke-2" aria-hidden="true">
                <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2Z" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </div>
        <p class="text-[15px] font-bold text-neutral-900">Pilih percakapan untuk mulai ngobrol</p>
        <p class="max-w-72 text-[13px] text-neutral-500">
            Chat bisa dimulai dari tombol "Chat Penjual" di halaman detail barang.
        </p>
    </section>
</div>
@endsection
