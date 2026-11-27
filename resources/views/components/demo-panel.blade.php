{{--
    Banner + panel demo penilaian [Fase 1].
    Hanya dirender saat DEMO_MODE=true. Dipakai juri untuk berpindah sudut
    pandang (pembeli/penjual/admin) tanpa akun kampus.
--}}
@if (config('demo.enabled'))
    @php
        $personaAktif = auth()->user()?->email;
        $uriSekarang = request()->getRequestUri();

        $personaDemo = [
            'pembeli' => ['label' => 'Pembeli', 'keterangan' => 'Cari & tawar barang'],
            'penjual' => ['label' => 'Penjual', 'keterangan' => 'Kelola produk & tawaran'],
            'admin' => ['label' => 'Admin', 'keterangan' => 'Moderasi laporan'],
        ];
    @endphp

    <div x-data="{ buka: false }" class="relative z-50 shrink-0 print:hidden">
        {{-- Banner tipis di paling atas: juri selalu tahu ini data contoh. --}}
        <div
            class="flex items-center justify-center gap-x-3 gap-2 bg-lime-300 px-3 py-1.5 text-neutral-900 sm:px-4">
            <p class="flex min-w-0 items-center gap-2 text-[10px] font-bold uppercase tracking-wide sm:text-[11px]">
                <span class="inline-flex size-1.5 shrink-0 rounded-full bg-neutral-900" aria-hidden="true"></span>
                <span class="truncate sm:hidden">Mode demo — data contoh</span>
                <span class="hidden truncate sm:inline">Mode Demo Penilaian — data contoh, bukan data mahasiswa</span>
            </p>

            <button type="button" x-on:click="buka = ! buka"
                x-bind:aria-expanded="buka ? 'true' : 'false'" aria-controls="panel-demo"
                class="flex shrink-0 items-center gap-1 rounded-full bg-neutral-900/10 px-2.5 py-1 text-[10px] font-bold uppercase tracking-wide text-neutral-900 transition hover:bg-neutral-900/20">
                <span x-text="buka ? 'Tutup' : 'Ganti Peran'">Ganti Peran</span>
                <svg viewBox="0 0 24 24" class="size-3 fill-none stroke-current stroke-2.5 [stroke-linecap:round] [stroke-linejoin:round] transition"
                    x-bind:class="buka && 'rotate-180'">
                    <polyline points="6 9 12 15 18 9" />
                </svg>
            </button>
        </div>

        {{-- Panel persona: absolute di bawah banner, jadi tidak pernah menutupi tombol kirim chat. --}}
        <div id="panel-demo" x-cloak x-show="buka" x-transition.origin.top.right
            class="absolute right-2 top-full z-50 mt-2 w-72 max-w-[calc(100vw-1rem)] rounded-2xl border border-neutral-200 bg-white p-3 shadow-2xl">
            <div class="flex items-start justify-between gap-2 px-1">
                <div>
                    <p class="text-[11px] font-bold uppercase tracking-wide text-neutral-500">Mode Demo</p>
                    <p class="text-sm font-extrabold text-neutral-900">Ganti sudut pandang</p>
                </div>
                <button type="button" x-on:click="buka = false"
                    class="grid size-7 shrink-0 place-items-center rounded-full text-neutral-400 transition hover:bg-neutral-100 hover:text-neutral-800"
                    aria-label="Tutup panel demo">
                    <svg viewBox="0 0 24 24" class="size-4 fill-none stroke-current stroke-2 [stroke-linecap:round]">
                        <line x1="18" y1="6" x2="6" y2="18" />
                        <line x1="6" y1="6" x2="18" y2="18" />
                    </svg>
                </button>
            </div>

            <div class="mt-3 space-y-1.5">
                @foreach ($personaDemo as $persona => $meta)
                    @php
                        // Admin bukan peserta percakapan, jadi selalu mendarat di dashboard.
                        $tujuan = $persona === 'admin'
                            ? route('admin.dashboard', absolute: false)
                            : $uriSekarang;
                        $aktif = $personaAktif === config("demo.personas.{$persona}");
                    @endphp

                    <a href="{{ route('demo.login', ['persona' => $persona, 'to' => $tujuan]) }}"
                        class="flex items-center justify-between gap-2 rounded-xl border px-3 py-2 transition
                            {{ $aktif ? 'border-neutral-900 bg-neutral-900 text-white' : 'border-neutral-200 bg-white text-neutral-800 hover:border-neutral-400 hover:bg-neutral-50' }}">
                        <span>
                            <span class="block text-xs font-bold">{{ $meta['label'] }}</span>
                            <span class="block text-[10px] {{ $aktif ? 'text-neutral-300' : 'text-neutral-500' }}">
                                {{ $meta['keterangan'] }}
                            </span>
                        </span>
                        @if ($aktif)
                            <span class="rounded-full bg-lime-300 px-2 py-0.5 text-[9px] font-bold text-neutral-900">
                                Aktif
                            </span>
                        @else
                            <svg viewBox="0 0 24 24"
                                class="size-4 shrink-0 fill-none stroke-current stroke-2 [stroke-linecap:round] [stroke-linejoin:round]">
                                <polyline points="9 18 15 12 9 6" />
                            </svg>
                        @endif
                    </a>
                @endforeach
            </div>

            <form method="POST" action="{{ route('demo.reset') }}" class="mt-3 border-t border-neutral-200 pt-3"
                onsubmit="return confirm('Reset seluruh data demo ke kondisi awal? Tindakan ini menghapus data yang dibuat selama mencoba.');">
                @csrf
                <button type="submit"
                    class="flex w-full items-center justify-center gap-2 rounded-xl border border-red-200 bg-red-50 px-3 py-2 text-xs font-bold text-red-700 transition hover:border-red-300 hover:bg-red-100">
                    <svg viewBox="0 0 24 24"
                        class="size-3.5 fill-none stroke-current stroke-2 [stroke-linecap:round] [stroke-linejoin:round]">
                        <path d="M3 12a9 9 0 1 0 3-6.7" />
                        <polyline points="3 4 3 10 9 10" />
                    </svg>
                    Reset Data Demo
                </button>
            </form>
        </div>
    </div>
@endif
