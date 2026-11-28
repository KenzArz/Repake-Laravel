{{--
    Banner + panel demo penilaian [Fase 1].
    Hanya dirender saat DEMO_MODE=true. Dipakai juri untuk berpindah akun
    persona (pembeli/penjual/admin) tanpa akun kampus.
--}}
@if (config('demo.enabled'))
    @php
        $personaAktif = auth()->user()?->email;
        $adalahAdmin = $personaAktif !== null && $personaAktif === config('demo.personas.admin');
        $uriSekarang = request()->getRequestUri();

        $personaDemo = [
            'pembeli' => ['nama' => config('demo.names.pembeli'), 'keterangan' => 'Pembeli — cari & tawar barang'],
            'penjual' => ['nama' => config('demo.names.penjual'), 'keterangan' => 'Penjual — kelola produk & tawaran'],
            'admin' => ['nama' => config('demo.names.admin'), 'keterangan' => 'Admin — moderasi laporan'],
        ];
    @endphp

    {{-- `open-persona-panel` dipakai modal login demo agar membuka panel yang sama. --}}
    <div x-data="{ buka: false }"
        x-on:open-persona-panel.window="buka = true"
        x-on:keydown.escape.window="buka = false"
        class="relative z-50 shrink-0 print:hidden">
        {{-- Banner tipis di paling atas: penanda instance demo. --}}
        <div class="flex items-center justify-center gap-2 bg-lime-300 px-3 py-1.5 text-neutral-900 sm:px-4">
            <p class="flex min-w-0 items-center gap-2 text-[10px] font-bold uppercase tracking-wide sm:text-[11px]">
                <span class="relative inline-flex size-2 shrink-0" aria-hidden="true">
                    <span class="absolute inline-flex size-full animate-ping rounded-full bg-red-500 opacity-75"></span>
                    <span class="relative inline-flex size-2 rounded-full bg-red-600"></span>
                </span>
                <span class="truncate">Tampilan mode demo</span>
            </p>

            <button type="button" x-on:click="buka = true" x-bind:aria-expanded="buka ? 'true' : 'false'"
                aria-controls="panel-demo" aria-haspopup="dialog"
                class="flex shrink-0 items-center gap-1 rounded-full bg-neutral-900/10 px-2.5 py-1 text-[10px] font-bold uppercase tracking-wide text-neutral-900 transition hover:bg-neutral-900/20">
                <span>Ganti Akun Persona</span>
                <svg viewBox="0 0 24 24" class="size-3 fill-none stroke-current stroke-2.5 [stroke-linecap:round] [stroke-linejoin:round]"
                    aria-hidden="true">
                    <polyline points="6 9 12 15 18 9" />
                </svg>
            </button>
        </div>

        {{-- Panel persona dipindah ke <body> agar benar-benar di tengah layar
             dan tidak terpotong overflow layout. --}}
        <template x-teleport="body">
            <div id="panel-demo" x-cloak x-show="buka" x-transition.opacity.duration.150ms
                x-on:click.self="buka = false"
                class="fixed inset-0 z-[70] flex items-center justify-center bg-neutral-900/60 p-4 backdrop-blur-xs"
                role="dialog" aria-modal="true" aria-labelledby="panel-demo-judul">
                <div x-show="buka" x-transition.scale.95.duration.150ms
                    class="max-h-[calc(100dvh-2rem)] w-full max-w-md overflow-y-auto rounded-2xl border border-neutral-200 bg-white p-5 shadow-2xl">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-[11px] font-bold uppercase tracking-wide text-neutral-500">Mode Demo</p>
                            <h2 id="panel-demo-judul" class="text-base font-extrabold text-neutral-900">Ganti Akun Persona</h2>
                        </div>
                        <button type="button" x-on:click="buka = false"
                            class="grid size-8 shrink-0 place-items-center rounded-full text-neutral-400 transition hover:bg-neutral-100 hover:text-neutral-800"
                            aria-label="Tutup panel persona">
                            <svg viewBox="0 0 24 24" class="size-4 fill-none stroke-current stroke-2 [stroke-linecap:round]"
                                aria-hidden="true">
                                <line x1="18" y1="6" x2="6" y2="18" />
                                <line x1="6" y1="6" x2="18" y2="18" />
                            </svg>
                        </button>
                    </div>

                    <div class="mt-4 space-y-1.5">
                        @foreach ($personaDemo as $persona => $meta)
                            @php
                                // Tujuan divalidasi per persona di DemoLoginController,
                                // jadi panel cukup mengirim halaman yang sedang dibuka.
                                $tujuan = $uriSekarang;
                                $aktif = $personaAktif === config("demo.personas.{$persona}");
                            @endphp

                            <a href="{{ route('demo.login', ['persona' => $persona, 'to' => $tujuan]) }}"
                                class="flex items-center justify-between gap-2 rounded-xl border px-3 py-2.5 transition
                                    {{ $aktif ? 'border-neutral-900 bg-neutral-900 text-white' : 'border-neutral-200 bg-white text-neutral-800 hover:border-neutral-400 hover:bg-neutral-50' }}">
                                <span class="min-w-0">
                                    <span class="block truncate text-sm font-bold">{{ $meta['nama'] }}</span>
                                    <span class="block truncate text-[10px] {{ $aktif ? 'text-neutral-300' : 'text-neutral-500' }}">
                                        {{ $meta['keterangan'] }}
                                    </span>
                                </span>
                                @if ($aktif)
                                    <span class="shrink-0 rounded-full bg-lime-300 px-2 py-0.5 text-[9px] font-bold text-neutral-900">
                                        Aktif
                                    </span>
                                @else
                                    <svg viewBox="0 0 24 24"
                                        class="size-4 shrink-0 fill-none stroke-current stroke-2 [stroke-linecap:round] [stroke-linejoin:round]"
                                        aria-hidden="true">
                                        <polyline points="9 18 15 12 9 6" />
                                    </svg>
                                @endif
                            </a>
                        @endforeach
                    </div>

                    {{-- Endpoint reset hanya menerima persona admin, jadi tombolnya
                         pun hanya ditampilkan untuk admin agar juri tidak kena 403. --}}
                    @if ($adalahAdmin)
                        <form method="POST" action="{{ route('demo.reset') }}" class="mt-4 border-t border-neutral-200 pt-4"
                            onsubmit="return confirm('Reset seluruh data demo ke kondisi awal? Tindakan ini menghapus data yang dibuat selama mencoba.');">
                            @csrf
                            <button type="submit"
                                class="flex w-full items-center justify-center gap-2 rounded-xl border border-red-200 bg-red-50 px-3 py-2 text-xs font-bold text-red-700 transition hover:border-red-300 hover:bg-red-100">
                                <svg viewBox="0 0 24 24"
                                    class="size-3.5 fill-none stroke-current stroke-2 [stroke-linecap:round] [stroke-linejoin:round]"
                                    aria-hidden="true">
                                    <path d="M3 12a9 9 0 1 0 3-6.7" />
                                    <polyline points="3 4 3 10 9 10" />
                                </svg>
                                Reset Data Demo
                            </button>
                        </form>
                    @else
                        <p class="mt-4 border-t border-neutral-200 pt-4 text-[10px] font-medium leading-relaxed text-neutral-500">
                            Reset data demo hanya tersedia untuk persona Admin.
                        </p>
                    @endif
                </div>
            </div>
        </template>
    </div>
@endif
