import "./bootstrap";

/* Tombol/link yang butuh login (guest) -> buka login modal.
   login-modal.blade.php (ALE) tinggal listen event 'open-login'. */
document.addEventListener("click", (e) => {
    const trigger = e.target.closest("[data-open-login]");
    if (!trigger) return;
    e.preventDefault();
    window.dispatchEvent(new CustomEvent("open-login"));
});

/* Toggle favorit (user yang sudah login) */
document.addEventListener("click", async (e) => {
    const btn = e.target.closest("[data-favorite-toggle]");
    if (!btn) return;
    e.preventDefault();

    const was = btn.getAttribute("aria-pressed") === "true";
    btn.setAttribute("aria-pressed", String(!was)); // optimistic update

    const url = btn.dataset.url;
    if (!url) return; // route belum ada, cukup visual dulu

    try {
        const res = await fetch(url, {
            method: "POST",
            headers: {
                "X-CSRF-TOKEN": document.querySelector(
                    'meta[name="csrf-token"]',
                ).content,
                Accept: "application/json",
            },
        });
        if (!res.ok) throw new Error();
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
