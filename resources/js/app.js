import "./bootstrap";

/* Firebase SDK dimuat dinamis hanya saat login modal dipakai,
   agar semua halaman tidak ikut membawa bundle Firebase. */
window.repakeFirebase = () =>
    Promise.all([import("firebase/app"), import("firebase/auth")]);


/* Tombol/link yang butuh login (guest) -> buka login modal.
   login-modal.blade.php (ALE) tinggal listen event 'open-login'. */
document.addEventListener("click", (e) => {
    const trigger = e.target.closest("[data-open-login]");
    if (!trigger) return;
    e.preventDefault();
    window.dispatchEvent(new CustomEvent("open-login"));
});

/* Toggle favorit */
document.addEventListener("click", async (e) => {
    const btn = e.target.closest("[data-favorite-toggle]");
    if (!btn) return;
    e.preventDefault();

    const was = btn.getAttribute("aria-pressed") === "true";
    const nextState = !was;
    btn.setAttribute("aria-pressed", String(nextState)); // optimistic update

    const url = btn.dataset.url;
    if (!url) return;

    try {
        const csrfMeta = document.querySelector('meta[name="csrf-token"]');
        const token = csrfMeta ? csrfMeta.content : "";

        const res = await fetch(url, {
            method: "POST",
            headers: {
                "X-CSRF-TOKEN": token,
                Accept: "application/json",
                "Content-Type": "application/json",
            },
        });
        if (!res.ok) throw new Error("Gagal mengubah status favorit");

        const data = await res.json();
        if (typeof data.is_favorite === "boolean") {
            btn.setAttribute("aria-pressed", String(data.is_favorite));
        }

        // Update jumlah favorit di halaman jika ada
        const countEl = document.getElementById("favorites-count");
        if (countEl && typeof data.count === "number") {
            countEl.textContent = data.count;
        }

        const favCard = btn.closest("[data-favorite-card]");
        if (favCard && data.is_favorite === false) {
            favCard.style.transition = "opacity 0.25s ease, transform 0.25s ease";
            favCard.style.opacity = "0.4";
        } else if (favCard && data.is_favorite === true) {
            favCard.style.opacity = "1";
        }
    } catch {
        btn.setAttribute("aria-pressed", String(was)); // rollback
    }
});

/* Autoplay banner (setara Timer 4 detik di Flutter) */
document.querySelectorAll("[data-banner-slider]").forEach((el) => {
    const items = [...el.children];
    if (items.length < 2 || el.scrollWidth <= el.clientWidth + 1) return; // muat semua, tidak perlu geser

    let i = 0;
    let paused = false;
    el.addEventListener("mouseenter", () => (paused = true));
    el.addEventListener("mouseleave", () => (paused = false));

    setInterval(() => {
        if (paused) return;
        i = (i + 1) % items.length;
        el.scrollTo({
            left: items[i].offsetLeft - el.offsetLeft,
            behavior: "smooth",
        });
    }, 4000);
});
