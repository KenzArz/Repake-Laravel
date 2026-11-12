@php
    $type = session()->has('success') ? 'success' : (session()->has('error') ? 'error' : null);
    $message = session('success') ?? session('error');
@endphp

@if ($type && $message)
    <div id="flash-toast" role="status" aria-live="polite"
        class="fixed inset-x-4 top-5 z-50 mx-auto max-w-md -translate-y-3 opacity-0 transition-all duration-300 ease-out sm:inset-x-auto sm:right-6 sm:w-full">
        <div
            class="relative overflow-hidden rounded-2xl border {{ $type === 'success' ? 'border-emerald-200' : 'border-red-200' }} bg-white p-4 shadow-2xl ring-1 ring-black/5">
            <div class="flex items-start gap-3.5">
                @if ($type === 'success')
                    <div
                        class="grid size-10 shrink-0 place-items-center rounded-full bg-emerald-50 text-emerald-600 ring-4 ring-emerald-50/70">
                        <svg viewBox="0 0 24 24" class="size-5 fill-none stroke-current stroke-2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M20 6 9 17l-5-5" />
                        </svg>
                    </div>
                @else
                    <div
                        class="grid size-10 shrink-0 place-items-center rounded-full bg-red-50 text-red-600 ring-4 ring-red-50/70">
                        <svg viewBox="0 0 24 24" class="size-5 fill-none stroke-current stroke-2.5">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M12 9v4m0 4h.01M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0Z" />
                        </svg>
                    </div>
                @endif

                <div class="min-w-0 flex-1 pt-0.5">
                    <p class="text-sm font-bold text-neutral-900">
                        {{ $type === 'success' ? 'Berhasil' : 'Terjadi Kesalahan' }}
                    </p>
                    <p class="mt-0.5 text-sm leading-relaxed text-neutral-600">
                        {{ $message }}
                    </p>
                </div>

                <button type="button" id="flash-toast-close"
                    class="grid size-8 shrink-0 place-items-center rounded-full text-neutral-400 transition hover:bg-neutral-100 hover:text-neutral-700 cursor-pointer"
                    aria-label="Tutup notifikasi">
                    <svg viewBox="0 0 24 24" class="size-4.5 fill-none stroke-current stroke-2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M18 6 6 18M6 6l12 12" />
                    </svg>
                </button>
            </div>

            {{-- Indikator waktu tutup otomatis --}}
            <div class="absolute inset-x-0 bottom-0 h-1 bg-neutral-100">
                <div id="flash-toast-progress"
                    class="h-full w-full origin-left {{ $type === 'success' ? 'bg-emerald-500' : 'bg-red-500' }} transition-transform ease-linear"
                    style="transition-duration: 4500ms;"></div>
            </div>
        </div>
    </div>

    <script>
        (() => {
            const toast = document.getElementById("flash-toast");
            const closeBtn = document.getElementById("flash-toast-close");
            const progress = document.getElementById("flash-toast-progress");
            if (!toast) return;

            const DURATION = 4500;
            let remaining = DURATION;
            let startTime = Date.now();
            let timer = null;

            const dismiss = () => {
                clearTimeout(timer);
                toast.classList.add("opacity-0", "-translate-y-3");
                toast.classList.remove("opacity-100", "translate-y-0");
                setTimeout(() => toast.remove(), 300);
            };

            const startTimer = (ms) => {
                startTime = Date.now();
                if (progress) {
                    progress.style.transitionDuration = `${ms}ms`;
                    requestAnimationFrame(() => {
                        progress.style.transform = "scaleX(0)";
                    });
                }
                timer = setTimeout(dismiss, ms);
            };

            const pauseTimer = () => {
                clearTimeout(timer);
                remaining -= Date.now() - startTime;
                if (progress) {
                    const ratio = Math.max(0, remaining / DURATION);
                    progress.style.transitionDuration = "0ms";
                    progress.style.transform = `scaleX(${ratio})`;
                }
            };

            // Animasi masuk
            requestAnimationFrame(() => {
                toast.classList.remove("opacity-0", "-translate-y-3");
                toast.classList.add("opacity-100", "translate-y-0");
                startTimer(remaining);
            });

            toast.addEventListener("mouseenter", pauseTimer);
            toast.addEventListener("mouseleave", () => {
                if (remaining > 0) startTimer(remaining);
            });
            closeBtn?.addEventListener("click", dismiss);
        })();
    </script>
@endif