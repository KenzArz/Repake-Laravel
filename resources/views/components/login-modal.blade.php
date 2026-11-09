{{-- Pop-up Modal Login SSO Google UNSIKA [ALE] --}}
<div id="login-modal" class="fixed inset-0 z-50 hidden flex items-center justify-center bg-black/50 p-4 backdrop-blur-xs transition duration-200">
    <div class="relative w-full max-w-sm rounded-3xl bg-white p-7 shadow-2xl text-center transition-all">

        {{-- Tombol Tutup (X) --}}
        <button type="button" data-close-login
                class="absolute right-5 top-5 grid size-8 place-items-center rounded-full text-neutral-400 hover:bg-neutral-100 hover:text-neutral-800 transition"
                aria-label="Tutup">
            <svg viewBox="0 0 24 24" class="size-5 fill-none stroke-current stroke-2 [stroke-linecap:round] [stroke-linejoin:round]">
                <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
            </svg>
        </button>

        {{-- STEP 1: Tampilan Utama Sesuai Gambar Wireframe --}}
        <div id="login-step-main">
            {{-- Logo RePake Asli Sesuai Gambar --}}
            <div class="mx-auto flex size-16 items-center justify-center overflow-hidden rounded-2xl shadow-sm">
                <img src="{{ asset('images/repake-logo.png') }}" alt="RePake Logo" class="size-full object-cover">
            </div>

            {{-- Judul --}}
            <h2 class="mt-4 text-xl font-black tracking-tight text-neutral-900">
                Masuk ke RePake
            </h2>

            {{-- Subjudul --}}
            <p class="mx-auto mt-3 max-w-[240px] text-xs font-medium leading-relaxed text-neutral-600">
                Silahkan masuk menggunakan akun Google UNSIKA anda untuk mulai menjelajahi RePake
            </p>

            {{-- Tombol Continue with Google --}}
            <button type="button" id="btn-continue-google"
                    class="mt-6 flex w-full items-center justify-center gap-3 rounded-full border border-neutral-300 bg-white px-4 py-2.5 text-xs font-bold text-neutral-800 shadow-2xs transition hover:border-neutral-400 hover:bg-neutral-50 active:scale-[0.98]">
                {{-- Google G Multi-Color SVG --}}
                <svg class="size-4 shrink-0" viewBox="0 0 24 24">
                    <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                    <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                    <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.06H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.94l2.85-2.22.81-.63z"/>
                    <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.06l3.66 2.84c.87-2.6 3.3-4.52 6.16-4.52z"/>
                </svg>
                <span>Continue with Google</span>
            </button>

            {{-- Footer T&C Note --}}
            <p class="mt-6 text-[11px] font-medium leading-relaxed text-neutral-400">
                Dengan masuk, Anda menyetujui<br>
                <a href="#" class="underline hover:text-neutral-600">Syarat dan Ketentuan RePake</a>
            </p>
        </div>

        {{-- STEP 2: Google SSO Account Chooser ("Pilih Akun yang Kamu Punya") --}}
        <div id="login-step-accounts" class="hidden text-left">
            <div class="mb-4 flex items-center justify-between border-b border-neutral-100 pb-3">
                <div class="flex items-center gap-2">
                    <svg class="size-4" viewBox="0 0 24 24">
                        <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                        <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                        <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.06H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.94l2.85-2.22.81-.63z"/>
                        <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.06l3.66 2.84c.87-2.6 3.3-4.52 6.16-4.52z"/>
                    </svg>
                    <span class="text-xs font-bold text-neutral-800">Login dengan Google</span>
                </div>
                <button type="button" id="btn-back-to-step1" class="text-[11px] font-semibold text-neutral-500 hover:text-neutral-900">
                    &larr; Kembali
                </button>
            </div>

            <div class="mb-3 text-center">
                <h3 class="text-base font-bold text-neutral-900">Pilih akun Google Anda</h3>
                <p class="text-[11px] text-neutral-500">untuk melanjutkan ke <strong class="text-neutral-800">RePake</strong></p>
            </div>

            {{-- Alert error validasi domain @student.unsika.ac.id --}}
            <div id="login-error-alert" class="hidden mb-3 rounded-xl border border-red-200 bg-red-50 p-2.5 text-xs font-medium text-red-700 leading-tight">
            </div>

            {{-- Daftar Akun yang Tersedia di Perangkat --}}
            <div class="space-y-1.5">
                {{-- Akun 1: Midou Wijaya --}}
                <button type="button"
                        onclick="performSsoLogin('midou.kun@student.unsika.ac.id', 'Midou Wijaya')"
                        class="group flex w-full items-center gap-3 rounded-2xl border border-neutral-200 bg-white p-2.5 transition hover:bg-neutral-50 hover:border-neutral-400">
                    <div class="grid size-9 shrink-0 place-items-center rounded-full bg-emerald-600 font-bold text-xs text-white">
                        M
                    </div>
                    <div class="min-w-0 flex-1 text-left">
                        <p class="truncate text-xs font-bold text-neutral-900">Midou Wijaya</p>
                        <p class="truncate text-[11px] text-neutral-500">midou.kun@student.unsika.ac.id</p>
                    </div>
                    <span class="rounded bg-lime-100 px-1.5 py-0.5 text-[9px] font-bold text-lime-800">UNSIKA</span>
                </button>

                {{-- Akun 2: Ale Awaludin --}}
                <button type="button"
                        onclick="performSsoLogin('ale.awaludin@student.unsika.ac.id', 'Ale Awaludin')"
                        class="group flex w-full items-center gap-3 rounded-2xl border border-neutral-200 bg-white p-2.5 transition hover:bg-neutral-50 hover:border-neutral-400">
                    <div class="grid size-9 shrink-0 place-items-center rounded-full bg-indigo-600 font-bold text-xs text-white">
                        A
                    </div>
                    <div class="min-w-0 flex-1 text-left">
                        <p class="truncate text-xs font-bold text-neutral-900">Ale Awaludin</p>
                        <p class="truncate text-[11px] text-neutral-500">ale.awaludin@student.unsika.ac.id</p>
                    </div>
                    <span class="rounded bg-lime-100 px-1.5 py-0.5 text-[9px] font-bold text-lime-800">UNSIKA</span>
                </button>

                {{-- Akun 3: Reza Auditore --}}
                <button type="button"
                        onclick="performSsoLogin('reza.auditore@student.unsika.ac.id', 'Reza Auditore')"
                        class="group flex w-full items-center gap-3 rounded-2xl border border-neutral-200 bg-white p-2.5 transition hover:bg-neutral-50 hover:border-neutral-400">
                    <div class="grid size-9 shrink-0 place-items-center rounded-full bg-amber-600 font-bold text-xs text-white">
                        R
                    </div>
                    <div class="min-w-0 flex-1 text-left">
                        <p class="truncate text-xs font-bold text-neutral-900">Reza Auditore</p>
                        <p class="truncate text-[11px] text-neutral-500">reza.auditore@student.unsika.ac.id</p>
                    </div>
                    <span class="rounded bg-lime-100 px-1.5 py-0.5 text-[9px] font-bold text-lime-800">UNSIKA</span>
                </button>

                {{-- Opsi: Gunakan Akun Lain --}}
                <button type="button" id="btn-toggle-custom-account"
                        class="flex w-full items-center gap-3 rounded-2xl border border-dashed border-neutral-300 p-2.5 transition hover:bg-neutral-50 hover:border-neutral-400">
                    <div class="grid size-9 shrink-0 place-items-center rounded-full bg-neutral-100 text-neutral-600">
                        <svg viewBox="0 0 24 24" class="size-4 stroke-current stroke-2 fill-none">
                            <path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/><line x1="19" y1="8" x2="19" y2="14"/><line x1="22" y1="11" x2="16" y2="11"/>
                        </svg>
                    </div>
                    <div class="text-left">
                        <p class="text-xs font-bold text-neutral-800">Gunakan akun UNSIKA lain</p>
                        <p class="text-[10px] text-neutral-400">Ketik email @student.unsika.ac.id</p>
                    </div>
                </button>
            </div>

            {{-- Form Input Akun Kustom (Hidden by Default) --}}
            <form id="custom-account-form" class="hidden mt-3 pt-3 border-t border-neutral-100 space-y-2.5">
                <div>
                    <label for="custom-email-input" class="block text-[11px] font-bold text-neutral-700 mb-1">
                        Email Google UNSIKA
                    </label>
                    <input type="email" id="custom-email-input" required
                           placeholder="nama@student.unsika.ac.id"
                           class="w-full rounded-xl border border-neutral-300 bg-white px-3 py-2 text-xs text-neutral-900 outline-none focus:border-neutral-900">
                    <p class="mt-1 text-[10px] text-neutral-500">
                        *Wajib berakhiran <strong>@student.unsika.ac.id</strong>
                    </p>
                </div>

                <button type="submit"
                        class="w-full rounded-xl bg-[#B8E639] py-2 text-xs font-bold text-neutral-900 transition hover:bg-lime-500">
                    Masuk dengan Akun Ini
                </button>
            </form>
        </div>

    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const modal = document.getElementById('login-modal');
        const stepMain = document.getElementById('login-step-main');
        const stepAccounts = document.getElementById('login-step-accounts');
        const btnContinue = document.getElementById('btn-continue-google');
        const btnBack = document.getElementById('btn-back-to-step1');
        const btnToggleCustom = document.getElementById('btn-toggle-custom-account');
        const customForm = document.getElementById('custom-account-form');
        const errorAlert = document.getElementById('login-error-alert');

        function openModal() {
            modal.classList.remove('hidden');
            stepMain.classList.remove('hidden');
            stepAccounts.classList.add('hidden');
            if (errorAlert) errorAlert.classList.add('hidden');
        }

        function closeModal() {
            modal.classList.add('hidden');
        }

        // Buka saat event 'open-login' dipicu
        window.addEventListener('open-login', openModal);

        // Tutup saat tombol X diklik
        document.querySelectorAll('[data-close-login]').forEach(btn => {
            btn.addEventListener('click', closeModal);
        });

        // Tutup jika klik di area luar modal
        modal.addEventListener('click', function (e) {
            if (e.target === modal) {
                closeModal();
            }
        });

        // Klik "Continue with Google" -> tampilkan daftar akun
        btnContinue?.addEventListener('click', function () {
            stepMain.classList.add('hidden');
            stepAccounts.classList.remove('hidden');
        });

        // Tombol kembali ke langkah 1
        btnBack?.addEventListener('click', function () {
            stepAccounts.classList.add('hidden');
            stepMain.classList.remove('hidden');
        });

        // Toggle form input email kustom
        btnToggleCustom?.addEventListener('click', function () {
            customForm.classList.toggle('hidden');
        });

        // Submit form kustom
        customForm?.addEventListener('submit', function (e) {
            e.preventDefault();
            const emailInput = document.getElementById('custom-email-input').value.trim();
            performSsoLogin(emailInput, '');
        });
    });

    // Fungsi login SSO dengan validasi domain @student.unsika.ac.id
    async function performSsoLogin(email, name) {
        const errorAlert = document.getElementById('login-error-alert');
        if (errorAlert) errorAlert.classList.add('hidden');

        // Validasi frontend domain
        if (!email.toLowerCase().endsWith('@student.unsika.ac.id')) {
            if (errorAlert) {
                errorAlert.textContent = 'Akses ditolak: Hanya akun Google dengan domain @student.unsika.ac.id yang diizinkan masuk ke RePake.';
                errorAlert.classList.remove('hidden');
            } else {
                alert('Akses ditolak: Hanya akun Google dengan domain @student.unsika.ac.id yang diizinkan masuk ke RePake.');
            }
            return;
        }

        try {
            const csrfMeta = document.querySelector('meta[name="csrf-token"]');
            const token = csrfMeta ? csrfMeta.content : '';

            const res = await fetch("{{ route('login.google') }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': token
                },
                body: JSON.stringify({ email: email, name: name })
            });

            const data = await res.json();

            if (!res.ok || !data.success) {
                throw new Error(data.message || 'Gagal masuk.');
            }

            // Berhasil login -> reload halaman atau redirect ke beranda
            window.location.reload();
        } catch (err) {
            if (errorAlert) {
                errorAlert.textContent = err.message;
                errorAlert.classList.remove('hidden');
            } else {
                alert(err.message);
            }
        }
    }
</script>

