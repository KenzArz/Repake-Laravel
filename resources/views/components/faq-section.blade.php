@props([
    // 'badge' => 'Bantuan & Info',
    'title' => 'Pertanyaan yang Sering Diajukan',
    'subtitle' => 'Masih bingung cara mulai jual beli barang bekas di RePake? Cek jawaban dari pertanyaan yang paling sering ditanyakan pengguna.',
    'defaultOpen' => 0,
    'items' => [
        [
            'q' => 'Apakah pasang iklan jual barang di RePake gratis?',
            'a' => 'Ya, 100% gratis. Kamu bisa mengunggah foto, menentukan harga, dan mempublikasikan barang bekas layak pakai tanpa potongan biaya admin atau komisi.',
        ],
        [
            'q' => 'Bagaimana sistem transaksi dan pembayaran di RePake?',
            'a' => 'Pembeli dan penjual dapat langsung berkomunikasi melalui fitur chat untuk menyepakati harga, metode pembayaran, serta lokasi pertemuan (COD) di sekitar area kamu.',
        ],
        [
            'q' => 'Bagaimana tips aman saat melakukan COD (Cash on Delivery)?',
            'a' => 'Selalu pilih titik temu di tempat umum yang ramai dan mudah dijangkau. Pastikan kamu mengecek fisik serta fungsi barang secara langsung sebelum melakukan pembayaran.',
        ],
        [
            'q' => 'Barang apa saja yang boleh dijual di RePake?',
            'a' => 'Semua barang bekas pakai yang masih layak dan legal diperjualbelikan, seperti elektronik, pakaian, buku, perabot kos, perlengkapan olahraga, hobi, hingga aksesori otomotif.',
        ],
        [
            'q' => 'Bagaimana cara mengubah status barang jika sudah terjual?',
            'a' => 'Buka halaman Profil atau klik ikon titik tiga (⋮) pada kartu produkmu, lalu ubah status iklan menjadi terjual agar tidak lagi dihubungi calon pembeli baru.',
        ],
    ],
])

<section aria-labelledby="faq-heading" x-data="{ open: @js($defaultOpen) }" {{ $attributes->merge(['class' => 'mt-12 mb-8 border-t border-neutral-200/80 pt-10']) }}>
    <div class="grid grid-cols-1 gap-8 lg:grid-cols-12 lg:gap-10">
        {{-- Kolom Kiri: Judul & Pengantar --}}
        <div class="lg:col-span-4">
            {{-- @if ($badge)
                <span
                    class="inline-block rounded-full bg-lime-200/80 px-3 py-1 text-[11px] font-extrabold uppercase tracking-wider text-neutral-900">
                    {{ $badge }}
                </span>
            @endif --}}

            <h2 id="faq-heading" class="mt-3 text-xl font-bold leading-snug text-neutral-900 sm:text-2xl">
                {{ $title }}
            </h2>

            @if ($subtitle)
                <p class="mt-2.5 text-xs leading-relaxed text-neutral-600 sm:text-sm">
                    {{ $subtitle }}
                </p>
            @endif
        </div>

        {{-- Kolom Kanan: Accordion FAQ --}}
        <div class="space-y-3 lg:col-span-8">
            @foreach ($items as $index => $faq)
                <div class="overflow-hidden rounded-xl border bg-white transition-all duration-200" :class="open === {{ $index }}
                            ? 'border-lime-400 shadow-xs ring-1 ring-lime-400/50'
                            : 'border-neutral-200/80 hover:border-neutral-300'">
                    <h3>
                        <button type="button" @click="open = open === {{ $index }} ? null : {{ $index }}"
                            :aria-expanded="open === {{ $index }}"
                            class="flex w-full cursor-pointer items-center justify-between gap-4 px-5 py-4 text-left transition-colors">
                            <span class="text-xs font-bold leading-snug text-neutral-900 sm:text-sm">
                                {{ $faq['q'] }}
                            </span>
                            <span class="grid size-7 shrink-0 place-items-center rounded-full transition-all duration-200"
                                :class="open === {{ $index }}
                                        ? 'rotate-180 bg-lime-400 text-neutral-900'
                                        : 'bg-neutral-100 text-neutral-600'">
                                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"
                                    aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                                </svg>
                            </span>
                        </button>
                    </h3>

                    <div class="grid transition-all duration-300 ease-out"
                        :class="open === {{ $index }} ? 'grid-rows-[1fr] opacity-100' : 'grid-rows-[0fr] opacity-0'">
                        <div class="overflow-hidden">
                            <p
                                class="border-t border-neutral-100 px-5 pt-3 pb-4 text-xs leading-relaxed text-neutral-600 sm:text-sm">
                                {{ $faq['a'] }}
                            </p>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>