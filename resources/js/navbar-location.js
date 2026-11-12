const STORAGE_KEY = "repake_location_data";

function applySelectedLocation({ label, lat = null, lng = null }) {
    const wrapper = document.getElementById("navbar-location-wrapper");
    const homeUrl = wrapper?.dataset.homeUrl || "/";
    const searchUrl = wrapper?.dataset.searchUrl || "/products";
    const currentLabel = document.getElementById("current-location-label");
    const dropdown = document.getElementById("location-picker-dropdown");

    if (currentLabel) {
        currentLabel.textContent = label || "Pilih Lokasimu";
    }
    dropdown?.classList.add("hidden");

    try {
        if (!label || label === "Pilih Lokasimu") {
            localStorage.removeItem(STORAGE_KEY);
        } else {
            localStorage.setItem(
                STORAGE_KEY,
                JSON.stringify({ label, lat, lng }),
            );
        }
    } catch {
        // Abaikan jika storage penuh/diblokir
    }

    const currentPath = window.location.pathname;
    const homePath = new URL(homeUrl, window.location.origin).pathname;
    const searchPath = new URL(searchUrl, window.location.origin).pathname;

    // Jika sedang di halaman Home atau halaman Pencarian (/products), tetap di halaman tersebut
    const isStayPage = currentPath === homePath || currentPath === searchPath;
    const targetUrl = new URL(
        isStayPage ? window.location.href : searchUrl,
        window.location.origin,
    );

    // Reset pagination saat lokasi berubah
    targetUrl.searchParams.delete("page");

    if (!label || label === "Pilih Lokasimu") {
        targetUrl.searchParams.set("kota", "");
        targetUrl.searchParams.delete("lat");
        targetUrl.searchParams.delete("lng");
    } else {
        targetUrl.searchParams.set("kota", label);
        if (lat !== null && lng !== null) {
            targetUrl.searchParams.set("lat", String(lat));
            targetUrl.searchParams.set("lng", String(lng));
        } else {
            targetUrl.searchParams.delete("lat");
            targetUrl.searchParams.delete("lng");
        }
    }

    window.location.assign(targetUrl.toString());
}

function formatPlaceLabel(item) {
    const addr = item.address || {};
    const primary =
        item.name ||
        addr.amenity ||
        addr.village ||
        addr.suburb ||
        addr.town ||
        addr.city ||
        addr.county ||
        item.display_name.split(",")[0];

    const area = addr.city || addr.town || addr.county || addr.state || "";

    if (area && primary.toLowerCase() !== area.toLowerCase()) {
        return `${primary}, ${area}`;
    }
    return primary;
}

function escapeHtml(str) {
    return String(str)
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;");
}

function attachLocationSearch(input, resultsBox) {
    let timer = null;
    let controller = null;

    input.addEventListener("input", () => {
        clearTimeout(timer);
        const query = input.value.trim();

        if (query.length < 2) {
            controller?.abort();
            resultsBox.classList.add("hidden");
            resultsBox.innerHTML = "";
            return;
        }

        resultsBox.innerHTML = `<p class="px-3 py-2 text-[11px] text-neutral-400">Mencari lokasi...</p>`;
        resultsBox.classList.remove("hidden");

        timer = setTimeout(async () => {
            controller?.abort();
            controller = new AbortController();

            try {
                const url =
                    "https://nominatim.openstreetmap.org/search" +
                    `?format=jsonv2&addressdetails=1&countrycodes=id&limit=6&q=${encodeURIComponent(query)}`;

                const res = await fetch(url, {
                    signal: controller.signal,
                    headers: {
                        Accept: "application/json",
                        "Accept-Language": "id",
                    },
                });

                if (!res.ok) throw new Error("Gagal mencari lokasi");

                const items = await res.json();
                if (!Array.isArray(items) || items.length === 0) {
                    resultsBox.innerHTML = `<p class="px-3 py-2 text-[11px] text-neutral-400">Lokasi tidak ditemukan. Tekan Enter untuk pakai "${escapeHtml(query)}".</p>`;
                    resultsBox.classList.remove("hidden");
                    return;
                }

                resultsBox.innerHTML = items
                    .map((item) => {
                        const label = formatPlaceLabel(item);
                        return `
                            <button type="button"
                                data-search-pick
                                data-label="${escapeHtml(label)}"
                                data-lat="${item.lat}"
                                data-lng="${item.lon}"
                                class="block w-full px-3 py-2 text-left text-xs text-neutral-700 hover:bg-neutral-100 transition cursor-pointer">
                                <span class="block font-semibold text-neutral-900 truncate">${escapeHtml(label)}</span>
                                <span class="block text-[10px] text-neutral-400 truncate">${escapeHtml(item.display_name)}</span>
                            </button>
                        `;
                    })
                    .join("");
                resultsBox.classList.remove("hidden");
            } catch (err) {
                if (err.name === "AbortError") return;
                resultsBox.innerHTML = `<p class="px-3 py-2 text-[11px] text-neutral-400">Tekan Enter untuk mencari di "${escapeHtml(query)}".</p>`;
                resultsBox.classList.remove("hidden");
            }
        }, 300);
    });

    // Tekan Enter: pilih hasil teratas jika ada, atau pakai teks yang diketik
    input.addEventListener("keydown", (e) => {
        if (e.key !== "Enter") return;
        e.preventDefault();

        const firstResult = resultsBox.querySelector("[data-search-pick]");
        if (firstResult) {
            firstResult.click();
            return;
        }

        const typed = input.value.trim();
        if (typed !== "") {
            applySelectedLocation({ label: typed });
        }
    });

    resultsBox.addEventListener("click", (e) => {
        const btn = e.target.closest("[data-search-pick]");
        if (!btn) return;
        applySelectedLocation({
            label: btn.dataset.label,
            lat: Number(btn.dataset.lat),
            lng: Number(btn.dataset.lng),
        });
    });
}

export function initNavbarLocation() {
    const wrapper = document.getElementById("navbar-location-wrapper");
    if (!wrapper) return;

    const toggleBtn = document.getElementById("location-picker-toggle");
    const dropdown = document.getElementById("location-picker-dropdown");
    const searchInput = document.getElementById("navbar-location-search");
    const resultsBox = document.getElementById("navbar-location-results");
    const liveBtn = document.getElementById("navbar-btn-live-location");
    const statusText = document.getElementById("navbar-location-status");

    if (searchInput && resultsBox) {
        attachLocationSearch(searchInput, resultsBox);
    }

    toggleBtn?.addEventListener("click", (e) => {
        e.stopPropagation();
        const willOpen = dropdown?.classList.contains("hidden");
        dropdown?.classList.toggle("hidden");

        if (willOpen) {
            setTimeout(() => searchInput?.focus(), 50);
        }
    });

    // Klik preset lokasi populer
    wrapper.querySelectorAll("[data-preset-location]").forEach((btn) => {
        btn.addEventListener("click", () => {
            applySelectedLocation({
                label: btn.dataset.label,
                lat: Number(btn.dataset.lat),
                lng: Number(btn.dataset.lng),
            });
        });
    });

    // Reset lokasi
    wrapper
        .querySelector("[data-reset-location]")
        ?.addEventListener("click", () => {
            applySelectedLocation({ label: "Pilih Lokasimu" });
        });

    // Tombol GPS: Gunakan lokasi saat ini
    liveBtn?.addEventListener("click", () => {
        if (!navigator.geolocation) {
            if (statusText) {
                statusText.textContent = "Browser tidak mendukung GPS.";
                statusText.classList.remove("hidden");
            }
            return;
        }

        if (statusText) {
            statusText.textContent = "Mendeteksi lokasi GPS...";
            statusText.classList.remove("hidden");
        }

        navigator.geolocation.getCurrentPosition(
            async (pos) => {
                const { latitude, longitude } = pos.coords;
                let placeName = "Lokasi Saat Ini";

                try {
                    const url = `https://api.bigdatacloud.net/data/reverse-geocode-client?latitude=${latitude}&longitude=${longitude}&localityLanguage=id`;
                    const res = await fetch(url);
                    if (res.ok) {
                        const data = await res.json();
                        placeName =
                            data.locality ||
                            data.city ||
                            data.principalSubdivision ||
                            placeName;
                    }
                } catch {
                    // Tetap gunakan label default jika reverse-geocode gagal
                }

                applySelectedLocation({
                    label: placeName,
                    lat: latitude,
                    lng: longitude,
                });
            },
            () => {
                if (statusText) {
                    statusText.textContent =
                        "Izin lokasi ditolak. Aktifkan GPS atau pilih preset.";
                    statusText.classList.remove("hidden");
                }
            },
            { timeout: 6000, maximumAge: 60000 },
        );
    });

    // Tutup dropdown saat klik di luar
    document.addEventListener("click", (e) => {
        if (
            dropdown &&
            !dropdown.contains(e.target) &&
            !toggleBtn?.contains(e.target)
        ) {
            dropdown.classList.add("hidden");
        }
    });
}
