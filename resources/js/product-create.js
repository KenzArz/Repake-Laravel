const MAX_FILES = 3;
const MAX_BYTES = 5 * 1024 * 1024;
const SAFE_UPLOAD_BYTES = 2 * 1024 * 1024;

const FIELD_IDS = ['judul', 'category_id', 'harga', 'deskripsi', 'kota', 'patokan_lokasi'];

const files = [];
const els = {};

function showError(message) {
    els.errorMessage.textContent = message;
    els.errorContainer.classList.remove('hidden');
}

function hideError() {
    els.errorContainer.classList.add('hidden');
    els.errorMessage.textContent = '';
}

function showFormError(message) {
    els.formError.textContent = message;
    els.formError.classList.toggle('hidden', !message);
}

function setFieldError(name, message) {
    const el = document.getElementById('err-' + name);

    if (el) {
        el.textContent = message;
        el.classList.toggle('hidden', !message);
    }
}

function textValue(id) {
    const el = document.getElementById(id);

    return el ? el.value.trim() : '';
}

const validators = {
    judul: () => {
        const value = textValue('judul');
        if (!value) return 'Nama produk wajib diisi.';
        if (value.length > 255) return 'Nama produk maksimal 255 karakter.';
        return '';
    },
    category_id: () => (textValue('category_id') ? '' : 'Pilih kategori produk.'),
    harga: () => {
        const el = document.getElementById('harga');
        if (el.value === '' || el.value === null) return 'Harga wajib diisi.';
        const amount = Number(el.value);
        if (!Number.isInteger(amount)) return 'Harga harus angka bulat.';
        if (amount < 0) return 'Harga minimal 0 (Rp 0).';
        return '';
    },
    deskripsi: () => {
        const value = textValue('deskripsi');
        if (!value) return 'Deskripsi wajib diisi.';
        if (value.length < 10) return 'Deskripsi minimal 10 karakter.';
        return '';
    },
    kota: () => {
        const value = textValue('kota');
        if (!value) return 'Kota / area produk wajib diisi.';
        if (value.length > 255) return 'Kota maksimal 255 karakter.';
        return '';
    },
    patokan_lokasi: () => {
        const value = textValue('patokan_lokasi');
        if (value.length > 255) return 'Patokan lokasi maksimal 255 karakter.';
        return '';
    },
};

function validateImages() {
    if (files.length === 0) return 'Unggah minimal 1 gambar produk.';
    if (files.length > MAX_FILES) return `Maksimal ${MAX_FILES} gambar produk.`;

    for (const file of files) {
        if (!file.type.startsWith('image/')) return 'Hanya file gambar yang diperbolehkan.';
        if (file.size > MAX_BYTES) return `Gambar "${file.name}" melebihi 5MB.`;
    }

    return '';
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
        if (first === null) first = 'images';
    } else {
        hideError();
    }

    return first;
}

function focusInvalid(name) {
    if (name === 'images') {
        els.dropzone.scrollIntoView({ behavior: 'smooth', block: 'center' });
        return;
    }

    const el = document.getElementById(name);

    if (el) {
        el.scrollIntoView({ behavior: 'smooth', block: 'center' });
        el.focus({ preventScroll: true });
    }
}

function compressImage(file, quality = 0.7, maxSide = 1920) {
    return new Promise((resolve) => {
        const img = new Image();

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

            const canvas = document.createElement('canvas');
            canvas.width = Math.round(width);
            canvas.height = Math.round(height);
            canvas.getContext('2d').drawImage(img, 0, 0, width, height);

            canvas.toBlob((blob) => {
                resolve(blob ? new File([blob], file.name, { type: 'image/jpeg' }) : file);
            }, 'image/jpeg', quality);
        };

        img.onerror = () => resolve(file);
        img.src = URL.createObjectURL(file);
    });
}

async function prepareFiles(accepted) {
    return Promise.all(
        accepted.map(async (file) => {
            if (file.size <= SAFE_UPLOAD_BYTES) return file;

            const compressed = await compressImage(file, 0.7);

            return compressed.size > SAFE_UPLOAD_BYTES ? compressImage(file, 0.4) : compressed;
        })
    );
}

function renderPreviews() {
    els.previewContainer.innerHTML = '';
    const transfer = new DataTransfer();

    files.forEach((file, index) => {
        transfer.items.add(file);

        const isCover = index === 0;
        const item = document.createElement('div');
        item.className = `relative aspect-square overflow-hidden rounded-2xl border-2 ${isCover ? 'border-[var(--color-lime)] ring-2 ring-[var(--color-lime)]/40 shadow-sm' : 'border-[var(--color-border-default)]'} bg-neutral-100 group cursor-grab active:cursor-grabbing transition-all duration-150`;
        item.draggable = true;
        item.dataset.index = index;

        item.innerHTML = `
            <img src="${URL.createObjectURL(file)}" alt="Preview ${index + 1}" class="h-full w-full object-cover pointer-events-none select-none">
            <button type="button" data-remove-index="${index}"
                    class="absolute top-1.5 right-1.5 flex h-6 w-6 items-center justify-center rounded-full bg-black/60 text-white transition hover:bg-red-600 z-10 pointer-events-auto">
                &times;
            </button>
            ${isCover
                ? `<div class="absolute inset-x-0 bottom-0 bg-[var(--color-lime)] py-1 text-center text-[10px] font-extrabold text-[var(--color-charcoal)] shadow-sm pointer-events-none select-none">★ COVER UTAMA</div>`
                : `<div class="absolute inset-x-0 bottom-0 bg-black/40 py-0.5 text-center text-[9px] font-semibold text-white pointer-events-none select-none">Foto ${index + 1}</div>`}
        `;

        item.addEventListener('dragstart', (event) => {
            event.dataTransfer.setData('text/plain', String(index));
            event.dataTransfer.effectAllowed = 'move';
            item.classList.add('opacity-40', 'scale-95');
        });

        item.addEventListener('dragover', (event) => {
            event.preventDefault();
            event.dataTransfer.dropEffect = 'move';
            item.classList.add('scale-105', 'border-amber-400');
        });

        item.addEventListener('dragleave', () => {
            item.classList.remove('scale-105', 'border-amber-400');
        });

        item.addEventListener('drop', (event) => {
            event.preventDefault();
            event.stopPropagation();
            item.classList.remove('scale-105', 'border-amber-400');

            const from = Number.parseInt(event.dataTransfer.getData('text/plain'), 10);

            if (!Number.isNaN(from) && from !== index) {
                const [moved] = files.splice(from, 1);
                files.splice(index, 0, moved);
                renderPreviews();
            }
        });

        item.addEventListener('dragend', () => {
            item.classList.remove('opacity-40', 'scale-95');
        });

        els.previewContainer.appendChild(item);
    });

    els.imageInput.files = transfer.files;
}

function buildFormData() {
    const data = new FormData();

    for (const el of els.form.querySelectorAll('input, textarea, select')) {
        if (el.name === 'images[]') continue;

        if (el.type === 'radio') {
            if (el.checked) data.append(el.name, el.value);
        } else {
            data.append(el.name, el.value);
        }
    }

    files.forEach((file) => data.append('images[]', file, file.name));

    return data;
}

function handleResponse(response) {
    if (response.status === 419) {
        window.location.reload();
        return;
    }

    if (response.status === 413) {
        showError('File terlalu besar. Maksimal 5MB per gambar.');
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

    if (error?.validation) {
        let firstField = null;

        Object.entries(error.validation).forEach(([key, messages]) => {
            const message = Array.isArray(messages) ? messages[0] : messages;

            if (key.startsWith('images')) {
                showError(message);
            } else {
                setFieldError(key, message);
            }

            if (firstField === null) firstField = key;
        });

        focusInvalid(firstField);
        return;
    }

    showFormError('Terjadi kesalahan saat mengunggah. Silakan coba lagi atau gunakan gambar lain.');
}

function handleImageSelection(event) {
    hideError();

    const accepted = [];
    let rejected = '';

    Array.from(event.target.files || []).forEach((file) => {
        if (!file.type.startsWith('image/')) {
            rejected = 'Hanya file gambar yang diperbolehkan.';
            return;
        }
        if (file.size > MAX_BYTES) {
            rejected = `Gambar "${file.name}" melebihi 5MB.`;
            return;
        }
        accepted.push(file);
    });

    if (files.length + accepted.length > MAX_FILES) {
        showError(`Maksimal hanya dapat memilih ${MAX_FILES} gambar produk.`);
        els.imageInput.value = '';
        return;
    }

    if (accepted.length === 0) {
        if (rejected) showError(rejected);
        return;
    }

    prepareFiles(accepted)
        .then((prepared) => {
            files.push(...prepared.slice(0, MAX_FILES - files.length));
            renderPreviews();
            if (rejected) showError(rejected);
        })
        .catch(() => showError('Gagal memproses gambar. Coba gambar lain.'));
}

function handleSubmit(event) {
    event.preventDefault();
    hideError();
    showFormError('');

    const first = validateAll();

    if (first) {
        focusInvalid(first);
        return;
    }

    els.submitBtn.disabled = true;

    fetch(els.form.action, {
        method: 'POST',
        body: buildFormData(),
        headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json' },
        credentials: 'same-origin',
    })
        .then(handleResponse)
        .catch(handleError);
}

export function initProductCreate() {
    els.form = document.getElementById('productForm');

    if (!els.form) return;

    els.dropzone = document.getElementById('dropzone');
    els.imageInput = document.getElementById('imageInput');
    els.previewContainer = document.getElementById('previewContainer');
    els.errorContainer = document.getElementById('imageErrorContainer');
    els.errorMessage = document.getElementById('imageErrorMessage');
    els.closeErrorBtn = document.getElementById('closeErrorBtn');
    els.formError = document.getElementById('formError');
    els.submitBtn = els.form.querySelector('button[type="submit"]');

    els.closeErrorBtn.addEventListener('click', hideError);
    els.dropzone.addEventListener('click', () => els.imageInput.click());

    els.dropzone.addEventListener('dragover', (event) => {
        event.preventDefault();
        els.dropzone.classList.add('border-[var(--color-lime)]', 'bg-[var(--color-lime-100)]');
    });

    els.dropzone.addEventListener('dragleave', () => {
        els.dropzone.classList.remove('border-[var(--color-lime)]', 'bg-[var(--color-lime-100)]');
    });

    els.dropzone.addEventListener('drop', (event) => {
        event.preventDefault();
        els.dropzone.classList.remove('border-[var(--color-lime)]', 'bg-[var(--color-lime-100)]');
        els.imageInput.files = event.dataTransfer.files;
        els.imageInput.dispatchEvent(new Event('change', { bubbles: true }));
    });

    els.imageInput.addEventListener('change', handleImageSelection);

    els.previewContainer.addEventListener('click', (event) => {
        const button = event.target.closest('[data-remove-index]');

        if (!button) return;

        hideError();
        files.splice(Number(button.dataset.removeIndex), 1);
        renderPreviews();
    });

    FIELD_IDS.forEach((name) => {
        const el = document.getElementById(name);

        if (!el) return;

        el.addEventListener('input', () => setFieldError(name, validators[name]()));
        el.addEventListener('change', () => setFieldError(name, validators[name]()));
    });

    els.form.addEventListener('submit', handleSubmit);
}
