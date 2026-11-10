<footer class="mt-20 border-t border-neutral-200 bg-white py-8 text-center text-xs text-neutral-500">
    <div class="mx-auto flex max-w-6xl flex-col items-center justify-between gap-4 px-4 sm:flex-row">
        <p class="font-bold text-neutral-800">
            RePake<span class="text-lime-600">.</span> &copy; {{ date('Y') }} Jual Beli Barang Bekas Kampus & Komunitas
        </p>
        <div class="flex items-center gap-5 text-neutral-600">
            <a href="{{ route('home') }}" class="transition hover:text-neutral-900">Beranda</a>
            <a href="{{ route('favorites.index') }}" class="transition hover:text-neutral-900">Favorit</a>
            <a href="{{ route('profile.catalog') }}" class="transition hover:text-neutral-900">Profil</a>
        </div>
    </div>
</footer>
