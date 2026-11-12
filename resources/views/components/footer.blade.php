@php
    $r = fn(string $name, $params = []) => Route::has($name) ? route($name, $params) : '#';
    $needLogin = auth()->guest() ? 'data-open-login' : '';
@endphp

<footer class="mt-20 border-t border-neutral-800 bg-neutral-900 pt-16 pb-24 text-white md:pb-12">
    <div class="mx-auto max-w-6xl px-4 sm:px-6">
        <div class="grid grid-cols-1 gap-10 border-b border-neutral-800/80 pb-12 md:grid-cols-5 lg:gap-12">
            {{-- Brand Column --}}
            <div class="space-y-4 md:col-span-2">
                <a href="{{ $r('home') }}" class="inline-flex items-center gap-2.5 group">
                    <img src="{{ asset('images/repake-logo.png') }}" alt="RePake Logo"
                        class="size-8 rounded-lg object-contain shrink-0" onerror="this.remove()">
                    <span class="text-2xl font-black tracking-tight text-white">
                        RePake<span class="text-lime-400">.</span>
                    </span>
                </a>
                <p class="max-w-sm text-sm leading-relaxed text-neutral-400">
                    Platform jual beli barang bekas berkualitas untuk mahasiswa dan komunitas sekitar kampus. Lebih
                    hemat, praktis, dan ramah lingkungan.
                </p>
                <div class="flex items-center gap-3 pt-2 text-neutral-400">
                    <span class="rounded-full bg-neutral-800 px-3 py-1 text-xs font-medium text-neutral-300">
                        ♻️ Barang lama mu, menjadi cerita baru di hidup orang lain
                    </span>
                </div>
            </div>

            {{-- Jelajahi --}}
            <div class="space-y-3">
                <h4 class="text-sm font-bold uppercase tracking-wider text-white">Jelajahi</h4>
                <ul class="space-y-2.5 text-sm text-neutral-400">
                    <li>
                        <a href="{{ $r('products.index') }}" class="transition-colors hover:text-lime-400">
                            Semua Produk
                        </a>
                    </li>
                    <li>
                        <a href="{{ $r('products.index', ['sort' => 'terdekat']) }}"
                            class="transition-colors hover:text-lime-400">
                            Terdekat dari Kamu
                        </a>
                    </li>
                    <li>
                        <a href="{{ $r('favorites.index') }}" {{ $needLogin }}
                            class="transition-colors hover:text-lime-400">
                            Produk Favorit
                        </a>
                    </li>
                    <li>
                        <a href="{{ $r('chat.index') }}" {{ $needLogin }} class="transition-colors hover:text-lime-400">
                            Inbox & Negosiasi
                        </a>
                    </li>
                </ul>
            </div>

            {{-- Untuk Penjual --}}
            <div class="space-y-3">
                <h4 class="text-sm font-bold uppercase tracking-wider text-white">Untuk Penjual</h4>
                <ul class="space-y-2.5 text-sm text-neutral-400">
                    <li>
                        <a href="{{ $r('products.create') }}" {{ $needLogin }}
                            class="transition-colors hover:text-lime-400">
                            Pasang Iklan Barang
                        </a>
                    </li>
                    <li>
                        <a href="{{ $r('profile.catalog') }}" {{ $needLogin }}
                            class="transition-colors hover:text-lime-400">
                            Katalog & Profil Saya
                        </a>
                    </li>
                    <li>
                        <a href="#" class="transition-colors hover:text-lime-400">
                            Panduan Jual Beli Aman
                        </a>
                    </li>
                    <li>
                        <a href="#" class="transition-colors hover:text-lime-400">
                            Tips Transaksi & COD
                        </a>
                    </li>
                </ul>
            </div>

            {{-- Bantuan & Kontak --}}
            <div class="space-y-3">
                <h4 class="text-sm font-bold uppercase tracking-wider text-white">Bantuan & Kontak</h4>
                <ul class="space-y-2.5 text-sm text-neutral-400">
                    <li>
                        <a href="#" class="transition-colors hover:text-lime-400">
                            Pusat Bantuan
                        </a>
                    </li>
                    <li>
                        <a href="https://wa.me/6281234567890" target="_blank" rel="noopener noreferrer"
                            class="transition-colors hover:text-lime-400">
                            Hubungi via WhatsApp
                        </a>
                    </li>
                    <li>
                        <a href="#" class="transition-colors hover:text-lime-400">
                            Syarat & Ketentuan
                        </a>
                    </li>
                    <li>
                        <a href="#" class="transition-colors hover:text-lime-400">
                            Kebijakan Privasi
                        </a>
                    </li>
                </ul>
            </div>
        </div>

        {{-- Bottom Copyright --}}
        <div class="flex flex-col items-center justify-between gap-4 pt-8 text-xs text-neutral-500 sm:flex-row">
            <p>&copy; {{ date('Y') }} RePake. Platform Marketplace & Preloved Komunitas Mahasiswa Kampus</p>
            <p class="flex items-center gap-1.5">
                <span>Dikelola dengan ❤️ untuk Mahasiswa & Komunitas</span>
            </p>
        </div>
    </div>
</footer>