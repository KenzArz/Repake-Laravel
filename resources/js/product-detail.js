export function initProductDetail() {
    initDescriptionToggle();
    initGallerySwitcher();
    initCopyCod();
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
            "inline font-bold text-neutral-900 hover:underline cursor-pointer";
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

function initGallerySwitcher() {
    const mainImg = document.getElementById("main-product-img");
    const buttons = document.querySelectorAll(".thumbnail-btn");
    if (!mainImg || buttons.length === 0) return;

    buttons.forEach((btn) => {
        btn.addEventListener("click", () => {
            const src = btn.dataset.imgSrc;
            if (!src) return;

            mainImg.src = src;
            buttons.forEach((b) => {
                b.classList.remove(
                    "border-amber-400",
                    "ring-2",
                    "ring-amber-400/50",
                );
                b.classList.add("border-white/80");
            });
            btn.classList.add(
                "border-amber-400",
                "ring-2",
                "ring-amber-400/50",
            );
            btn.classList.remove("border-white/80");
        });
    });
}

function initCopyCod() {
    document.querySelectorAll("[data-copy-cod]").forEach((btn) => {
        btn.addEventListener("click", async () => {
            const text = btn.dataset.copyCod;
            if (!text || !navigator.clipboard) return;

            await navigator.clipboard.writeText(text);
            alert("Lokasi COD berhasil disalin!");
        });
    });
}
