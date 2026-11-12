export default (initialData) => ({
    ...initialData,
    openFilter: null,
    timer: null,
    controller: null,
    selectedKategori: initialData.kategori || [],
    selectedKondisi: initialData.kondisi || [],
    hargaPreset: initialData.hargaPreset || "",
    minPrice: initialData.minPrice || "",
    maxPrice: initialData.maxPrice || "",
    kota: initialData.kota || "",
    selectedSort: initialData.sort || initialData.defaultSort,

    init() {
        window.addEventListener("popstate", () => location.reload());
    },

    toggleFilter(name) {
        this.openFilter = this.openFilter === name ? null : name;
    },

    closeFilter() {
        this.openFilter = null;
    },

    toggleKategori(slug) {
        this.selectedKategori = this.selectedKategori.includes(slug)
            ? this.selectedKategori.filter((x) => x !== slug)
            : [...this.selectedKategori, slug];
        this.apply();
    },

    setHargaPreset(preset) {
        this.hargaPreset = preset;
        const presets = {
            gratis: ["0", "0"],
            under_50k: ["0", "50000"],
            "50k_200k": ["50000", "200000"],
            over_200k: ["200000", ""],
        };
        const range = presets[preset] || ["", ""];
        this.minPrice = range[0];
        this.maxPrice = range[1];
        this.apply();
    },

    formatRupiah(num) {
        if (num === "" || num === null || num === undefined) return "";
        return "Rp" + parseInt(num, 10).toLocaleString("id-ID");
    },

    clearNavbarLocation() {
        this.kota = "";
        try {
            localStorage.removeItem("repake_location_data");
        } catch {
            // Abaikan
        }
        const navLabel = document.getElementById("current-location-label");
        if (navLabel) {
            navLabel.textContent = "Pilih Lokasimu";
        }
    },

    resetFilter(name) {
        if (name === "kategori") this.selectedKategori = [];
        if (name === "kondisi") this.selectedKondisi = [];
        if (name === "harga") {
            this.hargaPreset = "";
            this.minPrice = "";
            this.maxPrice = "";
        }
        if (name === "lokasi") {
            this.clearNavbarLocation();
        }
        this.apply();
    },

    resetAll() {
        ["kategori", "kondisi", "harga", "lokasi"].forEach((n) =>
            this.resetFilter(n),
        );
    },

    get activeTags() {
        const tags = [];

        const addTag = (key, label, removeFn) => {
            tags.push({
                key,
                label,
                remove: () => {
                    removeFn();
                    this.apply();
                },
            });
        };

        if (this.kota && this.kota !== "Pilih Lokasimu") {
            addTag("kota", "Lokasi: " + this.kota, () => {
                this.clearNavbarLocation();
            });
        }

        this.selectedKategori.forEach((slug) =>
            addTag("kat_" + slug, this.kategoriNames[slug] ?? slug, () => {
                this.selectedKategori = this.selectedKategori.filter(
                    (x) => x !== slug,
                );
            }),
        );

        this.selectedKondisi.forEach((kondisi) =>
            addTag("kon_" + kondisi, kondisi, () => {
                this.selectedKondisi = this.selectedKondisi.filter(
                    (x) => x !== kondisi,
                );
            }),
        );

        if (
            this.hargaPreset ||
            (this.minPrice !== "" && this.minPrice !== null) ||
            (this.maxPrice !== "" && this.maxPrice !== null)
        ) {
            const presetLabels = {
                gratis: "Gratis (Rp 0)",
                under_50k: "< Rp50.000",
                "50k_200k": "Rp50rb - Rp200rb",
                over_200k: "> Rp200.000",
            };
            const label =
                presetLabels[this.hargaPreset] ??
                `${this.formatRupiah(this.minPrice) || "Rp0"} - ${this.maxPrice !== "" ? this.formatRupiah(this.maxPrice) : "Maks"}`;
            addTag("harga", "Harga: " + label, () => {
                this.hargaPreset = "";
                this.minPrice = "";
                this.maxPrice = "";
            });
        }

        return tags;
    },

    params() {
        const cur = new URLSearchParams(window.location.search);
        const p = new URLSearchParams();

        if (cur.get("q")) p.set("q", cur.get("q"));

        if (this.kota && this.kota !== "Pilih Lokasimu") {
            p.set("kota", this.kota);
            if (cur.get("lat")) p.set("lat", cur.get("lat"));
            if (cur.get("lng")) p.set("lng", cur.get("lng"));
        } else {
            // Kirim kota kosong agar session('user_location') ikut terhapus di backend
            p.set("kota", "");
        }

        this.selectedKategori.forEach((v) => p.append("kategori[]", v));
        this.selectedKondisi.forEach((v) => p.append("kondisi[]", v));

        const setParam = (k, v) => {
            if (v !== "" && v != null && v !== false) p.set(k, v);
        };

        setParam("harga_preset", this.hargaPreset);
        setParam("min_price", this.minPrice);
        setParam("max_price", this.maxPrice);

        if (this.selectedSort !== this.defaultSort) {
            p.set("sort", this.selectedSort);
        }

        return p.toString();
    },

    apply() {
        clearTimeout(this.timer);
        this.timer = setTimeout(() => this.load(), 250);
    },

    async load() {
        const qs = this.params();
        const url = this.action + (qs ? "?" + qs : "");
        const box = document.getElementById(this.target);

        if (this.controller) this.controller.abort();
        this.controller = new AbortController();

        if (box) box.classList.add("opacity-50", "pointer-events-none");

        try {
            const response = await fetch(url, {
                headers: { "X-Requested-With": "XMLHttpRequest" },
                signal: this.controller.signal,
            });

            if (!response.ok) throw new Error(response.status);

            const html = await response.text();
            if (box) {
                box.innerHTML = html;
                const total = box.querySelector("[data-total]")?.dataset.total;
                const countEl = document.getElementById("result-count");
                if (total !== undefined && countEl) countEl.textContent = total;
            }

            history.pushState({}, "", url);
        } catch (e) {
            if (e.name !== "AbortError") {
                window.location.href = url;
            }
        } finally {
            if (box) box.classList.remove("opacity-50", "pointer-events-none");
        }
    },
});
