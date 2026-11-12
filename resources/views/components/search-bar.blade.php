<div x-data="{
        query: @js((string) request('q', '')),
        isOpen: false,
        mobileSearchOpen: false,
        isLoading: false,
        products: [],
        categories: [],
        selectedIndex: -1,
        popularSearches: ['Sepeda', 'Laptop', 'Kulkas Mini', 'Buku Kuliah', 'Meja Belajar', 'Kamera'],

        fetchSuggestions() {
            if (this.query.trim().length < 2) {
                this.products = [];
                this.categories = [];
                this.isLoading = false;
                this.selectedIndex = -1;
                return;
            }

            this.isLoading = true;
            fetch(`{{ route('api.search.suggestions') }}?q=${encodeURIComponent(this.query.trim())}`)
                .then(res => res.json())
                .then(data => {
                    this.products = data.products || [];
                    this.categories = data.categories || [];
                    this.isLoading = false;
                    this.selectedIndex = -1;
                })
                .catch(() => {
                    this.isLoading = false;
                });
        },

        totalItems() {
            return this.categories.length + this.products.length;
        },

        selectNext() {
            if (this.totalItems() === 0) return;
            this.selectedIndex = (this.selectedIndex + 1) % this.totalItems();
        },

        selectPrev() {
            if (this.totalItems() === 0) return;
            this.selectedIndex = (this.selectedIndex - 1 + this.totalItems()) % this.totalItems();
        },

        pickSuggestion(keyword) {
            this.query = keyword;
            this.isOpen = false;
            this.mobileSearchOpen = false;
            this.goToSearch(keyword);
        },

        goToSelected() {
            if (this.selectedIndex >= 0) {
                if (this.selectedIndex < this.categories.length) {
                    window.location.href = this.categories[this.selectedIndex].url;
                } else {
                    const productIndex = this.selectedIndex - this.categories.length;
                    const chosen = this.products[productIndex];
                    if (chosen) {
                        this.pickSuggestion(chosen.judul);
                    }
                }
            } else {
                this.submitForm();
            }
        },

        goToSearch(customQuery = null) {
            const q = (customQuery !== null ? customQuery : this.query).trim();
            const url = new URL(@js(route('products.index')), window.location.origin);

            // Pertahankan lokasi aktif dari URL jika ada
            const cur = new URLSearchParams(window.location.search);
            ['kota', 'lat', 'lng'].forEach(k => {
                if (cur.get(k)) url.searchParams.set(k, cur.get(k));
            });

            if (q.length > 0) {
                url.searchParams.set('q', q);
            }
            window.location.href = url.toString();
        },

        submitForm() {
            this.isOpen = false;
            this.mobileSearchOpen = false;
            this.goToSearch();
        }
    }" @open-mobile-search.window="mobileSearchOpen = true; $nextTick(() => $refs.mobileSearchInput?.focus())"
    class="w-full">

    {{-- DESKTOP SEARCH BAR --}}
    <div @click.outside="isOpen = false" class="relative w-full">
        <form action="{{ route('products.index') }}" method="GET" @submit.prevent="submitForm()" class="relative">
            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-neutral-400">
                <template x-if="!isLoading">
                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                    </svg>
                </template>
                <template x-if="isLoading">
                    <svg class="size-4 animate-spin text-lime-600" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4">
                        </circle>
                        <path class="opacity-75" fill="currentColor"
                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                        </path>
                    </svg>
                </template>
            </div>

            <input type="text" name="q" x-model="query"
                @focus="isOpen = true; if (query.trim().length >= 2 && products.length === 0) fetchSuggestions();"
                @input.debounce.250ms="fetchSuggestions(); isOpen = true;" @keydown.arrow-down.prevent="selectNext()"
                @keydown.arrow-up.prevent="selectPrev()" @keydown.enter.prevent="goToSelected()"
                @keydown.escape="isOpen = false" placeholder="Cari sepeda, laptop, keyboard..." autocomplete="off"
                class="h-10 w-full rounded-lg border border-neutral-400 bg-white pl-10 pr-9 text-xs sm:text-sm text-neutral-900 placeholder:text-neutral-400 outline-none transition focus:border-neutral-900 focus:ring-2 focus:ring-lime-400/30">

            <button type="button" x-cloak x-show="query.length > 0"
                @click="query = ''; products = []; categories = []; selectedIndex = -1;"
                class="absolute inset-y-0 right-0 flex items-center pr-3 text-neutral-400 hover:text-neutral-700 cursor-pointer"
                aria-label="Hapus pencarian">
                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </form>

        {{-- DESKTOP DROPDOWN REKOMENDASI --}}
        <div x-cloak x-show="isOpen" x-transition:enter="transition ease-out duration-150"
            x-transition:enter-start="opacity-0 translate-y-1" x-transition:enter-end="opacity-100 translate-y-0"
            x-transition:leave="transition ease-in duration-100" x-transition:leave-start="opacity-100 translate-y-0"
            x-transition:leave-end="opacity-0 translate-y-1"
            class="absolute left-0 right-0 top-full z-50 mt-2 overflow-hidden rounded-2xl border border-neutral-200 bg-white py-2 shadow-xl"
            style="display: none;">

            {{-- State 1: Input < 2 karakter -> Pencarian Populer --}}
                <template x-if="query.trim().length < 2">
                    <div class="px-4 py-2">
                        <p class="mb-2 text-[11px] font-extrabold uppercase tracking-wider text-neutral-400">
                            Pencarian Populer
                        </p>
                        <div class="flex flex-wrap gap-1.5">
                            <template x-for="item in popularSearches" :key="item">
                                <button type="button" @click="pickSuggestion(item)"
                                    class="rounded-lg bg-neutral-100 px-2.5 py-1 text-xs font-semibold text-neutral-700 transition-colors hover:bg-lime-100 hover:text-lime-900 cursor-pointer">
                                    <span x-text="item"></span>
                                </button>
                            </template>
                        </div>
                    </div>
                </template>

                {{-- State 2: Input >= 2 karakter -> Kategori + Rekomendasi Kata Kunci Produk --}}
                <template x-if="query.trim().length >= 2">
                    <div>
                        {{-- Kategori Terkait --}}
                        <template x-if="categories.length > 0">
                            <div class="mb-1.5 border-b border-neutral-100 pb-1.5">
                                <p
                                    class="px-4 py-1 text-[11px] font-extrabold uppercase tracking-wider text-neutral-400">
                                    Kategori Terkait
                                </p>
                                <template x-for="(cat, index) in categories" :key="cat.slug">
                                    <a :href="cat.url"
                                        class="flex items-center gap-2.5 px-4 py-2 text-xs font-bold text-neutral-900 transition-colors hover:bg-lime-50 hover:text-lime-800"
                                        :class="{ 'bg-lime-50 text-lime-800': selectedIndex === index }">
                                        <svg class="size-3.5 shrink-0 text-lime-600" fill="none" viewBox="0 0 24 24"
                                            stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M9.568 3H5.25A2.25 2.25 0 003 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 005.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 009.568 3z" />
                                        </svg>
                                        <span>Lihat kategori <span class="text-lime-700 underline"
                                                x-text="cat.name"></span></span>
                                    </a>
                                </template>
                            </div>
                        </template>

                        {{-- Rekomendasi Produk (Klik -> cari ke halaman pencarian, BUKAN detail) --}}
                        <template x-if="products.length > 0">
                            <div class="py-1">
                                <p
                                    class="px-4 py-1 text-[11px] font-extrabold uppercase tracking-wider text-neutral-400">
                                    Rekomendasi Pencarian
                                </p>
                                <template x-for="(item, index) in products" :key="item.id">
                                    <button type="button" @click="pickSuggestion(item.judul)"
                                        class="flex w-full items-center justify-between px-4 py-2 text-left transition-colors hover:bg-neutral-50 group cursor-pointer"
                                        :class="{ 'bg-lime-50/70': selectedIndex === (categories.length + index) }">
                                        <div class="flex min-w-0 items-center gap-2.5 pr-2">
                                            <svg class="size-3.5 shrink-0 text-neutral-400 group-hover:text-lime-600"
                                                fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                                            </svg>
                                            <div class="min-w-0">
                                                <p class="truncate text-xs font-bold text-neutral-900 group-hover:text-lime-800"
                                                    x-text="item.judul"></p>
                                                <p class="truncate text-[11px] text-neutral-400">
                                                    <span x-text="item.harga"></span> · <span x-text="item.kota"></span>
                                                </p>
                                            </div>
                                        </div>
                                        <span
                                            class="shrink-0 rounded bg-neutral-100 px-2 py-0.5 text-[10px] font-bold text-neutral-600 group-hover:bg-lime-100 group-hover:text-lime-800"
                                            x-text="item.category"></span>
                                    </button>
                                </template>
                            </div>
                        </template>

                        {{-- Kosong --}}
                        <template x-if="products.length === 0 && categories.length === 0 && !isLoading">
                            <div class="px-4 py-4 text-center">
                                <p class="text-xs text-neutral-500">
                                    Tidak ada rekomendasi untuk "<span class="font-bold text-neutral-900"
                                        x-text="query"></span>"
                                </p>
                            </div>
                        </template>

                        {{-- Footer CTA Cari Semua --}}
                        <div class="border-t border-neutral-100 bg-neutral-50/70 px-4 py-2 mt-1">
                            <button type="button" @click="submitForm()"
                                class="flex w-full items-center justify-between text-left text-xs font-bold text-lime-700 hover:text-lime-900 cursor-pointer">
                                <span>Cari semua produk untuk "<span x-text="query"></span>"</span>
                                <svg class="size-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                    stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </template>
        </div>
    </div>

    {{-- MOBILE LIVE SEARCH OVERLAY MODAL (x-teleport="body" agar layar penuh solid putih) --}}
    <template x-teleport="body">
        <div x-show="mobileSearchOpen" x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100 translate-y-0"
            x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 translate-y-0"
            x-transition:leave-end="opacity-0 -translate-y-2"
            class="fixed inset-0 z-[999] flex h-screen w-screen flex-col bg-white md:hidden" style="display: none;">

            {{-- Top Input Bar --}}
            <div class="flex shrink-0 items-center gap-2.5 border-b border-neutral-200 bg-white px-4 py-3">
                <button type="button" @click="mobileSearchOpen = false"
                    class="rounded-lg p-1.5 text-neutral-600 hover:bg-neutral-100 hover:text-neutral-900"
                    aria-label="Tutup pencarian">
                    <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
                    </svg>
                </button>

                <form @submit.prevent="submitForm()" class="relative flex-1">
                    <input x-ref="mobileSearchInput" type="text" x-model="query"
                        @input.debounce.250ms="fetchSuggestions()" @keydown.enter.prevent="submitForm()"
                        placeholder="Cari sepeda, laptop, sofa..." autocomplete="off"
                        class="w-full rounded-xl border-none bg-neutral-100 py-2 pl-9 pr-9 text-sm text-neutral-900 placeholder:text-neutral-400 outline-none focus:ring-2 focus:ring-lime-500/30">
                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-neutral-400">
                        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                        </svg>
                    </div>
                    <button type="button" x-show="query.length > 0" @click="query = ''; products = []; categories = [];"
                        class="absolute inset-y-0 right-0 flex items-center pr-3 text-neutral-400">
                        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </form>
            </div>

            {{-- Area Rekomendasi Mobile --}}
            <div class="flex-1 space-y-5 overflow-y-auto bg-white p-4">
                <div x-show="isLoading" class="py-4 text-center text-xs font-semibold text-lime-700">
                    Memuat rekomendasi...
                </div>

                {{-- Pencarian Populer --}}
                <template x-if="query.trim().length < 2 && !isLoading">
                    <div>
                        <p class="mb-2.5 text-xs font-extrabold uppercase tracking-wider text-neutral-400">
                            Pencarian Populer
                        </p>
                        <div class="flex flex-wrap gap-2">
                            <template x-for="item in popularSearches" :key="item">
                                <button type="button" @click="pickSuggestion(item)"
                                    class="rounded-xl bg-neutral-100 px-3 py-1.5 text-xs font-semibold text-neutral-700 transition-colors hover:bg-lime-100 hover:text-lime-900">
                                    <span x-text="item"></span>
                                </button>
                            </template>
                        </div>
                    </div>
                </template>

                {{-- Kategori Terkait --}}
                <template x-if="query.trim().length >= 2 && categories.length > 0">
                    <div>
                        <p class="mb-2 text-xs font-extrabold uppercase tracking-wider text-neutral-400">
                            Kategori Terkait
                        </p>
                        <div class="space-y-1.5">
                            <template x-for="cat in categories" :key="cat.slug">
                                <a :href="cat.url"
                                    class="flex items-center gap-2.5 rounded-xl bg-neutral-50 p-3 text-xs font-bold text-neutral-900 transition-colors hover:bg-lime-50">
                                    <svg class="size-4 shrink-0 text-lime-600" fill="none" viewBox="0 0 24 24"
                                        stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M9.568 3H5.25A2.25 2.25 0 003 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 005.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 009.568 3z" />
                                    </svg>
                                    <span>Lihat kategori <span class="text-lime-700" x-text="cat.name"></span></span>
                                </a>
                            </template>
                        </div>
                    </div>
                </template>

                {{-- Rekomendasi Produk (Klik -> ke halaman pencarian, BUKAN detail) --}}
                <template x-if="query.trim().length >= 2 && products.length > 0">
                    <div>
                        <p class="mb-2 text-xs font-extrabold uppercase tracking-wider text-neutral-400">
                            Rekomendasi Pencarian
                        </p>
                        <div class="space-y-2">
                            <template x-for="item in products" :key="item.id">
                                <button type="button" @click="pickSuggestion(item.judul)"
                                    class="flex w-full items-center justify-between rounded-xl border border-neutral-200 bg-neutral-50/80 p-3 text-left transition-all hover:bg-lime-50/60">
                                    <div class="min-w-0 pr-2">
                                        <p class="truncate text-xs font-bold text-neutral-900" x-text="item.judul"></p>
                                        <p class="truncate text-[11px] text-neutral-500">
                                            <span x-text="item.harga"></span> · <span x-text="item.kota"></span>
                                        </p>
                                    </div>
                                    <span
                                        class="shrink-0 rounded border border-lime-200 bg-white px-2 py-0.5 text-[10px] font-bold text-lime-800"
                                        x-text="item.category"></span>
                                </button>
                            </template>
                        </div>
                    </div>
                </template>

                {{-- Kosong --}}
                <template
                    x-if="query.trim().length >= 2 && products.length === 0 && categories.length === 0 && !isLoading">
                    <div class="py-8 text-center">
                        <p class="text-xs font-medium text-neutral-500">
                            Tidak ada rekomendasi untuk "<span class="font-bold text-neutral-900"
                                x-text="query"></span>"
                        </p>
                    </div>
                </template>

                {{-- Tombol Cari Semua --}}
                <template x-if="query.trim().length >= 2">
                    <button type="button" @click="submitForm()"
                        class="mt-4 flex w-full items-center justify-center gap-2 rounded-xl bg-lime-400 py-3 text-xs font-extrabold text-neutral-900 shadow-sm transition-all active:scale-[0.98]">
                        <span>Cari semua produk untuk "<span x-text="query"></span>"</span>
                        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                        </svg>
                    </button>
                </template>
            </div>
        </div>
    </template>
</div>