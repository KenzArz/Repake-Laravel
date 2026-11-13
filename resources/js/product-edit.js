const MAX_FILES = 3;
const MAX_BYTES = 5 * 1024 * 1024;
const MAX_TOTAL_BYTES = 6 * 1024 * 1024;
const SAFE_UPLOAD_BYTES = 2 * 1024 * 1024;
const ALLOWED_TYPES = ["image/jpeg", "image/png", "image/webp"];
const ALLOWED_TYPES_MESSAGE = "Format gambar harus JPG, PNG, atau WebP.";

const FIELD_IDS = [
    "judul",
    "category_id",
    "harga",
    "deskripsi",
    "kota",
    "patokan_lokasi",
];

/** @type {Array<{ type: 'existing', id: number, url: string } | { type: 'new', file: File, url: string }>} */
const items = [];
/** @type {Array<{ type: 'existing', id: number, url: string }>} */
const deletedExisting = [];
const els = {};

function showError(message) {
    els.errorMessage.textContent = message;
    els.errorContainer.classList.remove("hidden");
}

function hideError() {
    els.errorContainer.classList.add("hidden");
    els.errorMessage.textContent = "";
}

function showFormError(message) {
    els.formError.textContent = message;
    els.formError.classList.toggle("hidden", !message);
}

function setFieldError(name, message) {
    const el = document.getElementById("err-" + name);

    if (el) {
        el.textContent = message;
        el.classList.toggle("hidden", !message);
    }
}

function textValue(id) {
    const el = document.getElementById(id);

    return el ? el.value.trim() : "";
}

const validators = {
    judul: () => {
        const value = textValue("judul");
        if (!value) return "Nama produk wajib diisi.";
        if (value.length > 255) return "Nama produk maksimal 255 karakter.";
        return "";
    },
    category_id: () =>
        textValue("category_id") ? "" : "Pilih kategori produk.",
    harga: () => {
        const el = document.getElementById("harga");
        if (el.value === "" || el.value === null) return "Harga wajib diisi.";
        const amount = Number(el.value);
        if (!Number.isInteger(amount)) return "Harga harus angka bulat.";
        if (amount < 0) return "Harga minimal 0 (Rp 0).";
        return "";
    },
    deskripsi: () => {
        const value = textValue("deskripsi");
        if (!value) return "Deskripsi wajib diisi.";
        if (value.length < 10) return "Deskripsi minimal 10 karakter.";
        return "";
    },
    kota: () => {
        const value = textValue("kota");
        if (!value) return "Kota / area produk wajib diisi.";
        if (value.length > 255) return "Kota maksimal 255 karakter.";
        return "";
    },
    patokan_lokasi: () => {
        const value = textValue("patokan_lokasi");
        if (value.length > 255) return "Patokan lokasi maksimal 255 karakter.";
        return "";
    },
};

function validateImages() {
    if (items.length === 0) return "Unggah minimal 1 gambar produk.";
    if (items.length > MAX_FILES) return `Maksimal ${MAX_FILES} gambar produk.`;

    let totalBytes = 0;

    for (const entry of items) {
        if (entry.type !== "new") continue;
        if (!ALLOWED_TYPES.includes(entry.file.type))
            return ALLOWED_TYPES_MESSAGE;
        if (entry.file.size > MAX_BYTES)
            return `Gambar "${entry.file.name}" melebihi 5MB.`;
        totalBytes += entry.file.size;
    }

    if (totalBytes > MAX_TOTAL_BYTES) {
        return "Total ukuran gambar terlalu besar. Kurangi jumlah atau ukuran gambar (maksimal total 6MB).";
    }

    return "";
}

function validateAll() {
    let first = null;

    FIELD_IDS.forEach((name) => {
        const message = validators[name]();
        setFieldError(name, message);
        if (message && first === null) first = name;
    });

    const imageError = validateImages();

    if (imageError) {
        showError(imageError);
        if (first === null) first = "images";
    } else {
        hideError();
    }

    return first;
}

function focusInvalid(name) {
    if (name === "images") {
        els.dropzone.scrollIntoView({ behavior: "smooth", block: "center" });
        return;
    }

    const el = document.getElementById(name);

    if (el) {
        el.scrollIntoView({ behavior: "smooth", block: "center" });
        el.focus({ preventScroll: true });
    }
}

function compressImage(file, quality = 0.7, maxSide = 1920) {
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
            const context = canvas.getContext("2d");
            context.fillStyle = "#ffffff";
            context.fillRect(0, 0, canvas.width, canvas.height);
            context.drawImage(img, 0, 0, width, height);

            canvas.toBlob(
                (blob) => {
                    URL.revokeObjectURL(objectUrl);

                    if (!blob) {
                        reject(new Error(failedMessage));
                        return;
                    }

                    resolve(
                        new File(
                            [blob],
                            file.name.replace(/\.[^./\\]+$/, "") + ".jpg",
                            { type: "image/jpeg" },
                        ),
                    );
                },
                "image/jpeg",
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

async function prepareFiles(accepted) {
    return Promise.all(
        accepted.map(async (file) => {
            const compressed = await compressImage(file, 0.7);

            return compressed.size > SAFE_UPLOAD_BYTES
                ? compressImage(file, 0.4)
                : compressed;
        }),
    );
}

function syncRestoreButton() {
    if (!els.restoreBtn) return;

    const canRestore = deletedExisting.length > 0 && items.length < MAX_FILES;
    els.restoreBtn.classList.toggle("hidden", !canRestore);
    if (canRestore) {
        els.restoreBtn.textContent = `Pulihkan foto (${deletedExisting.length})`;
    }
}

function renderPreviews() {
    els.previewContainer.innerHTML = "";
    const transfer = new DataTransfer();

    items.forEach((entry, index) => {
        if (entry.type === "new") {
            transfer.items.add(entry.file);
        }

        const isCover = index === 0;
        const card = document.createElement("div");
        card.className = `relative aspect-square overflow-hidden rounded-2xl border-2 ${isCover ? "border-[var(--color-lime)] ring-2 ring-[var(--color-lime)]/40 shadow-sm" : "border-[var(--color-border-default)]"} bg-neutral-100 group cursor-grab active:cursor-grabbing transition-all duration-150`;
        card.draggable = true;
        card.dataset.index = String(index);

        card.innerHTML = `
            <img src="${entry.url}" alt="Preview ${index + 1}" class="h-full w-full object-cover pointer-events-none select-none">
            <button type="button" data-remove-index="${index}"
                    class="absolute top-1.5 right-1.5 flex h-6 w-6 items-center justify-center rounded-full bg-black/60 text-white transition hover:bg-red-600 z-10 pointer-events-auto cursor-pointer">
                &times;
            </button>
            ${
                isCover
                    ? `<div class="absolute inset-x-0 bottom-0 bg-[var(--color-lime)] py-1 text-center text-[10px] font-extrabold text-[var(--color-charcoal)] shadow-sm pointer-events-none select-none">★ COVER UTAMA</div>`
                    : `<div class="absolute inset-x-0 bottom-0 bg-black/40 py-0.5 text-center text-[9px] font-semibold text-white pointer-events-none select-none">Foto ${index + 1}</div>`
            }
        `;

        card.addEventListener("dragstart", (event) => {
            event.dataTransfer.setData("text/plain", String(index));
            event.dataTransfer.effectAllowed = "move";
            card.classList.add("opacity-40", "scale-95");
        });

        card.addEventListener("dragover", (event) => {
            event.preventDefault();
            event.dataTransfer.dropEffect = "move";
            card.classList.add("scale-105", "border-amber-400");
        });

        card.addEventListener("dragleave", () => {
            card.classList.remove("scale-105", "border-amber-400");
        });

        card.addEventListener("drop", (event) => {
            event.preventDefault();
            event.stopPropagation();
            card.classList.remove("scale-105", "border-amber-400");

            const from = Number.parseInt(
                event.dataTransfer.getData("text/plain"),
                10,
            );

            if (!Number.isNaN(from) && from !== index) {
                const [moved] = items.splice(from, 1);
                items.splice(index, 0, moved);
                renderPreviews();
            }
        });

        card.addEventListener("dragend", () => {
            card.classList.remove("opacity-40", "scale-95");
        });

        els.previewContainer.appendChild(card);
    });

    els.imageInput.files = transfer.files;
    syncRestoreButton();
}

function buildFormData() {
    const data = new FormData();

    for (const el of els.form.querySelectorAll("input, textarea, select")) {
        if (!el.name || el.name === "images[]") continue;

        if (el.type === "radio") {
            if (el.checked) data.append(el.name, el.value);
        } else {
            data.append(el.name, el.value);
        }
    }

    deletedExisting.forEach((entry) => {
        data.append("delete_images[]", String(entry.id));
    });

    let newIndex = 0;
    items.forEach((entry) => {
        if (entry.type === "existing") {
            data.append("image_order[]", `existing:${entry.id}`);
        } else if (entry.type === "new") {
            data.append("image_order[]", `new:${newIndex}`);
            data.append("images[]", entry.file, entry.file.name);
            newIndex += 1;
        }
    });

    return data;
}

function handleResponse(response) {
    if (response.status === 419) {
        throw {
            userMessage:
                "Sesi Anda sudah berakhir. Muat ulang halaman, lalu unggah ulang gambar dan isi form.",
        };
    }

    if (response.status === 413) {
        showError("File terlalu besar. Maksimal 5MB per gambar.");
        throw { generic: true };
    }

    if (response.status === 422) {
        return response.json().then((data) => {
            throw { validation: data.errors || {} };
        });
    }

    if (response.ok) {
        return response.json().then((data) => {
            window.location.assign(data?.redirect || els.form.action);
        });
    }

    throw { generic: true };
}

function handleError(error) {
    els.submitBtn.disabled = false;

    if (error?.userMessage) {
        showFormError(error.userMessage);
        return;
    }

    if (error?.validation) {
        let firstField = null;

        Object.entries(error.validation).forEach(([key, messages]) => {
            const message = Array.isArray(messages) ? messages[0] : messages;

            if (key.startsWith("images")) {
                showError(message);
            } else {
                setFieldError(key, message);
            }

            if (firstField === null) firstField = key;
        });

        focusInvalid(firstField);
        return;
    }

    showFormError(
        "Terjadi kesalahan saat menyimpan perubahan. Silakan coba lagi.",
    );
}

function handleImageSelection(event) {
    hideError();

    const accepted = [];
    let rejected = "";

    Array.from(event.target.files || []).forEach((file) => {
        if (!ALLOWED_TYPES.includes(file.type)) {
            rejected = ALLOWED_TYPES_MESSAGE;
            return;
        }
        if (file.size > MAX_BYTES) {
            rejected = `Gambar "${file.name}" melebihi 5MB.`;
            return;
        }
        accepted.push(file);
    });

    if (items.length + accepted.length > MAX_FILES) {
        showError(`Maksimal hanya dapat memilih ${MAX_FILES} gambar produk.`);
        els.imageInput.value = "";
        return;
    }

    if (accepted.length === 0) {
        if (rejected) showError(rejected);
        return;
    }

    prepareFiles(accepted)
        .then((prepared) => {
            const slots = MAX_FILES - items.length;
            prepared.slice(0, slots).forEach((file) => {
                items.push({
                    type: "new",
                    file,
                    url: URL.createObjectURL(file),
                });
            });
            renderPreviews();
            if (rejected) showError(rejected);
        })
        .catch((error) =>
            showError(
                error?.message || "Gagal memproses gambar. Coba gambar lain.",
            ),
        );
}

function handleSubmit(event) {
    event.preventDefault();
    hideError();
    showFormError("");

    const locationInput = document.getElementById("location_search");
    const kotaInput = document.getElementById("kota");
    if (locationInput && kotaInput && !kotaInput.value.trim()) {
        kotaInput.value = locationInput.value.trim();
    }

    const first = validateAll();

    if (first) {
        focusInvalid(first);
        return;
    }

    els.submitBtn.disabled = true;

    fetch(els.form.action, {
        method: "POST",
        body: buildFormData(),
        headers: {
            "X-Requested-With": "XMLHttpRequest",
            Accept: "application/json",
        },
        credentials: "same-origin",
    })
        .then(handleResponse)
        .catch(handleError);
}

export function initProductEdit() {
    els.form = document.getElementById("productEditForm");

    if (!els.form) return;

    els.dropzone = document.getElementById("dropzone");
    els.imageInput = document.getElementById("imageInput");
    els.previewContainer = document.getElementById("previewContainer");
    els.errorContainer = document.getElementById("imageErrorContainer");
    els.errorMessage = document.getElementById("imageErrorMessage");
    els.closeErrorBtn = document.getElementById("closeErrorBtn");
    els.restoreBtn = document.getElementById("restoreDeletedBtn");
    els.formError = document.getElementById("formError");
    els.submitBtn = els.form.querySelector('button[type="submit"]');

    // Muat foto lama dari atribut data-existing-images
    items.length = 0;
    deletedExisting.length = 0;
    try {
        const existing = JSON.parse(
            els.previewContainer.dataset.existingImages || "[]",
        );
        existing.forEach((img) => {
            items.push({ type: "existing", id: Number(img.id), url: img.url });
        });
    } catch {
        // Abaikan jika JSON kosong
    }

    renderPreviews();

    els.closeErrorBtn.addEventListener("click", hideError);
    els.dropzone.addEventListener("click", (event) => {
        if (event.target === els.imageInput) return;

        els.imageInput.click();
    });

    els.dropzone.addEventListener("dragover", (event) => {
        event.preventDefault();
        els.dropzone.classList.add(
            "border-[var(--color-lime)]",
            "bg-[var(--color-lime-100)]",
        );
    });

    els.dropzone.addEventListener("dragleave", () => {
        els.dropzone.classList.remove(
            "border-[var(--color-lime)]",
            "bg-[var(--color-lime-100)]",
        );
    });

    els.dropzone.addEventListener("drop", (event) => {
        event.preventDefault();
        els.dropzone.classList.remove(
            "border-[var(--color-lime)]",
            "bg-[var(--color-lime-100)]",
        );
        els.imageInput.files = event.dataTransfer.files;
        els.imageInput.dispatchEvent(new Event("change", { bubbles: true }));
    });

    els.imageInput.addEventListener("change", handleImageSelection);

    els.previewContainer.addEventListener("click", (event) => {
        const button = event.target.closest("[data-remove-index]");

        if (!button) return;

        hideError();
        const [removed] = items.splice(Number(button.dataset.removeIndex), 1);
        if (removed?.type === "existing") {
            deletedExisting.push(removed);
        }
        renderPreviews();
    });

    els.restoreBtn?.addEventListener("click", () => {
        if (deletedExisting.length === 0 || items.length >= MAX_FILES) return;
        hideError();
        items.push(deletedExisting.pop());
        renderPreviews();
    });

    // const judulInput = document.getElementById("judul");
    // const headerTitle = document.getElementById("headerProductTitle");
    // judulInput?.addEventListener("input", () => {
    //     if (headerTitle) {
    //         headerTitle.textContent = judulInput.value.trim() || "Produk";
    //     }
    // });

    const locationInput = document.getElementById("location_search");
    const kotaInput = document.getElementById("kota");
    locationInput?.addEventListener("input", () => {
        if (kotaInput) {
            kotaInput.value = locationInput.value.trim();
        }
    });

    FIELD_IDS.forEach((name) => {
        const el = document.getElementById(name);

        if (!el) return;

        el.addEventListener("input", () =>
            setFieldError(name, validators[name]()),
        );
        el.addEventListener("change", () =>
            setFieldError(name, validators[name]()),
        );
    });

    els.form.addEventListener("submit", handleSubmit);
}
