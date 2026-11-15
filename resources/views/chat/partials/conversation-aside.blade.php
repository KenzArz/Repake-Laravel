{{--
    Daftar percakapan sisi kiri. Dipakai bersama oleh chat.index dan chat.show
    supaya lebar, border, dan padding-nya tidak pernah berbeda antar halaman.

    $isRoom = true  -> di ruang chat, daftar disembunyikan saat bottom nav tampil
                       (di bawah md) dan baru muncul lagi mulai tablet (md).
    $isRoom = false -> di halaman daftar, sidebar adalah konten utama sehingga
                       tetap tampil di semua ukuran layar.
--}}
@php($isRoom = $isRoom ?? false)

<aside
    class="{{ $isRoom ? 'hidden md:flex' : 'flex' }} min-h-0 w-full shrink-0 flex-col overflow-y-auto border-neutral-200 p-3 pb-24 md:w-[360px] md:border-r md:pb-3 lg:w-[400px] xl:w-[440px]">
    @include('chat.partials.sidebar', [
        'conversations' => $conversations,
        'activeId' => $activeId,
        'myId' => $myId,
        'filter' => $filter,
    ])
</aside>
