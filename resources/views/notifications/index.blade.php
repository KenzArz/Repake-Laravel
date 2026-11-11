@extends('layouts.app')

@section('title', 'Notifikasi - RePake')

@section('content')
<div class="mx-auto max-w-3xl px-4 py-6 sm:px-6">
    {{-- Header --}}
    <div class="mb-6 flex items-end justify-between gap-3">
        <div>
            <h1 class="text-h2 md:text-h1 font-bold tracking-tight text-neutral-900">Notifikasi</h1>
            <p class="mt-1 text-label text-neutral-500">{{ $unread }} belum dibaca</p>
        </div>
        @if ($unread > 0)
            <form method="POST" action="{{ route('notifications.readAll') }}">
                @csrf
                <button type="submit"
                        class="rounded-md border border-neutral-300 bg-white px-4 py-2 text-label font-semibold text-neutral-800 transition hover:bg-neutral-100">
                    Tandai semua dibaca
                </button>
            </form>
        @endif
    </div>

    @if ($notifications->isEmpty())
        <div class="my-12 rounded-2xl border border-dashed border-neutral-300 bg-neutral-50 px-6 py-16 text-center">
            <h2 class="text-h4 font-bold text-neutral-800">Belum Ada Notifikasi</h2>
            <p class="mt-2 text-body text-neutral-500">Notifikasi chat dan tawaran akan muncul di sini.</p>
        </div>
    @else
        <div class="divide-y divide-neutral-200 overflow-hidden rounded-2xl border border-neutral-200 bg-white">
            @foreach ($notifications as $notification)
                @php $data = $notification->data; @endphp
                <a href="{{ $data['url'] ?? '#' }}"
                   class="block px-4 py-3 transition hover:bg-neutral-50 {{ $notification->read_at === null ? 'bg-lime-50/60' : '' }}">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-body font-semibold text-neutral-900">{{ $data['title'] ?? '' }}</p>
                            <p class="text-label text-neutral-600">{{ $data['body'] ?? '' }}</p>
                        </div>
                        <div class="shrink-0 text-right">
                            @if ($notification->read_at === null)
                                <span class="inline-block size-2 rounded-full bg-lime-500" aria-label="Belum dibaca"></span>
                            @endif
                            <p class="text-xs text-neutral-400">{{ $notification->created_at->format('d M H:i') }}</p>
                        </div>
                    </div>
                </a>
            @endforeach
        </div>
    @endif
</div>
@endsection
