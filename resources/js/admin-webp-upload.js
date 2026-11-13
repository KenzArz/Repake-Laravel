const ALLOWED_TYPES = ["image/jpeg", "image/png", "image/webp"];

const PRESETS = {
    banner: {
        maxSide: 1600,
        quality: 0.82,
        fallbackQuality: 0.65,
        safeBytes: 1.5 * 1024 * 1024, // Dijaga di bawah batas 2MB BannerController
    },
    category: {
        maxSide: 256,
        quality: 0.88,
        fallbackQuality: 0.72,
        safeBytes: 512 * 1024,
    },
};

function convertToWebp(file, quality, maxSide) {
    return new Promise((resolve, reject) => {
        const img = new Image();
        const objectUrl = URL.createObjectURL(file);
        const failedMessage = `Gambar "${file.name}" rusak atau formatnya tidak didukung.`;

        img.onload = () => {
            let width = img.width;
            let height = img.height;

            if (width > maxSide) {
                height = (maxSide / width) * height;
                width = maxSide;
            }
            if (height > maxSide) {
                width = (maxSide / height) * width;
                height = maxSide;
            }

            const canvas = document.createElement("canvas");
            canvas.width = Math.round(width);
            canvas.height = Math.round(height);

            // Jangan isi background putih agar transparansi PNG tetap terjaga di WebP
            const context = canvas.getContext("2d");
            context.clearRect(0, 0, canvas.width, canvas.height);
            context.drawImage(img, 0, 0, canvas.width, canvas.height);

            canvas.toBlob(
                (blob) => {
                    URL.revokeObjectURL(objectUrl);

                    if (!blob) {
                        reject(new Error(failedMessage));
                        return;
                    }

                    const baseName =
                        file.name.replace(/\.[^./\\]+$/, "") || "image";

                    resolve(
                        new File([blob], `${baseName}.webp`, {
                            type: "image/webp",
                            lastModified: Date.now(),
                        }),
                    );
                },
                "image/webp",
                quality,
            );
        };

        img.onerror = () => {
            URL.revokeObjectURL(objectUrl);
            reject(new Error(failedMessage));
        };

        img.src = objectUrl;
    });
}

async function prepareWebpFile(file, preset) {
    const compressed = await convertToWebp(
        file,
        preset.quality,
        preset.maxSide,
    );

    if (compressed.size > preset.safeBytes) {
        return convertToWebp(file, preset.fallbackQuality, preset.maxSide);
    }

    return compressed;
}

function resolvePreset(input) {
    const explicit = input.dataset.webpUpload;
    if (explicit && PRESETS[explicit]) return PRESETS[explicit];
    if (input.name === "icon_file") return PRESETS.category;
    if (input.name === "image") return PRESETS.banner;
    return null;
}

function setFormBusy(form, busy) {
    if (!form) return;
    form.dataset.webpConverting = busy ? "1" : "0";
    form.querySelectorAll('button[type="submit"]').forEach((btn) => {
        btn.disabled = busy;
    });
}

export function initAdminWebpUpload() {
    document.addEventListener("change", async (event) => {
        const input = event.target;
        if (!(input instanceof HTMLInputElement) || input.type !== "file") {
            return;
        }

        // Abaikan form jual/edit produk karena sudah ditangani product-create/edit
        if (input.closest("#productForm, #productEditForm")) return;

        const preset = resolvePreset(input);
        if (!preset || !input.files?.length) return;

        const rawFiles = Array.from(input.files);
        if (rawFiles.some((file) => !ALLOWED_TYPES.includes(file.type))) {
            return;
        }

        const form = input.closest("form");
        setFormBusy(form, true);

        try {
            const converted = await Promise.all(
                rawFiles.map((file) => prepareWebpFile(file, preset)),
            );

            const transfer = new DataTransfer();
            converted.forEach((file) => transfer.items.add(file));
            input.files = transfer.files;
        } catch (error) {
            console.error(error);
        } finally {
            setFormBusy(form, false);
        }
    });

    // Cegah submit jika konversi masih berlangsung
    document.addEventListener("submit", (event) => {
        const form = event.target;
        if (
            form instanceof HTMLFormElement &&
            form.dataset.webpConverting === "1"
        ) {
            event.preventDefault();
        }
    });
}
