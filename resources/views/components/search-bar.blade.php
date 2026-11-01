<form action="{{ route('home') }}" method="GET" role="search"
      class="flex h-10 min-w-0 flex-1 items-center gap-2 rounded-lg border border-neutral-300 px-3 transition focus-within:border-neutral-900">
    <svg viewBox="0 0 24 24" class="size-[18px] shrink-0 fill-none stroke-neutral-900 stroke-[1.75] [stroke-linecap:round] [stroke-linejoin:round]" aria-hidden="true">
        <circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/>
    </svg>
    <input type="search" name="q" value="{{ request('q') }}"
           placeholder="Cari sepeda, laptop, sofa..."
           class="min-w-0 flex-1 bg-transparent text-label outline-none placeholder:text-neutral-700">
</form>