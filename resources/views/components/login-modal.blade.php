{{-- Pop-up Modal Login Firebase Google UNSIKA [ALE] --}}
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

        {{-- Tampilan Utama Sesuai Gambar Wireframe --}}
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

            {{-- Pesan error login --}}
            <div id="login-error-alert" class="hidden mt-4 rounded-xl border border-red-200 bg-red-50 p-2.5 text-xs font-medium text-red-700 leading-tight">
            </div>

            {{-- Tombol Continue with Google --}}
            <button type="button" id="btn-continue-google"
                    class="mt-6 flex w-full items-center justify-center gap-3 rounded-full border border-neutral-300 bg-white px-4 py-2.5 text-xs font-bold text-neutral-800 shadow-2xs transition hover:border-neutral-400 hover:bg-neutral-50 active:scale-[0.98] disabled:cursor-not-allowed disabled:opacity-60">
                {{-- Google G Multi-Color SVG --}}
                <svg class="size-4 shrink-0" viewBox="0 0 24 24">
                    <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                    <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                    <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.06H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.94l2.85-2.22.81-.63z"/>
                    <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.06l3.66 2.84c.87-2.6 3.3-4.52 6.16-4.52z"/>
                </svg>
                <span id="btn-continue-google-label">Continue with Google</span>
            </button>

            {{-- Footer T&C Note --}}
            <p class="mt-6 text-[11px] font-medium leading-relaxed text-neutral-400">
                Dengan masuk, Anda menyetujui<br>
                <a href="#" class="underline hover:text-neutral-600">Syarat dan Ketentuan RePake</a>
            </p>
        </div>

    </div>
</div>

@push('scripts')
@php
    $firebaseConfig = [
        'apiKey' => config('services.firebase.api_key'),
        'authDomain' => config('services.firebase.auth_domain'),
        'projectId' => config('services.firebase.project_id'),
        'appId' => config('services.firebase.app_id'),
    ];
@endphp
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const REDIRECT_KEY = 'repake-firebase-redirect';
        const modal = document.getElementById('login-modal');
        const btnContinue = document.getElementById('btn-continue-google');
        const btnLabel = document.getElementById('btn-continue-google-label');
        const errorAlert = document.getElementById('login-error-alert');
        const firebaseConfig = @json($firebaseConfig);

        let authInstance = null;
        let authApi = null;

        function openModal() {
            modal.classList.remove('hidden');
            if (errorAlert) errorAlert.classList.add('hidden');
        }

        function closeModal() {
            modal.classList.add('hidden');
        }

        function showError(message) {
            if (!errorAlert) return;
            errorAlert.textContent = message;
            errorAlert.classList.remove('hidden');
        }

        function setLoading(isLoading) {
            if (!btnContinue) return;
            btnContinue.disabled = isLoading;
            if (btnLabel) btnLabel.textContent = isLoading ? 'Menghubungkan ke Google...' : 'Continue with Google';
        }

        async function initFirebase() {
            if (authInstance) return;

            const [appModule, authModule] = await window.repakeFirebase();

            authInstance = authModule.getAuth(appModule.initializeApp(firebaseConfig));
            authApi = authModule;
        }

        async function submitToken(user) {
            const idToken = await user.getIdToken();
            const csrf = document.querySelector('meta[name="csrf-token"]');
            const response = await fetch("{{ route('auth.firebase') }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrf ? csrf.content : ''
                },
                body: JSON.stringify({ id_token: idToken })
            });

            const data = await response.json().catch(() => ({}));

            if (!response.ok || !data.success) {
                throw new Error(data.message || 'Gagal masuk. Silakan coba lagi.');
            }

            window.location = data.redirect;
        }

        // Buka saat event 'open-login' dipicu
        window.addEventListener('open-login', openModal);

        // Tutup saat tombol X diklik
        document.querySelectorAll('[data-close-login]').forEach(function (button) {
            button.addEventListener('click', closeModal);
        });

        // Tutup jika klik di area luar modal
        modal.addEventListener('click', function (event) {
            if (event.target === modal) closeModal();
        });

        // Lanjutkan login setelah fallback redirect kembali ke halaman ini
        if (sessionStorage.getItem(REDIRECT_KEY) === '1') {
            sessionStorage.removeItem(REDIRECT_KEY);

            initFirebase()
                .then(() => authApi.getRedirectResult(authInstance))
                .then((result) => (result && result.user ? submitToken(result.user) : null))
                .catch((error) => showError(error.message || 'Gagal masuk. Silakan coba lagi.'));
        }

        btnContinue?.addEventListener('click', async function () {
            if (errorAlert) errorAlert.classList.add('hidden');
            setLoading(true);

            try {
                await initFirebase();

                const provider = new authApi.GoogleAuthProvider();
                // Tanpa `hd`, semua akun Google tampil di pemilih akun.
                // Akun non-UNSIKA tetap ditolak di server (FirebaseAuthController).

                let user;

                try {
                    const result = await authApi.signInWithPopup(authInstance, provider);
                    user = result.user;
                } catch (error) {
                    if (['auth/popup-blocked', 'auth/operation-not-supported-in-this-environment'].includes(error.code)) {
                        sessionStorage.setItem(REDIRECT_KEY, '1');
                        await authApi.signInWithRedirect(authInstance, provider);
                        return;
                    }

                    if (error.code === 'auth/popup-closed-by-user') {
                        showError('Login dibatalkan.');
                        return;
                    }

                    throw error;
                }

                await submitToken(user);
            } catch (error) {
                showError(error.message || 'Gagal masuk. Silakan coba lagi.');
            } finally {
                setLoading(false);
            }
        });
    });
</script>
@endpush
