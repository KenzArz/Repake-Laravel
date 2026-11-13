import "./bootstrap";
import Alpine from "alpinejs";
import { initLocationDetect } from "./location-detect";

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

import productFilter from "./product-filter";

import { initNavbarLocation } from "./navbar-location";

// ... di bagian inisialisasi:
initLocationDetect();
initNavbarLocation();

/* Toggle favorit */
document.addEventListener("click", async (e) => {
    const btn = e.target.closest("[data-favorite-toggle]");
    if (!btn) return;
    e.preventDefault();

    // Biarkan listener [data-open-login] membuka modal login tanpa mengubah status favorit
    if (btn.hasAttribute("data-open-login")) return;

    const was = btn.getAttribute("aria-pressed") === "true";
    const nextState = !was;

    // 1. Siapkan elemen counter di dalam tombol produk (halaman detail)
    const productCountEl = btn.querySelector("[data-favorite-count]");
    const initialCount = productCountEl
        ? parseInt(productCountEl.dataset.count || "0", 10)
        : 0;

    const setProductCount = (val) => {
        if (!productCountEl) return;
        const safeCount = Math.max(0, val);
        productCountEl.dataset.count = String(safeCount);
        productCountEl.textContent = String(safeCount);
        productCountEl.classList.toggle("hidden", safeCount <= 0);
    };

    // Optimistic update status & angka
    btn.setAttribute("aria-pressed", String(nextState));
    setProductCount(initialCount + (nextState ? 1 : -1));

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
        const finalState =
            typeof data.is_favorite === "boolean"
                ? data.is_favorite
                : nextState;

        btn.setAttribute("aria-pressed", String(finalState));

        // 2. Sinkronkan jumlah favorit produk di tombol detail
        if (typeof data.favorites_count === "number") {
            setProductCount(data.favorites_count);
        } else {
            setProductCount(initialCount + (finalState ? 1 : -1));
        }

        // Update total favorit user di halaman /favorites (tetap dipertahankan)
        const pageCountEl = document.getElementById("favorites-count");
        if (pageCountEl && typeof data.count === "number") {
            pageCountEl.textContent = data.count;
        }

        const favCard = btn.closest("[data-favorite-card]");
        if (favCard && finalState === false) {
            favCard.style.transition =
                "opacity 0.25s ease, transform 0.25s ease";
            favCard.style.opacity = "0.4";
        } else if (favCard && finalState === true) {
            favCard.style.opacity = "1";
        }
    } catch {
        // 3. Rollback status & angka jika gagal
        btn.setAttribute("aria-pressed", String(was));
        setProductCount(initialCount);
    }
});

/* Autoplay banner (setara Timer 4 detik di Flutter) */
document.querySelectorAll("[data-banner-slider]").forEach((el) => {
    const items = [...el.querySelectorAll("[data-banner-slide]")];
    if (items.length < 2) return;

    const offsetOf = (item) =>
        item.offsetLeft - items[0].offsetLeft;

    let paused = false;

    const advance = () => {
        if (paused || document.hidden) return;

        const maxScroll = el.scrollWidth - el.clientWidth;
        if (maxScroll <= 1) return; // semuanya sudah muat di layar

        const positions = items.map(offsetOf);
        // Banner berikutnya: slide pertama yang mulai melewati posisi scroll aktif.
        let next = positions.findIndex((left) => left > el.scrollLeft + 2);
        // Di ujung / tidak ada lagi yang bisa digeser -> balik ke awal (loop).
        if (next === -1 || positions[next] > maxScroll + 2) next = 0;

        el.scrollTo({ left: next === 0 ? 0 : positions[next], behavior: "smooth" });
    };

    el.addEventListener("mouseenter", () => (paused = true));
    el.addEventListener("mouseleave", () => (paused = false));
    el.addEventListener("focusin", () => (paused = true));
    el.addEventListener("focusout", () => (paused = false));

    // Hormati preferensi aksesibilitas pengurangan gerakan.
    if (window.matchMedia?.("(prefers-reduced-motion: reduce)").matches) return;

    setInterval(advance, 4000);
});

/* Deteksi kota [ALDI] — hanya aktif bila ada pemicunya di halaman */
initLocationDetect();

/* Form jual barang — hanya dimuat bila halamannya memang punya form tersebut */
if (document.getElementById("productForm")) {
    import("./product-create").then((module) => module.initProductCreate());
    import("./product-location").then((module) => module.initProductLocation());
}

/* Form edit barang — hanya dimuat di halaman edit produk */
if (document.getElementById("productEditForm")) {
    import("./product-edit").then((module) => module.initProductEdit());
    import("./product-location").then((module) => module.initProductLocation());
}

/* Detail produk — hanya dimuat di halaman detail produk */
if (
    document.getElementById("product-desc") ||
    document.getElementById("main-product-img")
) {
    import("./product-detail").then((module) => module.initProductDetail());
}

/* Ulasan (detail produk & profil) — dimuat bila halaman punya elemen ulasan */
if (
    document.querySelector("[data-rating-input]") ||
    document.querySelector("[data-review-menu-toggle]")
) {
    import("./product-review").then((module) => module.initProductReviews());
}

/* Chat — sidebar, ruang chat, dan badge pesan belum dibaca di navbar.
   Badge dibutuhkan di semua halaman, jadi modul ini dimuat untuk user login. */
if (
    document.body.dataset.authed === "1" ||
    document.getElementById("chat-app") ||
    document.querySelector("[data-conversation-list]")
) {
    import("./chat").then((module) => {
        module.initChatSidebar();
        module.initChat();
        module.initChatBadge();
    });
}

window.Alpine = Alpine;
Alpine.data("productFilter", productFilter);
Alpine.start();
