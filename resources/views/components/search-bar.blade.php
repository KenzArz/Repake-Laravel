<form action="{{ route('products.index') }}" method="GET" role="search"
      class="flex h-10 w-full items-center gap-2 rounded-lg border border-neutral-300 bg-white px-3 transition focus-within:border-neutral-900">
    <svg viewBox="0 0 24 24" class="size-4 shrink-0 fill-none stroke-neutral-700 stroke-2" aria-hidden="true">
        <circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/>
    </svg>
    <input type="search" name="q" value="{{ request('q') }}"
           placeholder="Cari sepeda, laptop, sofa..."
           class="min-w-0 flex-1 bg-transparent text-xs sm:text-sm text-neutral-800 outline-none placeholder:text-neutral-500">
</form>