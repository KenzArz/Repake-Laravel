import { initProductReviews } from "./product-review";

export function initProductDetail() {
    initDescriptionToggle();
    initGalleryAndLightbox();
    initCopyCod();
    initProductReviews();
}

function initDescriptionToggle() {
    const el = document.getElementById("product-desc");
    if (!el) return;

    const fullText = (el.dataset.fullText || "").trim();
    const INITIAL_LIMIT = 140;
    const STEP_LIMIT = 350;

    if (fullText.length <= INITIAL_LIMIT) return;

    let currentLimit = INITIAL_LIMIT;

    const render = () => {
        el.textContent = "";

        const isTruncated = fullText.length - currentLimit > 60;
        const displayedText = isTruncated
            ? `${fullText.slice(0, currentLimit).trimEnd()}... `
            : `${fullText} `;

        el.appendChild(document.createTextNode(displayedText));

        const btn = document.createElement("button");
        btn.type = "button";
        btn.className =
            "inline cursor-pointer font-bold text-neutral-900 hover:underline";
        btn.textContent = isTruncated ? "baca selengkapnya" : "sembunyikan";

        btn.addEventListener("click", () => {
            currentLimit = isTruncated
                ? currentLimit + STEP_LIMIT
                : INITIAL_LIMIT;
            render();
        });

        el.appendChild(btn);
    };

    render();
}

function initGalleryAndLightbox() {
    const galleryContainer = document.getElementById("product-gallery");
    const mainImg = document.getElementById("main-product-img");
    const buttons = document.querySelectorAll(".thumbnail-btn");
    const fullscreenBtn = document.getElementById("btn-fullscreen-img");

    const lightbox = document.getElementById("image-lightbox");
    const lightboxImg = document.getElementById("lightbox-img");
    const lightboxCounter = document.getElementById("lightbox-counter");
    const closeBtn = document.getElementById("lightbox-close");
    const prevBtn = document.getElementById("lightbox-prev");
    const nextBtn = document.getElementById("lightbox-next");

    if (!mainImg) return;

    let images = [];
    try {
        images = JSON.parse(galleryContainer?.dataset.gallery || "[]");
    } catch {
        images = [];
    }
    if (images.length === 0) {
        images = [mainImg.src];
    }

    let activeIndex = 0;

    const setActiveImage = (index) => {
        activeIndex = (index + images.length) % images.length;
        const src = images[activeIndex];

        mainImg.src = src;
        if (lightboxImg) lightboxImg.src = src;
        if (lightboxCounter)
            lightboxCounter.textContent = `${activeIndex + 1} / ${images.length}`;

        buttons.forEach((b, idx) => {
            const isSelected = idx === activeIndex;
            b.classList.toggle("border-amber-400", isSelected);
            b.classList.toggle("ring-2", isSelected);
            b.classList.toggle("ring-amber-400/50", isSelected);
            b.classList.toggle("border-white/80", !isSelected);
        });
    };

    buttons.forEach((btn, idx) => {
        btn.addEventListener("click", (e) => {
            e.stopPropagation();
            setActiveImage(idx);
        });
    });

    if (!lightbox || !lightboxImg) return;

    const openLightbox = () => {
        lightboxImg.src = images[activeIndex];
        if (lightboxCounter)
            lightboxCounter.textContent = `${activeIndex + 1} / ${images.length}`;
        lightbox.classList.remove("hidden");
        lightbox.classList.add("flex");
        document.body.classList.add("overflow-hidden");
    };

    const closeLightbox = () => {
        lightbox.classList.add("hidden");
        lightbox.classList.remove("flex");
        document.body.classList.remove("overflow-hidden");
    };

    mainImg.addEventListener("click", openLightbox);
    fullscreenBtn?.addEventListener("click", (e) => {
        e.stopPropagation();
        openLightbox();
    });

    closeBtn?.addEventListener("click", closeLightbox);
    prevBtn?.addEventListener("click", () => setActiveImage(activeIndex - 1));
    nextBtn?.addEventListener("click", () => setActiveImage(activeIndex + 1));

    // Tutup jika klik area gelap di luar gambar
    lightbox.addEventListener("click", (e) => {
        if (e.target === lightbox) {
            closeLightbox();
        }
    });

    // Navigasi keyboard saat fullscreen terbuka
    document.addEventListener("keydown", (e) => {
        if (lightbox.classList.contains("hidden")) return;

        if (e.key === "Escape") closeLightbox();
        if (e.key === "ArrowLeft" && images.length > 1)
            setActiveImage(activeIndex - 1);
        if (e.key === "ArrowRight" && images.length > 1)
            setActiveImage(activeIndex + 1);
    });
}

function initCopyCod() {
    document.querySelectorAll("[data-copy-cod]").forEach((btn) => {
        let resetTimer = null;

        btn.addEventListener("click", async () => {
            const text = btn.dataset.copyCod;
            if (!text) return;

            try {
                if (navigator.clipboard?.writeText) {
                    await navigator.clipboard.writeText(text);
                } else {
                    const temp = document.createElement("textarea");
                    temp.value = text;
                    document.body.appendChild(temp);
                    temp.select();
                    document.execCommand("copy");
                    temp.remove();
                }

                const copyIcon = btn.querySelector("[data-copy-icon]");
                const checkIcon = btn.querySelector("[data-check-icon]");
                const label = btn.querySelector("[data-copy-label]");

                copyIcon?.classList.add("hidden");
                checkIcon?.classList.remove("hidden");
                if (label) label.textContent = "Tersalin";

                clearTimeout(resetTimer);
                resetTimer = setTimeout(() => {
                    copyIcon?.classList.remove("hidden");
                    checkIcon?.classList.add("hidden");
                    if (label) label.textContent = "Salin";
                }, 2000);
            } catch {
                // Abaikan jika izin clipboard diblokir browser
            }
        });
    });
}
