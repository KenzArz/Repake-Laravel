@props([
    'action' => route('products.index'),
    'target' => 'search-results',
    'categories' => collect(),
    'kondisiOptions' => ['Baru', 'Seperti baru', 'Dirawat dengan baik', 'Layak pakai'],
    'activeKota' => '',
    'sortOptions' => [
        'terbaru' => 'Terbaru',
        'terdekat' => 'Terdekat',
        'termurah' => 'Termurah',
        'termahal' => 'Termahal',
    ],
    'defaultSort' => 'terbaru',
])

<div x-cloak x-data="productFilter({
        action: @js($action),
        target: @js($target),
        defaultSort: @js($defaultSort),
        sort: @js(request('sort', $defaultSort)),
        sortLabels: @js($sortOptions),
        kategori: @js(array_values(array_filter((array) request('kategori', [])))),
        kondisi: @js(array_values(array_filter((array) request('kondisi', [])))),
        kategoriNames: @js($categories->pluck('name', 'slug')),
        hargaPreset: @js(request('harga_preset', '')),
        minPrice: @js(request('min_price', '')),
        maxPrice: @js(request('max_price', '')),
        kota: @js($activeKota),
    })" class="space-y-3">

    {{-- BARIS CHIP FILTER KAPSUL (Sesuai Wireframe) --}}
    <div
        class="flex items-center gap-2.5 overflow-x-auto sm:overflow-visible no-scrollbar py-1 -mx-4 px-4 sm:mx-0 sm:px-0 sm:flex-wrap">

        {{-- 1. CHIP: KATEGORI --}}
        @if ($categories->count() > 0)
            <div class="relative shrink-0">
                <button type="button" @click="toggleFilter('kategori')"
                    class="inline-flex items-center gap-2 rounded-full border px-4 py-2 text-xs font-semibold transition-all whitespace-nowrap cursor-pointer"
                    :class="selectedKategori.length > 0
                                    ? 'border-neutral-900 bg-lime-300/60 text-neutral-900 font-bold'
                                    : 'border-neutral-800 bg-white text-neutral-900 hover:bg-neutral-100'">
                    <span>Kategori</span>
                    <span x-cloak x-show="selectedKategori.length > 0"
                        class="rounded-full bg-neutral-900 px-1.5 py-0.2 text-[10px] font-bold text-white"
                        x-text="selectedKategori.length"></span>
                    <svg class="size-3.5 text-neutral-700 transition-transform"
                        :class="openFilter === 'kategori' ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m6 9 6 6 6-6" />
                    </svg>
                </button>

                {{-- Backdrop Mobile --}}
                <div x-cloak x-show="openFilter === 'kategori'" @click.prevent.stop="closeFilter()"
                    class="fixed inset-0 z-40 bg-black/30 backdrop-blur-[1px] sm:hidden"></div>

                {{-- Popover / Modal Kategori --}}
                <div x-cloak x-show="openFilter === 'kategori'" @click.outside="closeFilter()"
                    class="fixed sm:absolute inset-x-4 sm:inset-x-auto sm:left-0 top-1/2 sm:top-full -translate-y-1/2 sm:translate-y-0 mt-0 sm:mt-2 z-50 mx-auto sm:mx-0 w-auto sm:w-96 max-w-md rounded-2xl border border-neutral-200 bg-white p-4 shadow-2xl sm:shadow-xl whitespace-normal"
                    style="display: none;">
                    <p class="mb-3 text-xs font-bold text-neutral-500">Pilih Kategori</p>
                    <div class="mb-4 flex max-h-56 flex-wrap gap-2 overflow-y-auto">
                        @foreach ($categories as $cat)
                            <button type="button" @click="toggleKategori('{{ $cat->slug }}')"
                                class="rounded-full border px-3.5 py-1.5 text-xs font-semibold transition-all cursor-pointer"
                                :class="selectedKategori.includes('{{ $cat->slug }}')
                                                            ? 'border-neutral-900 bg-lime-400 text-neutral-900 font-bold'
                                                            : 'border-neutral-200 bg-neutral-50 text-neutral-700 hover:bg-neutral-100'">
                                {{ $cat->name }}
                            </button>
                        @endforeach
                    </div>
                    <div class="flex items-center justify-between border-t border-neutral-100 pt-3">
                        <button type="button" @click="resetFilter('kategori')"
                            class="text-xs font-bold text-neutral-400 hover:text-neutral-700 cursor-pointer">Reset</button>
                        <button type="button" @click="closeFilter()"
                            class="rounded-lg px-3 py-1.5 text-xs font-bold text-neutral-800 hover:bg-neutral-100 cursor-pointer">Tutup</button>
                    </div>
                </div>
            </div>
        @endif

        {{-- 2. CHIP: URUTKAN --}}
        <div class="relative shrink-0">
            <button type="button" @click="toggleFilter('sort')"
                class="inline-flex items-center gap-2 rounded-full border border-neutral-800 bg-white px-4 py-2 text-xs font-semibold text-neutral-900 hover:bg-neutral-100 transition-all whitespace-nowrap cursor-pointer">
                <span>Urutkan: <strong class="font-bold" x-text="sortLabels[selectedSort] || 'Terbaru'"></strong></span>
                <svg class="size-3.5 text-neutral-700 transition-transform"
                    :class="openFilter === 'sort' ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m6 9 6 6 6-6" />
                </svg>
            </button>

            <div x-cloak x-show="openFilter === 'sort'" @click.prevent.stop="closeFilter()"
                class="fixed inset-0 z-40 bg-black/30 backdrop-blur-[1px] sm:hidden"></div>

            <div x-cloak x-show="openFilter === 'sort'" @click.outside="closeFilter()"
                class="fixed sm:absolute inset-x-4 sm:inset-x-auto sm:left-0 top-1/2 sm:top-full -translate-y-1/2 sm:translate-y-0 mt-0 sm:mt-2 z-50 mx-auto sm:mx-0 w-auto sm:w-48 max-w-xs rounded-2xl border border-neutral-200 bg-white p-2 shadow-2xl sm:shadow-xl space-y-1 whitespace-normal"
                style="display: none;">
                @foreach ($sortOptions as $key => $label)
                    <button type="button" @click="selectedSort = '{{ $key }}'; apply(); closeFilter()"
                        class="w-full rounded-xl px-3 py-2 text-left text-xs transition-colors hover:bg-neutral-100 cursor-pointer"
                        :class="selectedSort === '{{ $key }}' ? 'bg-lime-100 font-bold text-neutral-900' : 'font-medium text-neutral-700'">
                        {{ $label }}
                    </button>
                @endforeach
            </div>
        </div>

        {{-- GARIS PEMISAH VERTIKAL (Sesuai Wireframe) --}}
        <span class="mx-1 h-6 w-px shrink-0 bg-neutral-300" aria-hidden="true"></span>

        {{-- 3. CHIP: KONDISI --}}
        <div class="relative shrink-0">
            <button type="button" @click="toggleFilter('kondisi')"
                class="inline-flex items-center gap-2 rounded-full border px-4 py-2 text-xs font-semibold transition-all whitespace-nowrap cursor-pointer"
                :class="selectedKondisi.length > 0
                    ? 'border-neutral-900 bg-lime-300/60 text-neutral-900 font-bold'
                    : 'border-neutral-800 bg-white text-neutral-900 hover:bg-neutral-100'">
                <span>Kondisi</span>
                <span x-cloak x-show="selectedKondisi.length > 0"
                    class="rounded-full bg-neutral-900 px-1.5 py-0.2 text-[10px] font-bold text-white"
                    x-text="selectedKondisi.length"></span>
                <svg class="size-3.5 text-neutral-700 transition-transform"
                    :class="openFilter === 'kondisi' ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m6 9 6 6 6-6" />
                </svg>
            </button>

            <div x-cloak x-show="openFilter === 'kondisi'" @click.prevent.stop="closeFilter()"
                class="fixed inset-0 z-40 bg-black/30 backdrop-blur-[1px] sm:hidden"></div>

            <div x-cloak x-show="openFilter === 'kondisi'" @click.outside="closeFilter()"
                class="fixed sm:absolute inset-x-4 sm:inset-x-auto sm:left-0 top-1/2 sm:top-full -translate-y-1/2 sm:translate-y-0 mt-0 sm:mt-2 z-50 mx-auto sm:mx-0 w-auto sm:w-64 max-w-sm rounded-2xl border border-neutral-200 bg-white p-4 shadow-2xl sm:shadow-xl whitespace-normal"
                style="display: none;">
                <p class="mb-3 text-xs font-bold text-neutral-500">Pilih Kondisi Barang</p>
                <div class="space-y-2.5">
                    @foreach ($kondisiOptions as $kondisi)
                        <label class="flex items-center gap-2.5 text-xs font-medium text-neutral-800 cursor-pointer">
                            <input type="checkbox" value="{{ $kondisi }}" x-model="selectedKondisi" @change="apply()"
                                class="rounded border-neutral-400 text-neutral-900 focus:ring-lime-500">
                            <span>{{ $kondisi }}</span>
                        </label>
                    @endforeach
                </div>
                <div class="mt-3 flex items-center justify-between border-t border-neutral-100 pt-3">
                    <button type="button" @click="resetFilter('kondisi')"
                        class="text-xs font-bold text-neutral-400 hover:text-neutral-700 cursor-pointer">Reset</button>
                    <button type="button" @click="closeFilter()"
                        class="rounded-lg px-3 py-1.5 text-xs font-bold text-neutral-800 hover:bg-neutral-100 cursor-pointer">Tutup</button>
                </div>
            </div>
        </div>

        {{-- 4. CHIP: HARGA --}}
        <div class="relative shrink-0">
            <button type="button" @click="toggleFilter('harga')"
                class="inline-flex items-center gap-2 rounded-full border px-4 py-2 text-xs font-semibold transition-all whitespace-nowrap cursor-pointer"
                :class="(hargaPreset || minPrice !== '' || maxPrice !== '')
                    ? 'border-neutral-900 bg-lime-300/60 text-neutral-900 font-bold'
                    : 'border-neutral-800 bg-white text-neutral-900 hover:bg-neutral-100'">
                <span>Harga</span>
                <span x-cloak x-show="hargaPreset === 'gratis'">: Gratis</span>
                <span x-cloak x-show="hargaPreset === 'under_50k'">: &lt; Rp50rb</span>
                <span x-cloak x-show="hargaPreset === '50k_200k'">: Rp50rb–200rb</span>
                <span x-cloak x-show="hargaPreset === 'over_200k'">: &gt; Rp200rb</span>
                <span x-cloak x-show="!hargaPreset && (minPrice !== '' || maxPrice !== '')"
                    x-text="`: ${formatRupiah(minPrice) || 'Rp0'} - ${maxPrice !== '' ? formatRupiah(maxPrice) : 'Maks'}`"></span>
                <svg class="size-3.5 text-neutral-700 transition-transform"
                    :class="openFilter === 'harga' ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m6 9 6 6 6-6" />
                </svg>
            </button>

            <div x-cloak x-show="openFilter === 'harga'" @click.prevent.stop="closeFilter()"
                class="fixed inset-0 z-40 bg-black/30 backdrop-blur-[1px] sm:hidden"></div>

            <div x-cloak x-show="openFilter === 'harga'" @click.outside="closeFilter()"
                class="fixed sm:absolute inset-x-4 sm:inset-x-auto sm:left-0 top-1/2 sm:top-full -translate-y-1/2 sm:translate-y-0 mt-0 sm:mt-2 z-50 mx-auto sm:mx-0 w-auto sm:w-80 max-w-sm rounded-2xl border border-neutral-200 bg-white p-4 shadow-2xl sm:shadow-xl space-y-4 whitespace-normal"
                style="display: none;">
                <p class="text-xs font-bold text-neutral-500">Rentang Harga</p>
                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="mb-1 block text-[11px] font-semibold text-neutral-500">Minimal (Rp)</label>
                        <input type="number" x-model="minPrice" @input.debounce.400ms="hargaPreset = ''; apply()"
                            placeholder="0"
                            class="w-full rounded-xl border border-neutral-300 bg-neutral-50 px-2.5 py-1.5 text-xs outline-none focus:border-neutral-900 focus:bg-white">
                    </div>
                    <div>
                        <label class="mb-1 block text-[11px] font-semibold text-neutral-500">Maksimal (Rp)</label>
                        <input type="number" x-model="maxPrice" @input.debounce.400ms="hargaPreset = ''; apply()"
                            placeholder="Maks"
                            class="w-full rounded-xl border border-neutral-300 bg-neutral-50 px-2.5 py-1.5 text-xs outline-none focus:border-neutral-900 focus:bg-white">
                    </div>
                </div>
                <div class="flex flex-wrap gap-1.5">
                    <button type="button" @click="setHargaPreset('gratis')"
                        class="rounded-full border px-3 py-1 text-xs font-semibold cursor-pointer"
                        :class="hargaPreset === 'gratis' ? 'border-neutral-900 bg-lime-400 text-neutral-900 font-bold' : 'border-neutral-200 bg-neutral-50 text-neutral-700'">Gratis</button>
                    <button type="button" @click="setHargaPreset('under_50k')"
                        class="rounded-full border px-3 py-1 text-xs font-semibold cursor-pointer"
                        :class="hargaPreset === 'under_50k' ? 'border-neutral-900 bg-lime-400 text-neutral-900 font-bold' : 'border-neutral-200 bg-neutral-50 text-neutral-700'">&lt;
                        Rp50.000</button>
                    <button type="button" @click="setHargaPreset('50k_200k')"
                        class="rounded-full border px-3 py-1 text-xs font-semibold cursor-pointer"
                        :class="hargaPreset === '50k_200k' ? 'border-neutral-900 bg-lime-400 text-neutral-900 font-bold' : 'border-neutral-200 bg-neutral-50 text-neutral-700'">Rp50rb
                        - Rp200rb</button>
                    <button type="button" @click="setHargaPreset('over_200k')"
                        class="rounded-full border px-3 py-1 text-xs font-semibold cursor-pointer"
                        :class="hargaPreset === 'over_200k' ? 'border-neutral-900 bg-lime-400 text-neutral-900 font-bold' : 'border-neutral-200 bg-neutral-50 text-neutral-700'">&gt;
                        Rp200.000</button>
                </div>
                <div class="flex items-center justify-between border-t border-neutral-100 pt-3">
                    <button type="button" @click="resetFilter('harga')"
                        class="text-xs font-bold text-neutral-400 hover:text-neutral-700 cursor-pointer">Reset</button>
                    <button type="button" @click="closeFilter()"
                        class="rounded-lg px-3 py-1.5 text-xs font-bold text-neutral-800 hover:bg-neutral-100 cursor-pointer">Tutup</button>
                </div>
            </div>
        </div>
    </div>

    {{-- TAGS FILTER AKTIF (Muncul otomatis saat ada chip filter yang dipilih) --}}
    <div x-cloak x-show="activeTags.length > 0" class="flex flex-wrap items-center gap-2 pt-0.5 text-xs">
        <template x-for="tag in activeTags" :key="tag.key">
            <button type="button" @click="tag.remove()"
                class="inline-flex items-center gap-1.5 rounded-full border border-neutral-300 bg-white px-3 py-1 text-xs font-semibold text-neutral-800 hover:bg-neutral-100 transition-colors cursor-pointer group">
                <span x-text="tag.label"></span>
                <svg class="size-3.5 text-neutral-500 group-hover:text-neutral-900 shrink-0" fill="none"
                    viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </template>

        <button type="button" @click="resetAll()"
            class="ml-1 text-xs font-bold text-neutral-600 underline hover:text-neutral-900 cursor-pointer">
            Reset semua
        </button>
    </div>
</div>