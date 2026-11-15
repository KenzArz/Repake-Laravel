/**
 * Utilitas gambar bersama untuk chat: kompresi sebelum unggah dan
 * konversi kanvas menjadi File.
 */

export const ALLOWED_IMAGE_TYPES = [
    "image/jpeg",
    "image/png",
    "image/webp",
    "image/gif",
];

/** Batas sisi terpanjang gambar yang diunggah dari chat. */
export const MAX_UPLOAD_SIDE = 1920;

export function isImageFile(file) {
    return Boolean(file) && ALLOWED_IMAGE_TYPES.includes(file.type);
}

export function baseName(name) {
    return (name ?? "foto").replace(/\.[^./\\]+$/, "") || "foto";
}

export function loadImage(src) {
    return new Promise((resolve, reject) => {
        const image = new Image();
        image.onload = () => resolve(image);
        image.onerror = () => reject(new Error("Gambar gagal dibaca."));
        image.src = src;
    });
}

export function fitWithin(width, height, maxSide) {
    if (width <= maxSide && height <= maxSide) {
        return { width: Math.max(1, Math.round(width)), height: Math.max(1, Math.round(height)) };
    }

    const scale = Math.min(maxSide / width, maxSide / height);

    return {
        width: Math.max(1, Math.round(width * scale)),
        height: Math.max(1, Math.round(height * scale)),
    };
}

export function canvasToBlob(canvas, type = "image/webp", quality = 0.85) {
    return new Promise((resolve) => {
        canvas.toBlob((blob) => resolve(blob), type, quality);
    });
}

/**
 * Kompres foto menjadi WebP agar satu kiriman banyak foto tetap ringan.
 * GIF dibiarkan apa adanya supaya animasinya tidak hilang.
 */
export async function compressImage(
    file,
    { maxSide = MAX_UPLOAD_SIDE, quality = 0.82, skipUnderBytes = 450 * 1024 } = {},
) {
    if (!isImageFile(file) || file.type === "image/gif") return file;

    const objectUrl = URL.createObjectURL(file);

    try {
        const image = await loadImage(objectUrl);
        const { width, height } = fitWithin(
            image.naturalWidth || image.width,
            image.naturalHeight || image.height,
            maxSide,
        );

        const canvas = document.createElement("canvas");
        canvas.width = width;
        canvas.height = height;
        canvas.getContext("2d").drawImage(image, 0, 0, width, height);

        const blob = await canvasToBlob(canvas, "image/webp", quality);
        if (!blob) return file;

        // Jangan naikkan ukuran: kalau hasil kompresi lebih besar dan file
        // aslinya sudah kecil, pakai file aslinya.
        if (blob.size >= file.size && file.size <= skipUnderBytes) return file;

        return new File([blob], `${baseName(file.name)}.webp`, {
            type: "image/webp",
            lastModified: Date.now(),
        });
    } catch {
        return file;
    } finally {
        URL.revokeObjectURL(objectUrl);
    }
}
