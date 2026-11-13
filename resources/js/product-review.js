/**
 * Interaksi ulasan produk (halaman detail produk):
 * - Input rating bintang interaktif (mengisi hidden input `name="rating"`).
 * - Toggle menu 3 titik untuk mengelola ulasan milik pengunjung.
 * - Toggle panel edit ulasan.
 */
export function initProductReviews() {
    document.querySelectorAll("[data-rating-input]").forEach((root) => {
        const input = root.querySelector('input[name="rating"]');
        if (!input) return;

        const stars = root.querySelectorAll("[data-rating-star]");

        const paint = (value) => {
            stars.forEach((star) => {
                const active = Number(star.dataset.ratingStar) <= Number(value);
                star.classList.toggle("text-amber-400", active);
                star.classList.toggle("text-neutral-300", !active);
            });
        };

        stars.forEach((star) => {
            star.addEventListener("click", () => {
                input.value = star.dataset.ratingStar;
                paint(input.value);
            });
            star.addEventListener("mouseenter", () => paint(star.dataset.ratingStar));
        });

        root.addEventListener("mouseleave", () => paint(input.value));

        // Prefill (mis. rating lama pada panel edit).
        if (input.value) paint(input.value);
    });

    // Menu 3 titik kelola ulasan.
    document.querySelectorAll("[data-review-menu-toggle]").forEach((toggle) => {
        const root = toggle.closest("[data-review-menu-root]");
        const menu = root.querySelector("[data-review-menu]");

        const close = () => {
            menu.classList.add("hidden");
            toggle.setAttribute("aria-expanded", "false");
        };

        toggle.addEventListener("click", (e) => {
            e.stopPropagation();
            const open = menu.classList.toggle("hidden") === false;
            toggle.setAttribute("aria-expanded", String(open));
        });

        document.addEventListener("click", (e) => {
            if (!root.contains(e.target)) close();
        });
    });

    // Buka panel edit ulasan.
    document.querySelectorAll("[data-edit-review-toggle]").forEach((btn) => {
        btn.addEventListener("click", () => {
            const form = btn.closest("[data-review-card]").querySelector("[data-edit-review-form]");
            form.hidden = !form.hidden;
        });
    });

    // Tutup panel edit ulasan.
    document.querySelectorAll("[data-edit-review-cancel]").forEach((btn) => {
        btn.addEventListener("click", () => {
            const form = btn.closest("[data-review-card]").querySelector("[data-edit-review-form]");
            form.hidden = true;
        });
    });
}