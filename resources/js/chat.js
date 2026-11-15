/**
 * Chat RePake — realtime via polling.
 *
 * Prinsip:
 * 1. Satu sumber markup: server mengirim HTML pesan siap tempel (partial Blade),
 *    JS hanya menyisipkan. Tidak ada dua versi markup yang bisa menyimpang.
 * 2. Idempotensi: tiap kiriman membawa client_id; server menolak duplikat.
 * 3. Polling berantai (bukan setInterval) + dedupe id + pause saat tab tersembunyi.
 */

import { compressImage, isImageFile } from "./image-utils";

const MAX_ATTACHMENTS = 20;

/**
 * Batas total ukuran satu kiriman di sisi klien. Dijaga di bawah
 * `post_max_size` PHP (8 MB di server dev) supaya permintaan tidak dibuang
 * diam-diam oleh PHP dan pengguna melihat error validasi yang membingungkan.
 * Server sendiri masih punya jaring pengaman 25 MB.
 */
const MAX_TOTAL_BYTES = 7 * 1024 * 1024;

const CSRF =
    document.querySelector('meta[name="csrf-token"]')?.content ??
    document.querySelector('input[name="_token"]')?.value ??
    "";

const LOCALE = "id-ID";

const timeFormatter = new Intl.DateTimeFormat(LOCALE, {
    hour: "2-digit",
    minute: "2-digit",
    hour12: false,
});

const relativeFormatter = new Intl.RelativeTimeFormat("id", {
    numeric: "auto",
});

function formatTime(iso) {
    const date = new Date(iso);
    if (Number.isNaN(date.getTime())) return "";
    // id-ID 24 jam memakai titik sebagai pemisah (18.20), sama seperti desain.
    return timeFormatter.format(date).replace(":", ".");
}

function relativeTime(iso) {
    const diffMs = new Date(iso).getTime() - Date.now();
    if (Number.isNaN(diffMs)) return "";
    const minutes = Math.round(diffMs / 60000);
    if (Math.abs(minutes) < 1) return "baru saja";
    if (Math.abs(minutes) < 60) return relativeFormatter.format(minutes, "minute");
    const hours = Math.round(minutes / 60);
    if (Math.abs(hours) < 24) return relativeFormatter.format(hours, "hour");
    return relativeFormatter.format(Math.round(hours / 24), "day");
}

/**
 * Satu-satunya tempat yang merender status kehadiran (Online / Terakhir dilihat).
 * Dipakai saat inisialisasi (dari atribut DOM) dan saat poll (dari payload server),
 * sehingga tidak ada dua logika yang bisa menyimpang.
 */
function renderPresence(node, presence) {
    if (!node) return;

    const online = Boolean(presence?.online);
    const iso = presence?.last_seen_at ?? node.getAttribute("data-last-seen-at") ?? "";

    node.dataset.online = online ? "1" : "0";
    if (iso) node.setAttribute("data-last-seen-at", iso);

    if (online) {
        node.textContent = "Online";
        node.classList.remove("text-neutral-500");
        node.classList.add("text-lime-700");
        return;
    }

    node.classList.add("text-neutral-500");
    node.classList.remove("text-lime-700");
    node.textContent = iso
        ? `Terakhir dilihat ${relativeTime(iso)}`
        : "Terakhir dilihat";
}

// Hidrasi label yang sudah dirender server menjadi "Terakhir dilihat 12 menit lalu"
// tanpa menunggu poll pertama (menghilangkan kedip teks yang masih kosong).
function hydratePresence(node) {
    if (!node) return;
    renderPresence(node, {
        online: node.dataset.online === "1",
        last_seen_at: node.getAttribute("data-last-seen-at"),
    });
}

function hydrateTimes(root = document) {
    root.querySelectorAll("time[data-local-time]").forEach((node) => {
        const iso = node.getAttribute("datetime");
        if (!iso || node.dataset.localized === "1") return;
        node.textContent = formatTime(iso);
        node.dataset.localized = "1";
    });
}

function receiptSvg(status) {
    if (status === "sent") {
        return '<svg viewBox="0 0 24 24" class="size-3.5 fill-none stroke-current stroke-2" aria-hidden="true"><path d="m5 13 4 4L19 7" stroke-linecap="round" stroke-linejoin="round"/></svg>';
    }

    const tone = status === "read" ? "text-sky-600" : "";

    return `<svg viewBox="0 0 30 24" class="size-4 fill-none stroke-current stroke-2 ${tone}" aria-hidden="true"><path d="m2 13 4 4L16 7" stroke-linecap="round" stroke-linejoin="round"/><path d="m10 13 4 4L24 7" stroke-linecap="round" stroke-linejoin="round"/></svg>`;
}

function uid() {
    if (window.crypto?.randomUUID) return crypto.randomUUID();
    return `c${Date.now().toString(36)}${Math.random().toString(36).slice(2, 10)}`;
}

/* ------------------------------------------------------------------ toast */

const TOAST_BASE =
    "fixed inset-x-4 top-4 z-[60] mx-auto max-w-md rounded-xl border bg-white px-4 py-3 text-[13px] font-semibold shadow-xl transition-all duration-200 sm:left-auto sm:right-6 sm:w-auto";

let toastNode = null;
let toastTimer = null;

function toast(message, isError = false) {
    if (!message) return;

    if (!toastNode) {
        toastNode = document.createElement("div");
        toastNode.setAttribute("role", "status");
        toastNode.setAttribute("aria-live", "polite");
        document.body.appendChild(toastNode);
    }

    toastNode.textContent = message;
    toastNode.className = `${TOAST_BASE} ${isError ? "border-error/40 text-error" : "border-neutral-200 text-neutral-800"}`;

    clearTimeout(toastTimer);
    toastTimer = setTimeout(() => {
        toastNode?.classList.add("opacity-0", "-translate-y-2");
    }, 4000);
}

/* --------------------------------------------------------------- sidebar */

export function initChatSidebar() {
    const list = document.querySelector("[data-conversation-list]");
    if (!list) return;

    const toggle = document.querySelector("[data-filter-toggle]");
    const menu = document.querySelector("[data-filter-menu]");
    const chevron = document.querySelector("[data-filter-chevron]");

    const closeMenu = () => {
        menu?.classList.add("hidden");
        chevron?.classList.remove("rotate-180");
        toggle?.setAttribute("aria-expanded", "false");
    };

    toggle?.addEventListener("click", (event) => {
        event.stopPropagation();
        const hidden = menu?.classList.toggle("hidden");
        chevron?.classList.toggle("rotate-180", !hidden);
        toggle.setAttribute("aria-expanded", String(!hidden));
    });

    document.addEventListener("click", (event) => {
        if (menu && !menu.contains(event.target) && !toggle?.contains(event.target)) {
            closeMenu();
        }
    });

    document.addEventListener("keydown", (event) => {
        if (event.key === "Escape") closeMenu();
    });

    // Pencarian percakapan (filter lokal, tanpa request).
    const searchToggle = document.querySelector("[data-sidebar-search-toggle]");
    const searchWrap = document.querySelector("[data-sidebar-search-wrap]");
    const search = document.querySelector("[data-sidebar-search]");
    const empty = document.querySelector("[data-search-empty]");

    searchToggle?.addEventListener("click", () => {
        const hidden = searchWrap?.classList.toggle("hidden");
        if (!hidden) search?.focus();
        else if (search && search.value !== "") {
            search.value = "";
            filterItems("");
        }
    });

    const filterItems = (query) => {
        const needle = query.trim().toLowerCase();
        let visible = 0;

        list.querySelectorAll("[data-conversation-item]").forEach((item) => {
            const match = needle === "" || (item.dataset.searchText ?? "").includes(needle);
            item.classList.toggle("hidden", !match);
            if (match) visible += 1;
        });

        if (empty) empty.classList.toggle("hidden", visible !== 0 || needle === "");
    };

    search?.addEventListener("input", () => filterItems(search.value));
}

/* ------------------------------------------------------------------ chat */

export function initChat() {
    const app = document.getElementById("chat-app");
    if (!app) return;

    const box = app.querySelector("[data-chat-box]");
    const form = app.querySelector("[data-chat-form]");
    const input = app.querySelector("[data-chat-input]");
    const submitBtn = app.querySelector("[data-chat-submit]");
    const imageInput = app.querySelector("[data-chat-image]");
    const imageBtn = app.querySelector("[data-chat-image-btn]");
    const modeBtn = app.querySelector("[data-offer-mode-toggle]");
    const modeIcon = app.querySelector("[data-offer-mode-icon]");
    const modeClose = app.querySelector("[data-offer-mode-close]");
    const offerPrefix = app.querySelector("[data-offer-prefix]");
    const presenceNode = app.querySelector("[data-presence]");
    const offerSummary = document.getElementById("chat-offer-summary");

    if (!box || !form || !input) return;

    // ID user sekarang UUID (string) — jangan diubah ke Number().
    const myId = app.dataset.myId ?? "";
    const sendUrl = app.dataset.sendUrl;
    const pollUrl = app.dataset.pollUrl;
    const readUrl = app.dataset.readUrl;

    const lightbox = document.querySelector("[data-lightbox]");
    const lightboxImg = lightbox?.querySelector("[data-lightbox-img]");
    const lightboxCounter = lightbox?.querySelector("[data-lightbox-counter]");
    const lightboxPrev = lightbox?.querySelector("[data-lightbox-prev]");
    const lightboxNext = lightbox?.querySelector("[data-lightbox-next]");
    const lightboxCaption = lightbox?.querySelector("[data-lightbox-caption]");

    const attachPanel = document.querySelector("[data-attach-panel]");
    const attachImg = attachPanel?.querySelector("[data-attach-img]");
    const attachCounter = attachPanel?.querySelector("[data-attach-counter]");
    const attachStrip = app.querySelector("[data-attach-strip]");
    const attachList = app.querySelector("[data-attach-list]");
    const editorDialog = document.querySelector("[data-image-editor]");

    const offerModal = document.querySelector("[data-offer-modal]");
    const offerForm = offerModal?.querySelector("[data-offer-form]");
    const offerInput = offerModal?.querySelector("[data-offer-input]");
    const offerError = offerModal?.querySelector("[data-offer-error]");

    let lastId = maxMessageId();
    let polling = false;
    let pollTimer = null;
    let pollAbort = null;
    let failures = 0;
    let submitting = false;
    let offerMode = false;
    let pendingFiles = [];
    let activeAttachment = 0;
    let imageEditor = null;
    let lightboxGroup = [];
    let lightboxIndex = 0;
    let composeId = null;
    let editingOfferUrl = "";
    let editingOfferId = 0;

    hydrateTimes();
    refreshMeta();
    scrollToBottom();
    stickImages(box, () => true);
    pollLater(600);

    let pinnedToBottom = true;

    box.addEventListener("scroll", () => {
        pinnedToBottom = nearBottom();
    }, { passive: true });

    /* ------------------------------------------------------------ helpers */

    function maxMessageId() {
        let max = 0;
        box.querySelectorAll("[data-message-id]").forEach((node) => {
            max = Math.max(max, Number(node.dataset.messageId) || 0);
        });
        return max;
    }

    function nearBottom() {
        return box.scrollHeight - box.scrollTop - box.clientHeight < 140;
    }

    function scrollToBottom() {
        box.scrollTop = box.scrollHeight;
    }

    /**
     * Gambar baru belum punya tinggi sampai selesai dimuat, sehingga
     * scrollToBottom() bisa berhenti di atas bubble foto. Setelah gambar
     * termuat, tempel lagi ke bawah bila pengguna memang sedang di bawah.
     */
    function stickImages(node, shouldStick) {
        node.querySelectorAll("img").forEach((img) => {
            if (img.complete) return;
            img.addEventListener("load", () => {
                if (shouldStick()) scrollToBottom();
            }, { once: true });
        });
    }

    function refreshMeta() {
        const nodes = [...box.querySelectorAll("[data-message-id]")].filter(
            (node) => node.dataset.type !== "system",
        );

        nodes.forEach((node, index) => {
            const meta = node.querySelector("[data-group-meta]");
            if (!meta) return;
            const next = nodes[index + 1];
            const hide = Boolean(next) && next.dataset.senderId === node.dataset.senderId;
            meta.classList.toggle("hidden", hide);
        });
    }

    function appendHtml(html) {
        if (!html) return null;

        const template = document.createElement("template");
        template.innerHTML = html.trim();
        const node = template.content.firstElementChild;
        if (!node) return null;

        const id = Number(node.dataset.messageId) || 0;
        if (id && box.querySelector(`[data-message-id="${id}"]`)) return null;

        const stick = nearBottom();
        box.querySelector("[data-empty-state]")?.remove();
        box.appendChild(node);

        hydrateTimes(node);
        if (id) lastId = Math.max(lastId, id);
        refreshMeta();
        if (stick) scrollToBottom();
        stickImages(node, () => stick);

        return node;
    }

    function replaceHtml(html) {
        const template = document.createElement("template");
        template.innerHTML = html.trim();
        const node = template.content.firstElementChild;
        if (!node) return;

        const current = box.querySelector(`[data-message-id="${node.dataset.messageId}"]`);
        if (!current) {
            appendHtml(html);
            return;
        }

        current.replaceWith(node);
        hydrateTimes(node);
        refreshMeta();
    }

    function setOfferSummary(html) {
        if (!offerSummary || html === undefined) return;
        offerSummary.innerHTML = html;
    }

    function pendingBubble(text) {
        const node = document.createElement("div");
        node.className = "flex items-start justify-end";
        node.dataset.pending = "1";
        node.innerHTML = `<div class="max-w-[min(80%,26rem)] rounded-2xl rounded-br-md bg-lime-400 px-4 py-2 text-[15px] leading-[1.35] text-neutral-900/60">${text}</div>`;
        box.appendChild(node);
        scrollToBottom();
        return node;
    }

    function setBusy(busy) {
        submitBtn?.toggleAttribute("disabled", busy);
        submitBtn?.classList.toggle("opacity-60", busy);
    }

    resetComposeIdOnChange();

    function resetComposeIdOnChange() {
        const reset = () => {
            composeId = null;
        };
        input.addEventListener("input", reset);
        imageInput?.addEventListener("change", reset);
    }

    function currentComposeId() {
        if (composeId === null) composeId = uid();
        return composeId;
    }

    /* --------------------------------------------------------------- send */

    async function send({ body = "", files = [], type = "text", amount = null }) {
        if (submitting) return false;

        submitting = true;
        setBusy(true);

        const clientId = currentComposeId();
        const data = new FormData();
        data.append("_token", CSRF);
        data.append("client_id", clientId);
        data.append("body", body);

        if (type === "offer") {
            data.append("type", "offer");
            data.append("offer_amount", String(amount));
        }

        if (files.length > 0) {
            files.forEach((file) => data.append("images[]", file, file.name));
        }

        const pendingLabel = type === "offer"
            ? "Mengirim tawaran..."
            : files.length > 1
                ? `Mengirim ${files.length} foto...`
                : files.length === 1
                    ? "Mengirim foto..."
                    : "Mengirim...";

        const pending = pendingBubble(pendingLabel);

        try {
            const response = await fetch(sendUrl, {
                method: "POST",
                body: data,
                headers: {
                    Accept: "application/json",
                    "X-Requested-With": "XMLHttpRequest",
                },
            });

            if (response.status === 419) {
                throw new Error("Sesi kamu berakhir. Muat ulang halaman lalu kirim lagi.");
            }

            if (response.status === 413) {
                const payload = await response.json().catch(() => ({}));
                throw new Error(payload.message ?? "Ukuran gambar terlalu besar.");
            }

            if (response.status === 422) {
                const payload = await response.json().catch(() => ({}));
                const first = Object.values(payload.errors ?? {})[0];
                throw new Error(first?.[0] ?? "Pesan belum lengkap.");
            }

            if (!response.ok) throw new Error("Pesan gagal terkirim. Coba lagi.");

            const payload = await response.json();
            pending?.remove();
            appendHtml(payload.html);
            setOfferSummary(payload.offer_summary);
            applyReceipts(payload.receipts);
            onSentSuccess();
            return true;
        } catch (error) {
            pending?.remove();
            toast(error.message || "Pesan gagal terkirim.", true);
            return false;
        } finally {
            submitting = false;
            setBusy(false);
        }
    }

    function onSentSuccess() {
        input.value = "";
        input.style.height = "auto";
        composeId = null;
        clearAttachments();
        setOfferMode(false);
        input.focus();
    }

    function submitComposer() {
        if (submitting) return;

        if (offerMode) {
            const amount = Number(input.value.replace(/[^0-9]/g, ""));
            if (!amount) {
                toast("Masukkan nominal tawaran.", true);
                input.focus();
                return;
            }
            send({ type: "offer", amount }).then((ok) => {
                if (ok) setOfferMode(false);
            });
            return;
        }

        const body = input.value.trim();
        const files = pendingFiles.map((item) => item.file);

        if (files.length === 0 && !body) return;

        send({ body, files });
    }

    form.addEventListener("submit", (event) => {
        event.preventDefault();
        submitComposer();
    });

    // Laptop/desktop: Enter kirim, Shift+Enter baris baru.
    // HP/tablet (layar sentuh): Enter tetap baris baru, kirim lewat tombol.
    const touchFirst = window.matchMedia?.("(hover: none) and (pointer: coarse)")?.matches ?? false;

    input.addEventListener("keydown", (event) => {
        if (event.key !== "Enter" || event.shiftKey) return;
        if (event.isComposing || event.keyCode === 229 || touchFirst) return;
        event.preventDefault();
        submitComposer();
    });

    /* ------------------------------------------------------ offer mode UI */

    function setOfferMode(active) {
        offerMode = active;

        modeBtn?.setAttribute("aria-pressed", String(active));
        modeIcon?.classList.toggle("hidden", active);
        modeClose?.classList.toggle("hidden", !active);
        offerPrefix?.classList.toggle("hidden", !active);
        imageBtn?.classList.toggle("hidden", active);

        if (active) clearAttachments();

        input.placeholder = active ? "Tawar harga" : "Ketik pesan";
        input.inputMode = active ? "numeric" : "text";
        input.value = "";
        autoGrow();
    }

    modeBtn?.addEventListener("click", () => {
        setOfferMode(!offerMode);
        input.focus();
    });

    /* --------------------------------------------------------- auto grow */

    function autoGrow() {
        input.style.height = "auto";
        input.style.height = `${Math.min(input.scrollHeight, 112)}px`;
    }

    input.addEventListener("input", autoGrow);

    /* -------------------------------------------------------- attachments */

    imageBtn?.addEventListener("click", () => imageInput?.click());

    imageInput?.addEventListener("change", () => {
        addFiles([...(imageInput.files ?? [])]);
        imageInput.value = "";
    });

    async function addFiles(files) {
        if (offerMode) return;

        const images = files.filter(isImageFile);
        if (images.length === 0) {
            if (files.length > 0) {
                toast("Pilih file gambar: JPG, PNG, WebP, atau GIF.", true);
            }
            return;
        }

        const remaining = MAX_ATTACHMENTS - pendingFiles.length;
        if (remaining <= 0) {
            toast(`Maksimal ${MAX_ATTACHMENTS} foto sekali kirim.`, true);
            return;
        }

        if (images.length > remaining) {
            toast(`Hanya ${remaining} foto lagi yang bisa ditambahkan.`, true);
        }

        const startIndex = pendingFiles.length;

        // Kompres dulu secara paralel, baru terima sesuai sisa kuota ukuran.
        const prepared = await Promise.all(images.slice(0, remaining).map(async (file) => ({
            id: uid(),
            file: await compressImage(file),
        })));

        let totalBytes = pendingFiles.reduce((sum, item) => sum + item.file.size, 0);
        const accepted = [];

        prepared.forEach((item) => {
            if (totalBytes + item.file.size > MAX_TOTAL_BYTES) return;

            totalBytes += item.file.size;
            accepted.push({ ...item, url: URL.createObjectURL(item.file) });
        });

        if (accepted.length < prepared.length) {
            toast(
                accepted.length === 0
                    ? "Total foto terlalu besar. Coba kirim beberapa dulu, lalu tambah lagi."
                    : "Sebagian foto tidak ikut karena totalnya melebihi batas sekali kirim.",
                true,
            );
        }

        pendingFiles.push(...accepted);
        renderAttachments();
        setActiveAttachment(startIndex);
    }

    function renderAttachments() {
        if (!attachList) return;

        attachList.innerHTML = "";

        pendingFiles.forEach((item, index) => {
            const tile = document.createElement("div");
            tile.dataset.attachTile = String(index);
            tile.setAttribute("role", "listitem");
            tile.className = "relative size-16 shrink-0 overflow-hidden rounded-xl border-2 border-transparent";

            const openButton = document.createElement("button");
            openButton.type = "button";
            openButton.dataset.attachOpen = String(index);
            openButton.className = "block size-full";
            openButton.setAttribute("aria-label", `Lihat foto ${index + 1}`);

            const thumb = document.createElement("img");
            thumb.src = item.url;
            thumb.alt = "";
            thumb.className = "size-full object-cover";
            openButton.appendChild(thumb);

            const removeButton = document.createElement("button");
            removeButton.type = "button";
            removeButton.dataset.attachRemove = String(index);
            removeButton.className = "absolute left-1 top-1 grid size-5 place-items-center rounded-full bg-black/65 text-white transition hover:bg-black";
            removeButton.setAttribute("aria-label", `Hapus foto ${index + 1}`);
            removeButton.innerHTML = '<svg viewBox="0 0 24 24" class="size-3 fill-none stroke-current stroke-[2.5]" aria-hidden="true"><path d="M18 6 6 18M6 6l12 12" stroke-linecap="round"/></svg>';

            tile.append(openButton, removeButton);
            attachList.appendChild(tile);
        });

        if (pendingFiles.length > 0) {
            const addButton = document.createElement("button");
            addButton.type = "button";
            addButton.dataset.attachAdd = "";
            addButton.className = "grid size-16 shrink-0 place-items-center rounded-xl border-2 border-dashed border-neutral-300 text-neutral-500 transition hover:border-neutral-400 hover:text-neutral-700";
            addButton.setAttribute("aria-label", "Tambah foto");
            addButton.innerHTML = '<svg viewBox="0 0 24 24" class="size-5 fill-none stroke-current stroke-2" aria-hidden="true"><path d="M12 5v14M5 12h14" stroke-linecap="round"/></svg>';
            attachList.appendChild(addButton);
        }

        const hasItems = pendingFiles.length > 0;
        attachStrip.classList.toggle("hidden", !hasItems);
        attachPanel?.classList.toggle("hidden", !hasItems);
        attachPanel?.classList.toggle("flex", hasItems);

        // Panel pratinjau menggantikan area produk + pesan supaya lebih tinggi.
        app.querySelector("[data-attach-hide]")?.classList.toggle("hidden", hasItems);

        syncCaptionPlaceholder();
    }

    function setActiveAttachment(index) {
        if (pendingFiles.length === 0) {
            if (attachImg) attachImg.removeAttribute("src");
            if (attachCounter) attachCounter.textContent = "";
            return;
        }

        activeAttachment = Math.min(Math.max(index, 0), pendingFiles.length - 1);
        const item = pendingFiles[activeAttachment];

        if (attachImg) attachImg.src = item.url;
        if (attachCounter) attachCounter.textContent = `${activeAttachment + 1} / ${pendingFiles.length}`;

        attachList?.querySelectorAll("[data-attach-tile]").forEach((tile) => {
            const active = Number(tile.dataset.attachTile) === activeAttachment;
            tile.classList.toggle("border-lime-500", active);
            tile.classList.toggle("border-transparent", !active);
        });
    }

    function removeAttachment(index) {
        const [removed] = pendingFiles.splice(index, 1);
        if (removed) URL.revokeObjectURL(removed.url);

        activeAttachment = Math.min(activeAttachment, Math.max(0, pendingFiles.length - 1));
        renderAttachments();
        setActiveAttachment(activeAttachment);
    }

    function clearAttachments() {
        pendingFiles.forEach((item) => URL.revokeObjectURL(item.url));
        pendingFiles = [];
        activeAttachment = 0;

        if (attachImg) attachImg.removeAttribute("src");
        if (attachCounter) attachCounter.textContent = "";
        renderAttachments();
    }

    function syncCaptionPlaceholder() {
        if (offerMode) return;
        input.placeholder = pendingFiles.length > 0 ? "Tambah keterangan..." : "Ketik pesan";
    }

    attachStrip?.addEventListener("click", (event) => {
        const addButton = event.target.closest("[data-attach-add]");
        if (addButton) {
            imageInput?.click();
            return;
        }

        const removeButton = event.target.closest("[data-attach-remove]");
        if (removeButton) {
            removeAttachment(Number(removeButton.dataset.attachRemove));
            return;
        }

        const openButton = event.target.closest("[data-attach-open]");
        if (openButton) setActiveAttachment(Number(openButton.dataset.attachOpen));
    });

    // Aksi untuk foto yang sedang tampil di panel pratinjau.
    attachPanel?.addEventListener("click", (event) => {
        if (event.target.closest("[data-attach-edit-active]")) {
            openEditor(activeAttachment);
            return;
        }

        if (event.target.closest("[data-attach-remove-active]")) {
            removeAttachment(activeAttachment);
            input.focus();
        }
    });

    attachPanel?.querySelector("[data-attach-close]")?.addEventListener("click", () => {
        clearAttachments();
        input.focus();
    });

    async function openEditor(index) {
        const item = pendingFiles[index];
        if (!item || !editorDialog) return;

        if (item.file.type === "image/gif") {
            toast("GIF tidak bisa diedit. Kirim apa adanya atau pilih foto biasa.", true);
            return;
        }

        try {
            if (!imageEditor) {
                const module = await import("./chat-image-editor");
                imageEditor = module.createImageEditor(editorDialog);
            }

            const edited = await imageEditor?.open(item.file);
            if (!edited) return;

            URL.revokeObjectURL(item.url);
            item.file = edited;
            item.url = URL.createObjectURL(edited);

            renderAttachments();
            setActiveAttachment(pendingFiles.indexOf(item));
        } catch (error) {
            toast(error.message || "Foto gagal diedit.", true);
        }
    }

    // Tempel gambar dari clipboard (Ctrl+V hasil copy gambar).
    form.addEventListener("paste", (event) => {
        if (offerMode) return;

        const files = [...(event.clipboardData?.items ?? [])]
            .filter((item) => item.type.startsWith("image/"))
            .map((item) => item.getAsFile())
            .filter(Boolean);

        if (files.length === 0) return;

        event.preventDefault();
        addFiles(files);
    });

    // Seret & lepas gambar ke area chat.
    ["dragover", "drop"].forEach((type) => {
        app.addEventListener(type, (event) => {
            if (offerMode) return;
            if (![...(event.dataTransfer?.items ?? [])].some((item) => item.kind === "file" && item.type.startsWith("image/"))) {
                return;
            }
            event.preventDefault();
            if (type === "drop") {
                addFiles([...(event.dataTransfer?.files ?? [])]);
            }
        });
    });

    /* --------------------------------------------------------- lightbox */

    box.addEventListener("click", (event) => {
        const trigger = event.target.closest("[data-lightbox-index]");
        if (!trigger || !lightbox || !lightboxImg) return;

        const groupNode = trigger.closest("[data-lightbox-group]");
        let group = [];

        try {
            group = JSON.parse(groupNode?.dataset.lightboxGroup ?? "[]");
        } catch {
            group = [];
        }

        openLightbox(
            Array.isArray(group) && group.length > 0 ? group : [trigger.querySelector("img")?.src ?? ""],
            Number(trigger.dataset.lightboxIndex) || 0,
            groupNode?.dataset.lightboxCaption ?? "",
        );
    });

    function openLightbox(group, index, caption = "") {
        lightboxGroup = group.filter(Boolean);
        if (lightboxGroup.length === 0) return;

        lightboxIndex = Math.min(Math.max(index, 0), lightboxGroup.length - 1);
        if (lightboxCaption) {
            lightboxCaption.textContent = caption;
            lightboxCaption.classList.toggle("hidden", caption === "");
        }

        updateLightbox();
        if (!lightbox.open) lightbox.showModal();
    }

    function updateLightbox() {
        if (!lightboxImg) return;

        lightboxImg.src = lightboxGroup[lightboxIndex] ?? "";
        const total = lightboxGroup.length;

        if (lightboxCounter) {
            lightboxCounter.textContent = total > 1 ? `${lightboxIndex + 1} / ${total}` : "Foto";
        }

        [lightboxPrev, lightboxNext].forEach((button) => {
            button?.classList.toggle("hidden", total <= 1);
        });

        if (lightboxPrev) lightboxPrev.disabled = lightboxIndex === 0;
        if (lightboxNext) lightboxNext.disabled = lightboxIndex >= total - 1;
    }

    function stepLightbox(step) {
        if (!lightbox?.open || lightboxGroup.length <= 1) return;

        lightboxIndex = Math.min(Math.max(lightboxIndex + step, 0), lightboxGroup.length - 1);
        updateLightbox();
    }

    lightbox?.querySelector("[data-lightbox-close]")?.addEventListener("click", () => lightbox.close());
    lightboxPrev?.addEventListener("click", () => stepLightbox(-1));
    lightboxNext?.addEventListener("click", () => stepLightbox(1));
    lightbox?.addEventListener("click", (event) => {
        if (event.target === lightbox) lightbox.close();
    });

    document.addEventListener("keydown", (event) => {
        if (!lightbox?.open) return;

        if (event.key === "ArrowLeft") {
            event.preventDefault();
            stepLightbox(-1);
        } else if (event.key === "ArrowRight") {
            event.preventDefault();
            stepLightbox(1);
        }
    });

    let lightboxTouchX = null;
    lightbox?.addEventListener("touchstart", (event) => {
        lightboxTouchX = event.touches[0]?.clientX ?? null;
    }, { passive: true });
    lightbox?.addEventListener("touchend", (event) => {
        if (lightboxTouchX === null) return;

        const delta = (event.changedTouches[0]?.clientX ?? lightboxTouchX) - lightboxTouchX;
        lightboxTouchX = null;

        if (Math.abs(delta) > 45) stepLightbox(delta > 0 ? -1 : 1);
    }, { passive: true });

    /* ------------------------------------------------------ offer actions */

    box.addEventListener("submit", (event) => {
        const actionForm = event.target.closest("form[data-offer-action]");
        if (!actionForm) return;

        event.preventDefault();
        runOfferAction(actionForm.action, new FormData(actionForm));
    });

    async function runOfferAction(url, payload) {
        try {
            const response = await fetch(url, {
                method: "POST",
                body: payload,
                headers: { Accept: "application/json", "X-Requested-With": "XMLHttpRequest" },
            });

            if (response.status === 409) throw new Error("Tawaran ini sudah diproses.");
            if (!response.ok) throw new Error("Gagal memproses tawaran.");

            const data = await response.json();
            if (data.html) replaceHtml(data.html);
            if (data.system) appendHtml(data.system.html);
            setOfferSummary(data.offer_summary);
        } catch (error) {
            toast(error.message || "Gagal memproses tawaran.", true);
        }
    }

    box.addEventListener("click", (event) => {
        const editBtn = event.target.closest("[data-offer-edit]");
        if (!editBtn) return;

        const card = editBtn.closest("[data-offer-card]");
        if (!card || !offerModal || !offerInput) return;

        editingOfferUrl = card.dataset.offerEditUrl || "";
        editingOfferId = Number(card.dataset.offerCard) || 0;
        offerError?.classList.add("hidden");
        offerInput.value = new Intl.NumberFormat(LOCALE).format(Number(card.dataset.offerAmount) || 0);
        if (!offerModal.open) offerModal.showModal();
        offerInput.focus();
        offerInput.select();
    });

    offerInput?.addEventListener("input", () => {
        const digits = offerInput.value.replace(/[^0-9]/g, "");
        offerInput.value = digits === "" ? "" : new Intl.NumberFormat(LOCALE).format(Number(digits));
    });

    offerModal?.querySelector("[data-offer-modal-cancel]")?.addEventListener("click", () => offerModal.close());

    offerForm?.addEventListener("submit", async (event) => {
        event.preventDefault();
        if (!editingOfferUrl) return;

        const amount = Number((offerInput?.value ?? "").replace(/[^0-9]/g, ""));
        if (!amount) {
            if (offerError) {
                offerError.textContent = "Nominal tawaran tidak valid.";
                offerError.classList.remove("hidden");
            }
            return;
        }

        try {
            const response = await fetch(editingOfferUrl, {
                method: "PUT",
                headers: {
                    "Content-Type": "application/json",
                    Accept: "application/json",
                    "X-CSRF-TOKEN": CSRF,
                    "X-Requested-With": "XMLHttpRequest",
                },
                body: JSON.stringify({ offer_amount: amount }),
            });

            if (response.status === 409) throw new Error("Tawaran ini sudah diproses.");
            if (!response.ok) throw new Error("Gagal mengubah tawaran.");

            const data = await response.json();
            if (data.html) replaceHtml(data.html);
            setOfferSummary(data.offer_summary);
            offerModal.close();
        } catch (error) {
            if (offerError) {
                offerError.textContent = error.message || "Gagal mengubah tawaran.";
                offerError.classList.remove("hidden");
            }
        }
    });

    /* ------------------------------------------------------------ receipts */

    function applyReceipts(receipts) {
        if (!receipts) return;

        const readUpto = Number(receipts.read_upto) || 0;
        const deliveredUpto = Number(receipts.delivered_upto) || 0;

        box.querySelectorAll("[data-message-id]").forEach((node) => {
            if ((node.dataset.senderId ?? "") !== myId) return;

            const badge = node.querySelector("[data-receipt]");
            if (!badge) return;

            const id = Number(node.dataset.messageId) || 0;
            const status = id <= readUpto ? "read" : id <= deliveredUpto ? "delivered" : "sent";

            if (badge.dataset.receipt === status) return;

            badge.dataset.receipt = status;
            badge.title = status === "read" ? "Dibaca" : status === "delivered" ? "Diterima" : "Terkirim";
            badge.innerHTML = receiptSvg(status);
        });
    }

    /* ------------------------------------------------------------ presence */

    // Saat halaman dimuat, langsung tampilkan selisih "Terakhir dilihat X lalu"
    // dari atribut server, tanpa menunggu poll pertama.
    hydratePresence(presenceNode);

    // Jaga label relatif tetap segar meski belum ada poll baru (mis. tab tidur).
    setInterval(() => {
        const iso = presenceNode?.getAttribute("data-last-seen-at");
        if (presenceNode?.dataset.online === "0" && iso) {
            renderPresence(presenceNode, { online: false, last_seen_at: iso });
        }
    }, 60000);

    function updatePresence(presence) {
        if (!presenceNode || !presence) return;
        renderPresence(presenceNode, presence);
    }

    /* -------------------------------------------------------------- polling */

    function pollLater(delay = 2500) {
        clearTimeout(pollTimer);
        pollTimer = setTimeout(poll, delay);
    }

    async function poll() {
        if (polling || document.hidden) {
            pollLater(4000);
            return;
        }

        polling = true;
        pollAbort = new AbortController();

        try {
            const url = `${pollUrl}?after=${lastId}&visible=1`;
            const response = await fetch(url, {
                headers: { Accept: "application/json", "X-Requested-With": "XMLHttpRequest" },
                signal: pollAbort.signal,
            });

            if (!response.ok) throw new Error("poll");

            const data = await response.json();

            (data.messages ?? []).forEach((message) => appendHtml(message.html));

            (data.offers ?? []).forEach((offer) => {
                const node = box.querySelector(`[data-message-id="${offer.id}"]`);
                if (!node) return;
                if (String(node.dataset.version) === String(offer.version)) return;
                replaceHtml(offer.html);
            });

            setOfferSummary(data.offer_summary);
            applyReceipts(data.receipts);
            updatePresence(data.presence);
            failures = 0;
        } catch {
            failures = Math.min(failures + 1, 4);
        } finally {
            polling = false;
            pollLater(failures === 0 ? 2500 : Math.min(20000, 2000 * 2 ** failures));
        }
    }

    document.addEventListener("visibilitychange", () => {
        if (!document.hidden) {
            markRead();
            pollLater(400);
        }
    });

    window.addEventListener("focus", () => markRead());

    async function markRead() {
        if (!readUrl) return;
        try {
            const response = await fetch(readUrl, {
                method: "POST",
                headers: {
                    Accept: "application/json",
                    "X-CSRF-TOKEN": CSRF,
                    "X-Requested-With": "XMLHttpRequest",
                },
            });
            if (!response.ok) return;
            const data = await response.json();
            applyReceipts(data.receipts);
            updateUnreadBadge(data.unread_total);
        } catch {
            /* diabaikan: akan dicoba lagi saat fokus berikutnya */
        }
    }

    /* ---------------------------------------------------------- chat menu */

    const menuToggle = app.querySelector("[data-chat-menu-toggle]");
    const menu = app.querySelector("[data-chat-menu]");

    menuToggle?.addEventListener("click", (event) => {
        event.stopPropagation();
        const hidden = menu?.classList.toggle("hidden");
        menuToggle.setAttribute("aria-expanded", String(!hidden));
    });

    document.addEventListener("click", (event) => {
        if (menu && !menu.contains(event.target) && !menuToggle?.contains(event.target)) {
            menu.classList.add("hidden");
            menuToggle?.setAttribute("aria-expanded", "false");
        }
    });

    app.querySelector("[data-mark-read]")?.addEventListener("click", () => {
        markRead();
        menu?.classList.add("hidden");
        toast("Ditandai sudah dibaca");
    });

    app.querySelector("[data-scroll-latest]")?.addEventListener("click", () => {
        scrollToBottom();
        menu?.classList.add("hidden");
    });

    /* --------------------------------------------------------- unread badge */

    function updateUnreadBadge(total) {
        const value = Number(total) || 0;
        document.querySelectorAll("[data-chat-badge]").forEach((badge) => {
            badge.textContent = value > 99 ? "99+" : String(value);
            badge.style.display = value === 0 ? "none" : "inline-grid";
        });
    }

    window.repakeRefreshChatBadge = refreshBadge;

    async function refreshBadge() {
        const badge = document.querySelector("[data-chat-badge]");
        if (!badge) return;
        try {
            const response = await fetch(badge.dataset.badgeUrl, {
                headers: { Accept: "application/json" },
            });
            if (!response.ok) return;
            const data = await response.json();
            updateUnreadBadge(data.total);
        } catch {
            /* diamkan */
        }
    }
}

/**
 * Badge pesan belum dibaca di navbar + bottom nav.
 * Dipasang global karena harus ikut tampil di semua halaman.
 */
export function initChatBadge() {
    const badges = document.querySelectorAll("[data-chat-badge]");
    if (!badges.length) return;

    const badgeUrl = badges[0].dataset.badgeUrl;
    if (!badgeUrl) return;

    const update = (total) => {
        const value = Number(total) || 0;
        badges.forEach((badge) => {
            badge.textContent = value > 99 ? "99+" : String(value);
            badge.style.display = value === 0 ? "none" : "inline-grid";
        });
    };

    let timer = null;

    const refresh = async () => {
        try {
            const response = await fetch(badgeUrl, { headers: { Accept: "application/json" } });
            if (!response.ok) return;
            const data = await response.json();
            update(data.total);
        } catch {
            /* offline / sesi berakhir: biarkan angka terakhir */
        }
    };

    const schedule = () => {
        clearTimeout(timer);
        timer = setTimeout(async () => {
            if (!document.hidden) await refresh();
            schedule();
        }, document.hidden ? 60000 : 25000);
    };

    refresh();
    schedule();

    document.addEventListener("visibilitychange", () => {
        if (!document.hidden) refresh();
    });
}
