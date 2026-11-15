/**
 * Editor foto chat: crop, blur area sensitif, dan spidol warna.
 *
 * Pendekatan render:
 * - `baseCanvas` menyimpan piksel yang sudah "dikomit" (foto / hasil crop).
 * - `inkCanvas` menyimpan semua goresan yang sudah selesai.
 * - `activeCanvas` menyimpan goresan yang sedang digambar, lalu digabung
 *   ke `inkCanvas` saat pointer dilepas.
 * - Undo menghapus perintah terakhir lalu menggambar ulang `inkCanvas` dari
 *   daftar perintah; crop punya riwayat `baseCanvas` tersendiri.
 */

import { baseName, canvasToBlob, fitWithin, loadImage } from "./image-utils";

const MAX_EDIT_SIDE = 1920;
const MAX_BASE_HISTORY = 6;
const PEN_ALPHA = 0.55;
const MIN_CROP_SIDE = 24;

const DEFAULT_COLOR = "#F44336";
const DEFAULT_BRUSH = 6;

function clamp(value, min, max) {
    if (max < min) return max;
    return Math.min(Math.max(value, min), max);
}

export function createImageEditor(dialog) {
    if (!dialog) return null;

    const canvas = dialog.querySelector("[data-editor-canvas]");
    const overlay = dialog.querySelector("[data-editor-overlay]");
    const tools = [...dialog.querySelectorAll("[data-editor-tool]")];
    const brushPanel = dialog.querySelector("[data-editor-brush]");
    const colorsPanel = dialog.querySelector("[data-editor-colors]");
    const cropPanel = dialog.querySelector("[data-editor-crop]");
    const colorButtons = [...dialog.querySelectorAll("[data-editor-color]")];
    const aspectButtons = [...dialog.querySelectorAll("[data-crop-aspect]")];
    const sizeInput = dialog.querySelector("[data-editor-size]");
    const undoButton = dialog.querySelector("[data-editor-undo]");
    const saveButton = dialog.querySelector("[data-editor-save]");
    const viewport = dialog.querySelector("[data-editor-viewport]");

    if (!canvas || !overlay) return null;

    const viewCtx = canvas.getContext("2d");
    const overlayCtx = overlay.getContext("2d");

    let baseCanvas = null;
    let blurCanvas = null;
    let inkCanvas = null;
    let activeCanvas = null;
    let activeStroke = null;
    let commands = [];
    let baseHistory = [];
    let tool = "pen";
    let brushSize = DEFAULT_BRUSH;
    let color = DEFAULT_COLOR;
    let cropRect = null;
    let cropAspect = null;
    let cropDrag = null;
    let pointerDown = false;
    let dirty = false;
    let originalFile = null;
    let originalImage = null;
    let resolver = null;
    let saving = false;

    /* ------------------------------------------------------------ helpers */

    function brushDiameter() {
        if (!baseCanvas) return 8;
        const shortest = Math.min(baseCanvas.width, baseCanvas.height);

        return Math.max(4, Math.round((shortest * brushSize) / 100));
    }

    function fullRect() {
        return { x: 0, y: 0, w: baseCanvas.width, h: baseCanvas.height };
    }

    function makeCanvas(width, height) {
        const node = document.createElement("canvas");
        node.width = Math.max(1, Math.round(width));
        node.height = Math.max(1, Math.round(height));
        return node;
    }

    /**
     * Kanvas ditampilkan sebesar mungkin di dalam area pratinjau, dengan rasio
     * asli dipertahankan. Ukuran piksel internal tidak berubah, hanya CSS-nya.
     */
    function fitCanvas() {
        if (!baseCanvas) return;

        const host = viewport || canvas.parentElement?.parentElement;
        if (!host) return;

        const styles = getComputedStyle(host);
        const padX = (parseFloat(styles.paddingLeft) || 0) + (parseFloat(styles.paddingRight) || 0);
        const padY = (parseFloat(styles.paddingTop) || 0) + (parseFloat(styles.paddingBottom) || 0);
        const availableWidth = Math.max(1, host.clientWidth - padX);
        const availableHeight = Math.max(1, host.clientHeight - padY);
        const scale = Math.min(availableWidth / baseCanvas.width, availableHeight / baseCanvas.height);

        canvas.style.width = `${Math.max(1, Math.floor(baseCanvas.width * scale))}px`;
        canvas.style.height = `${Math.max(1, Math.floor(baseCanvas.height * scale))}px`;
    }

    function rebuildBlurCanvas() {
        blurCanvas = makeCanvas(baseCanvas.width, baseCanvas.height);
        const ctx = blurCanvas.getContext("2d");
        const radius = Math.max(6, Math.round(Math.min(baseCanvas.width, baseCanvas.height) * 0.012));
        ctx.filter = `blur(${radius}px)`;
        ctx.drawImage(baseCanvas, 0, 0);
    }

    function prepareCanvases() {
        canvas.width = baseCanvas.width;
        canvas.height = baseCanvas.height;
        overlay.width = baseCanvas.width;
        overlay.height = baseCanvas.height;

        inkCanvas = makeCanvas(baseCanvas.width, baseCanvas.height);
        activeCanvas = makeCanvas(baseCanvas.width, baseCanvas.height);
        activeStroke = null;
        rebuildBlurCanvas();
        renderView();
        renderOverlay();
        fitCanvas();
    }

    function renderView() {
        if (!baseCanvas) return;
        viewCtx.clearRect(0, 0, canvas.width, canvas.height);
        viewCtx.drawImage(baseCanvas, 0, 0);
        viewCtx.drawImage(inkCanvas, 0, 0);

        if (activeStroke) {
            viewCtx.save();
            viewCtx.globalAlpha = activeStroke.tool === "pen" ? PEN_ALPHA : 1;
            viewCtx.drawImage(activeCanvas, 0, 0);
            viewCtx.restore();
        }
    }

    /* ------------------------------------------------------------- drawing */

    function blurAt(ctx, x, y, radius) {
        ctx.save();
        ctx.beginPath();
        ctx.arc(x, y, radius, 0, Math.PI * 2);
        ctx.clip();
        ctx.drawImage(blurCanvas, 0, 0);
        ctx.restore();
    }

    function densify(points, step) {
        if (points.length < 2) return points;
        const out = [points[0]];

        for (let index = 1; index < points.length; index += 1) {
            const from = points[index - 1];
            const to = points[index];
            const distance = Math.hypot(to.x - from.x, to.y - from.y);
            const steps = Math.max(1, Math.ceil(distance / Math.max(1, step)));

            for (let stepIndex = 1; stepIndex <= steps; stepIndex += 1) {
                out.push({
                    x: from.x + ((to.x - from.x) * stepIndex) / steps,
                    y: from.y + ((to.y - from.y) * stepIndex) / steps,
                });
            }
        }

        return out;
    }

    function paintStroke(ctx, stroke, alpha = 1) {
        if (!stroke || stroke.points.length === 0) return;

        if (stroke.tool === "pen") {
            ctx.save();
            ctx.globalAlpha = alpha;
            ctx.strokeStyle = stroke.color;
            ctx.fillStyle = stroke.color;
            ctx.lineWidth = stroke.size;
            ctx.lineCap = "round";
            ctx.lineJoin = "round";

            if (stroke.points.length === 1) {
                const point = stroke.points[0];
                ctx.beginPath();
                ctx.arc(point.x, point.y, stroke.size / 2, 0, Math.PI * 2);
                ctx.fill();
            } else {
                ctx.beginPath();
                ctx.moveTo(stroke.points[0].x, stroke.points[0].y);
                for (let index = 1; index < stroke.points.length; index += 1) {
                    ctx.lineTo(stroke.points[index].x, stroke.points[index].y);
                }
                ctx.stroke();
            }

            ctx.restore();
            return;
        }

        const radius = stroke.size / 2;
        densify(stroke.points, Math.max(2, radius * 0.35)).forEach((point) => {
            blurAt(ctx, point.x, point.y, radius);
        });
    }

    function replayInk() {
        const ctx = inkCanvas.getContext("2d");
        ctx.clearRect(0, 0, inkCanvas.width, inkCanvas.height);
        commands.forEach((stroke) => paintStroke(ctx, stroke, stroke.tool === "pen" ? PEN_ALPHA : 1));
    }

    function pointFromEvent(event, target) {
        const rect = target.getBoundingClientRect();
        if (rect.width === 0 || rect.height === 0) return { x: 0, y: 0 };

        return {
            x: (event.clientX - rect.left) * (target.width / rect.width),
            y: (event.clientY - rect.top) * (target.height / rect.height),
        };
    }

    function startStroke(event) {
        if (tool === "crop" || !baseCanvas) return;
        event.preventDefault();
        pointerDown = true;
        activeCanvas.getContext("2d").clearRect(0, 0, activeCanvas.width, activeCanvas.height);
        activeStroke = {
            tool,
            color,
            size: brushDiameter(),
            points: [pointFromEvent(event, canvas)],
        };
        canvas.setPointerCapture?.(event.pointerId);
        drawActivePoint();
        renderView();
    }

    function moveStroke(event) {
        if (!pointerDown || !activeStroke) return;
        event.preventDefault();

        const point = pointFromEvent(event, canvas);
        const previous = activeStroke.points[activeStroke.points.length - 1];
        activeStroke.points.push(point);
        drawActivePoint(previous);
        renderView();
    }

    function drawActivePoint(previous = null) {
        if (!activeStroke) return;
        const ctx = activeCanvas.getContext("2d");
        const current = activeStroke.points[activeStroke.points.length - 1];

        if (activeStroke.tool === "pen") {
            ctx.save();
            ctx.strokeStyle = activeStroke.color;
            ctx.fillStyle = activeStroke.color;
            ctx.lineWidth = activeStroke.size;
            ctx.lineCap = "round";
            ctx.lineJoin = "round";
            ctx.beginPath();

            if (previous) {
                ctx.moveTo(previous.x, previous.y);
                ctx.lineTo(current.x, current.y);
                ctx.stroke();
            } else {
                ctx.arc(current.x, current.y, activeStroke.size / 2, 0, Math.PI * 2);
                ctx.fill();
            }

            ctx.restore();
            return;
        }

        const radius = activeStroke.size / 2;
        const from = previous ?? current;
        const distance = Math.hypot(current.x - from.x, current.y - from.y);
        const steps = Math.max(1, Math.ceil(distance / Math.max(2, radius * 0.35)));

        for (let stepIndex = 1; stepIndex <= steps; stepIndex += 1) {
            blurAt(
                ctx,
                from.x + ((current.x - from.x) * stepIndex) / steps,
                from.y + ((current.y - from.y) * stepIndex) / steps,
                radius,
            );
        }
    }

    function endStroke(event) {
        if (!pointerDown) return;
        pointerDown = false;
        canvas.releasePointerCapture?.(event.pointerId);

        if (!activeStroke) return;

        const inkCtx = inkCanvas.getContext("2d");
        paintStroke(inkCtx, activeStroke, activeStroke.tool === "pen" ? PEN_ALPHA : 1);

        commands.push(activeStroke);
        activeStroke = null;
        activeCanvas.getContext("2d").clearRect(0, 0, activeCanvas.width, activeCanvas.height);
        dirty = true;
        renderView();
        updateUndo();
    }

    /* ---------------------------------------------------------------- crop */

    function renderOverlay() {
        if (!baseCanvas) return;

        overlayCtx.clearRect(0, 0, overlay.width, overlay.height);

        if (tool !== "crop" || !cropRect) return;

        const width = overlay.width;
        const height = overlay.height;
        const line = Math.max(2, Math.round(width / 700));

        overlayCtx.fillStyle = "rgba(0,0,0,0.55)";
        overlayCtx.fillRect(0, 0, width, height);
        overlayCtx.clearRect(cropRect.x, cropRect.y, cropRect.w, cropRect.h);

        overlayCtx.strokeStyle = "rgba(255,255,255,0.95)";
        overlayCtx.lineWidth = line;
        overlayCtx.strokeRect(cropRect.x, cropRect.y, cropRect.w, cropRect.h);

        // Garis bantu sepertiga.
        overlayCtx.strokeStyle = "rgba(255,255,255,0.35)";
        overlayCtx.lineWidth = line / 2;
        for (let index = 1; index <= 2; index += 1) {
            const x = cropRect.x + (cropRect.w * index) / 3;
            const y = cropRect.y + (cropRect.h * index) / 3;
            overlayCtx.beginPath();
            overlayCtx.moveTo(x, cropRect.y);
            overlayCtx.lineTo(x, cropRect.y + cropRect.h);
            overlayCtx.moveTo(cropRect.x, y);
            overlayCtx.lineTo(cropRect.x + cropRect.w, y);
            overlayCtx.stroke();
        }

        // Pegangan sudut.
        const handle = Math.max(12, Math.round(width / 40));
        overlayCtx.fillStyle = "#ffffff";
        [
            [cropRect.x, cropRect.y],
            [cropRect.x + cropRect.w, cropRect.y],
            [cropRect.x, cropRect.y + cropRect.h],
            [cropRect.x + cropRect.w, cropRect.y + cropRect.h],
        ].forEach(([x, y]) => {
            overlayCtx.fillRect(x - handle / 2, y - handle / 2, handle, handle);
        });
    }

    function hitHandle(point) {
        const radius = 22 * (overlay.width / Math.max(1, overlay.getBoundingClientRect().width));
        const corners = {
            nw: { x: cropRect.x, y: cropRect.y },
            ne: { x: cropRect.x + cropRect.w, y: cropRect.y },
            sw: { x: cropRect.x, y: cropRect.y + cropRect.h },
            se: { x: cropRect.x + cropRect.w, y: cropRect.y + cropRect.h },
        };

        return Object.entries(corners).find(([, corner]) => (
            Math.abs(corner.x - point.x) <= radius && Math.abs(corner.y - point.y) <= radius
        ))?.[0] ?? null;
    }

    function startCrop(event) {
        if (tool !== "crop" || !cropRect) return;
        event.preventDefault();

        const point = pointFromEvent(event, overlay);
        const handle = hitHandle(point);
        const inside = point.x >= cropRect.x
            && point.x <= cropRect.x + cropRect.w
            && point.y >= cropRect.y
            && point.y <= cropRect.y + cropRect.h;

        if (!handle && !inside) return;

        cropDrag = { mode: handle ?? "move", last: point, startRect: { ...cropRect } };
        overlay.setPointerCapture?.(event.pointerId);
    }

    function moveCrop(event) {
        if (!cropDrag || !cropRect) return;
        event.preventDefault();

        const point = pointFromEvent(event, overlay);

        if (cropDrag.mode === "move") {
            const dx = point.x - cropDrag.last.x;
            const dy = point.y - cropDrag.last.y;
            cropRect.x = clamp(cropRect.x + dx, 0, baseCanvas.width - cropRect.w);
            cropRect.y = clamp(cropRect.y + dy, 0, baseCanvas.height - cropRect.h);
            cropDrag.last = point;
        } else {
            resizeCrop(cropDrag.mode, point);
        }

        renderOverlay();
    }

    function endCrop() {
        cropDrag = null;
    }

    function resizeCrop(mode, point) {
        const width = baseCanvas.width;
        const height = baseCanvas.height;

        const anchors = {
            nw: { x: cropRect.x + cropRect.w, y: cropRect.y + cropRect.h },
            ne: { x: cropRect.x, y: cropRect.y + cropRect.h },
            sw: { x: cropRect.x + cropRect.w, y: cropRect.y },
            se: { x: cropRect.x, y: cropRect.y },
        };
        const anchor = anchors[mode];
        const signX = point.x >= anchor.x ? 1 : -1;
        const signY = point.y >= anchor.y ? 1 : -1;
        const minSide = Math.max(MIN_CROP_SIDE, Math.round(Math.min(width, height) * 0.03));

        let newWidth = clamp(Math.abs(point.x - anchor.x), minSide, signX > 0 ? width - anchor.x : anchor.x);
        let newHeight = clamp(Math.abs(point.y - anchor.y), minSide, signY > 0 ? height - anchor.y : anchor.y);

        if (cropAspect) {
            if (newWidth / newHeight > cropAspect) {
                newWidth = newHeight * cropAspect;
            } else {
                newHeight = newWidth / cropAspect;
            }
            newWidth = Math.min(newWidth, signX > 0 ? width - anchor.x : anchor.x);
            newHeight = Math.min(newHeight, signY > 0 ? height - anchor.y : anchor.y);
        }

        cropRect = {
            x: signX > 0 ? anchor.x : anchor.x - newWidth,
            y: signY > 0 ? anchor.y : anchor.y - newHeight,
            w: newWidth,
            h: newHeight,
        };
    }

    function applyCrop() {
        if (!cropRect) return;

        const sourceX = Math.round(cropRect.x);
        const sourceY = Math.round(cropRect.y);
        const sourceWidth = Math.round(cropRect.w);
        const sourceHeight = Math.round(cropRect.h);

        if (sourceWidth < MIN_CROP_SIDE || sourceHeight < MIN_CROP_SIDE) return;

        // Ratakan goresan yang sudah ada ke dalam base sebelum crop, supaya
        // spidol/blur tidak hilang saat pengguna memotong foto.
        const flattened = makeCanvas(baseCanvas.width, baseCanvas.height);
        const flatCtx = flattened.getContext("2d");
        flatCtx.drawImage(baseCanvas, 0, 0);
        flatCtx.drawImage(inkCanvas, 0, 0);

        const cropped = makeCanvas(sourceWidth, sourceHeight);
        cropped.getContext("2d").drawImage(
            flattened,
            sourceX,
            sourceY,
            sourceWidth,
            sourceHeight,
            0,
            0,
            sourceWidth,
            sourceHeight,
        );

        baseHistory.push(flattened);
        if (baseHistory.length > MAX_BASE_HISTORY) baseHistory.shift();

        baseCanvas = cropped;
        commands = [];
        cropAspect = null;
        cropRect = null;
        dirty = true;

        prepareCanvases();
        cropRect = fullRect();
        renderOverlay();
        setAspect(null);
        updateUndo();
    }

    function setAspect(aspect) {
        cropAspect = aspect === null || aspect === "free" ? null : Number(aspect);

        aspectButtons.forEach((button) => {
            const value = button.dataset.cropAspect;
            const active = cropAspect === null ? value === "free" : Number(value) === cropAspect;
            button.setAttribute("aria-pressed", String(active));
        });

        if (!cropRect || !baseCanvas) return;

        if (cropAspect) {
            const width = baseCanvas.width;
            const height = baseCanvas.height;
            const centerX = cropRect.x + cropRect.w / 2;
            const centerY = cropRect.y + cropRect.h / 2;

            let newWidth = width;
            let newHeight = width / cropAspect;
            if (newHeight > height) {
                newHeight = height;
                newWidth = height * cropAspect;
            }

            cropRect = {
                x: clamp(centerX - newWidth / 2, 0, width - newWidth),
                y: clamp(centerY - newHeight / 2, 0, height - newHeight),
                w: newWidth,
                h: newHeight,
            };
        }

        renderOverlay();
    }

    /* -------------------------------------------------------------- actions */

    function setTool(next) {
        tool = next;
        tools.forEach((button) => {
            button.setAttribute("aria-pressed", String(button.dataset.editorTool === next));
        });

        const brushVisible = next !== "crop";
        brushPanel?.classList.toggle("hidden", !brushVisible);
        brushPanel?.classList.toggle("flex", brushVisible);
        colorsPanel?.classList.toggle("hidden", next !== "pen");

        const cropVisible = next === "crop";
        cropPanel?.classList.toggle("hidden", !cropVisible);
        cropPanel?.classList.toggle("flex", cropVisible);

        overlay.classList.toggle("hidden", !cropVisible);
        canvas.classList.toggle("cursor-crosshair", !cropVisible);
        canvas.classList.toggle("cursor-default", cropVisible);

        if (cropVisible) renderOverlay();

        // Panel pengaturan berubah tinggi, jadi area kanvas ikut dihitung ulang.
        requestAnimationFrame(fitCanvas);
    }

    function setColor(next) {
        color = next;
        colorButtons.forEach((button) => {
            button.setAttribute("aria-pressed", String(button.dataset.editorColor === next));
        });
    }

    function setBrush(value) {
        brushSize = clamp(Number(value) || DEFAULT_BRUSH, 2, 20);
        if (sizeInput) sizeInput.value = String(brushSize);
    }

    function updateUndo() {
        if (undoButton) undoButton.disabled = commands.length === 0 && baseHistory.length === 0;
    }

    function undo() {
        if (commands.length > 0) {
            commands.pop();
            replayInk();
        } else if (baseHistory.length > 0) {
            baseCanvas = baseHistory.pop();
            commands = [];
            dirty = baseHistory.length > 0;

            prepareCanvases();
            cropRect = fullRect();
            renderOverlay();
            setAspect(null);
        }

        renderView();
        updateUndo();
    }

    function reset() {
        if (!originalImage) return;

        const { width, height } = fitWithin(
            originalImage.naturalWidth || originalImage.width,
            originalImage.naturalHeight || originalImage.height,
            MAX_EDIT_SIDE,
        );

        baseHistory = [];
        commands = [];
        baseCanvas = makeCanvas(width, height);
        baseCanvas.getContext("2d").drawImage(originalImage, 0, 0, width, height);
        dirty = false;

        prepareCanvases();
        cropRect = fullRect();
        renderOverlay();
        setAspect(null);
        updateUndo();
    }

    /* ----------------------------------------------------------------- flow */

    function settle(result) {
        if (dialog.open) dialog.close();
        const resolve = resolver;
        resolver = null;
        resolve?.(result);
    }

    async function save() {
        if (saving || !baseCanvas || !originalFile) return;

        if (!dirty) {
            settle(originalFile);
            return;
        }

        saving = true;
        if (saveButton) saveButton.disabled = true;

        try {
            let blob = await canvasToBlob(canvas, "image/webp", 0.85);

            if (!blob || blob.type !== "image/webp") {
                const flattened = makeCanvas(canvas.width, canvas.height);
                const ctx = flattened.getContext("2d");
                ctx.fillStyle = "#ffffff";
                ctx.fillRect(0, 0, flattened.width, flattened.height);
                ctx.drawImage(canvas, 0, 0);
                blob = await canvasToBlob(flattened, "image/jpeg", 0.9);
            }

            if (!blob) return;

            const extension = blob.type === "image/webp" ? "webp" : "jpg";
            settle(new File([blob], `${baseName(originalFile.name)}-edit.${extension}`, {
                type: blob.type,
                lastModified: Date.now(),
            }));
        } finally {
            saving = false;
            if (saveButton) saveButton.disabled = false;
        }
    }

    async function open(file) {
        if (!file) return null;

        originalFile = file;
        dirty = false;
        commands = [];
        baseHistory = [];
        cropAspect = null;

        const objectUrl = URL.createObjectURL(file);

        try {
            originalImage = await loadImage(objectUrl);
        } catch {
            URL.revokeObjectURL(objectUrl);
            throw new Error("Foto tidak bisa dibuka untuk diedit.");
        }

        URL.revokeObjectURL(objectUrl);

        const { width, height } = fitWithin(
            originalImage.naturalWidth || originalImage.width,
            originalImage.naturalHeight || originalImage.height,
            MAX_EDIT_SIDE,
        );

        baseCanvas = makeCanvas(width, height);
        baseCanvas.getContext("2d").drawImage(originalImage, 0, 0, width, height);

        setBrush(DEFAULT_BRUSH);
        setColor(DEFAULT_COLOR);
        prepareCanvases();
        cropRect = fullRect();
        setTool("pen");
        setAspect(null);
        updateUndo();

        if (!dialog.open) dialog.showModal();

        // Dialog baru punya ukuran setelah showModal(), jadi ukur ulang kanvas.
        requestAnimationFrame(fitCanvas);

        return new Promise((resolve) => {
            resolver = resolve;
        });
    }

    /* -------------------------------------------------------------- events */

    tools.forEach((button) => {
        button.addEventListener("click", () => setTool(button.dataset.editorTool));
    });

    colorButtons.forEach((button) => {
        button.addEventListener("click", () => setColor(button.dataset.editorColor));
    });

    aspectButtons.forEach((button) => {
        button.addEventListener("click", () => setAspect(button.dataset.cropAspect));
    });

    sizeInput?.addEventListener("input", () => setBrush(sizeInput.value));
    undoButton?.addEventListener("click", undo);
    dialog.querySelector("[data-editor-reset]")?.addEventListener("click", reset);
    dialog.querySelector("[data-editor-cancel]")?.addEventListener("click", () => settle(null));
    dialog.querySelector("[data-crop-apply]")?.addEventListener("click", applyCrop);
    saveButton?.addEventListener("click", save);

    canvas.addEventListener("pointerdown", startStroke);
    canvas.addEventListener("pointermove", moveStroke);
    canvas.addEventListener("pointerup", endStroke);
    canvas.addEventListener("pointercancel", endStroke);

    overlay.addEventListener("pointerdown", startCrop);
    overlay.addEventListener("pointermove", moveCrop);
    overlay.addEventListener("pointerup", endCrop);
    overlay.addEventListener("pointercancel", endCrop);

    dialog.addEventListener("click", (event) => {
        if (event.target === dialog) settle(null);
    });

    window.addEventListener("resize", () => {
        if (dialog.open) fitCanvas();
    });

    dialog.addEventListener("close", () => {
        const resolve = resolver;
        resolver = null;
        resolve?.(null);
        originalFile = null;
        originalImage = null;
        baseCanvas = null;
        inkCanvas = null;
        activeCanvas = null;
        activeStroke = null;
        commands = [];
        baseHistory = [];
    });

    return { open };
}
