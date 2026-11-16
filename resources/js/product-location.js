export function initProductLocation() {
    const searchInput = document.getElementById("location_search");
    const kotaInput = document.getElementById("kota");
    const provinceInput = document.getElementById("province");
    const latInput = document.getElementById("cod_lat");
    const lngInput = document.getElementById("cod_lng");
    const liveBtn = document.getElementById("btn-live-location");
    const statusEl = document.getElementById("location-status");

    if (!searchInput || !kotaInput) return;

    searchInput.disabled = false;
    searchInput.removeAttribute("disabled");

    let useGooglePlaces = Boolean(
        document.querySelector('script[src*="maps.googleapis.com"]'),
    );
    let placesLib = null;
    let debounceTimer = null;
    let abortController = null;
    let isFocused = false;
    const cache = new Map();

    window.gm_authFailure = () => {
        useGooglePlaces = false;
    };

    // 1. Preload Google Places library sejak awal (menghilangkan delay ketikan pertama)
    const preloadPlaces = async () => {
        if (!useGooglePlaces || placesLib) return;
        if (window.google?.maps?.importLibrary) {
            try {
                placesLib = await google.maps.importLibrary("places");
            } catch {
                useGooglePlaces = false;
            }
        }
    };

    if (useGooglePlaces) {
        preloadPlaces();
        const warmupTimer = setInterval(() => {
            if (placesLib || !useGooglePlaces) {
                clearInterval(warmupTimer);
            } else {
                preloadPlaces();
            }
        }, 300);
        setTimeout(() => clearInterval(warmupTimer), 6000);
    }

    const setStatus = (msg, isError = false) => {
        if (!statusEl) return;
        statusEl.textContent = msg;
        statusEl.className = `mt-1 text-[11px] font-medium ${isError ? "text-red-600" : "text-lime-700"}`;
        statusEl.classList.remove("hidden");
    };

    const extractRegion = (components = []) => {
        let city = "";
        let province = "";

        for (const comp of components) {
            const label = comp.longText || comp.long_name || "";
            if (comp.types?.includes("administrative_area_level_2")) {
                city = label;
            }
            if (comp.types?.includes("administrative_area_level_1")) {
                province = label;
            }
        }
        return { city, province };
    };

    const applyLocation = ({ name, city, province, lat, lng, sourceLabel }) => {
        const cleanCity = (city || name || "")
            .replace(/^(Kabupaten|Kota)\s+/i, "")
            .trim();
        searchInput.value = name || cleanCity;
        kotaInput.value = cleanCity;
        provinceInput.value = province || "";
        latInput.value = lat ?? "";
        lngInput.value = lng ?? "";
        setStatus(`${sourceLabel} (${cleanCity})`);
    };

    // Dropdown rekomendasi kustom
    const dropdown = document.createElement("ul");
    dropdown.className =
        "absolute left-0 right-0 top-full z-30 mt-1 hidden max-h-60 overflow-y-auto rounded-xl border border-neutral-200 bg-white py-1 shadow-lg text-xs";
    searchInput.parentElement.appendChild(dropdown);

    const hideDropdown = () => dropdown.classList.add("hidden");

    const showLoading = () => {
        if (!isFocused) return;
        dropdown.innerHTML = `<li class="px-3 py-2 text-[11px] text-neutral-400">Mencari lokasi...</li>`;
        dropdown.classList.remove("hidden");
    };

    const renderSuggestions = (items) => {
        if (!isFocused) return;
        dropdown.innerHTML = "";

        if (!items || items.length === 0) {
            hideDropdown();
            return;
        }

        items.forEach((item) => {
            const li = document.createElement("li");
            li.className =
                "cursor-pointer border-b border-neutral-100 px-3 py-2 transition hover:bg-lime-50 last:border-0";
            li.innerHTML = `
                <p class="font-bold text-neutral-900">${item.title}</p>
                ${item.subtitle ? `<p class="text-[11px] text-neutral-500">${item.subtitle}</p>` : ""}
            `;

            li.addEventListener("mousedown", async (e) => {
                e.preventDefault();
                hideDropdown();
                await item.onSelect();
            });

            dropdown.appendChild(li);
        });

        dropdown.classList.remove("hidden");
    };

    // Pencarian via Google Places API (New)
    const searchWithGoogle = async (query) => {
        if (!useGooglePlaces) return null;

        try {
            if (!placesLib && window.google?.maps?.importLibrary) {
                placesLib = await google.maps.importLibrary("places");
            }
            if (!placesLib) return null;

            const { suggestions } =
                await placesLib.AutocompleteSuggestion.fetchAutocompleteSuggestions(
                    {
                        input: query,
                        includedRegionCodes: ["id"],
                        language: "id",
                    },
                );

            return (suggestions || [])
                .filter((s) => s.placePrediction)
                .map((s) => {
                    const pred = s.placePrediction;
                    return {
                        title: pred.mainText?.text || pred.text?.text || query,
                        subtitle: pred.secondaryText?.text || "",
                        onSelect: async () => {
                            const place = pred.toPlace();
                            await place.fetchFields({
                                fields: [
                                    "displayName",
                                    "location",
                                    "addressComponents",
                                ],
                            });

                            const { city, province } = extractRegion(
                                place.addressComponents,
                            );
                            applyLocation({
                                name:
                                    place.displayName ||
                                    pred.mainText?.text ||
                                    query,
                                city,
                                province,
                                lat: place.location?.lat(),
                                lng: place.location?.lng(),
                                sourceLabel: "Titik lokasi tersimpan",
                            });
                        },
                    };
                });
        } catch {
            useGooglePlaces = false;
            return null;
        }
    };

    // Pencarian gratis via Photon (OpenStreetMap) dengan AbortController
    const searchWithPhoton = async (query, signal) => {
        try {
            const url = `https://photon.komoot.io/api/?q=${encodeURIComponent(query)}&lat=-6.3249&lon=107.3069&limit=6`;
            const res = await fetch(url, { signal });
            const data = await res.json();

            return (data.features || [])
                .filter(
                    (f) =>
                        !f.properties?.countrycode ||
                        f.properties.countrycode === "ID",
                )
                .map((feat) => {
                    const props = feat.properties || {};
                    const [lng, lat] = feat.geometry?.coordinates || [];
                    const name = props.name || props.street || query;
                    const city =
                        props.city || props.county || props.district || name;
                    const province = props.state || "";
                    const subtitle = [props.street, city, province]
                        .filter(Boolean)
                        .join(", ");

                    return {
                        title: name,
                        subtitle,
                        onSelect: async () => {
                            applyLocation({
                                name,
                                city,
                                province,
                                lat,
                                lng,
                                sourceLabel: "Titik lokasi tersimpan",
                            });
                        },
                    };
                });
        } catch (err) {
            if (err.name === "AbortError") return null;
            return [];
        }
    };

    // Event saat user mengetik (minimal 2 huruf, debounce 150ms + cache)
    searchInput.addEventListener("input", () => {
        const q = searchInput.value.trim();
        const cacheKey = q.toLowerCase();

        kotaInput.value = q;
        latInput.value = "";
        lngInput.value = "";

        clearTimeout(debounceTimer);
        if (abortController) {
            abortController.abort();
            abortController = null;
        }

        if (q.length < 2) {
            hideDropdown();
            return;
        }

        // Langsung tampilkan dari cache tanpa delay jika kata kunci pernah dicari
        if (cache.has(cacheKey)) {
            renderSuggestions(cache.get(cacheKey));
            return;
        }

        debounceTimer = setTimeout(async () => {
            showLoading();
            abortController = new AbortController();

            let results = await searchWithGoogle(q);
            if (results === null) {
                results = await searchWithPhoton(q, abortController.signal);
            }

            if (results === null) return; // Request dibatalkan karena user lanjut mengetik

            cache.set(cacheKey, results);
            renderSuggestions(results);
        }, 150);
    });

    searchInput.addEventListener("focus", () => {
        isFocused = true;
        preloadPlaces();
        const q = searchInput.value.trim();
        if (q.length >= 2 && cache.has(q.toLowerCase())) {
            renderSuggestions(cache.get(q.toLowerCase()));
        }
    });

    searchInput.addEventListener("blur", () => {
        isFocused = false;
        hideDropdown();
    });

    document.addEventListener("click", (e) => {
        if (!searchInput.parentElement.contains(e.target)) {
            isFocused = false;
            hideDropdown();
        }
    });

    // Tombol Lokasi Saat Ini (Live GPS — cepat seperti navbar)
    liveBtn?.addEventListener("click", () => {
        if (!navigator.geolocation) {
            setStatus("Browser tidak mendukung fitur GPS.", true);
            return;
        }

        liveBtn.disabled = true;
        liveBtn.classList.add("opacity-50", "pointer-events-none");
        const unlockBtn = () => {
            liveBtn.disabled = false;
            liveBtn.classList.remove("opacity-50", "pointer-events-none");
        };

        hideDropdown();
        setStatus("Mendeteksi lokasi GPS...");

        navigator.geolocation.getCurrentPosition(
            async (pos) => {
                try {
                    const lat = pos.coords.latitude;
                    const lng = pos.coords.longitude;
                    await reverseFast(lat, lng);
                } finally {
                    unlockBtn();
                }
            },
            () => {
                unlockBtn();
                setStatus("Izin lokasi ditolak atau GPS tidak aktif.", true);
            },
            { enableHighAccuracy: false, timeout: 6000, maximumAge: 60000 },
        );
    });

    const reverseFast = async (lat, lng) => {
        // 1. Primary cepat via BigDataCloud (sama seperti navbar)
        try {
            const res = await fetch(
                `https://api.bigdatacloud.net/data/reverse-geocode-client?latitude=${lat}&longitude=${lng}&localityLanguage=id`,
            );
            if (res.ok) {
                const data = await res.json();
                const city = data.city || data.principalSubdivision || "";
                const province = data.principalSubdivision || "";
                const locality = data.locality || "";
                const placeName =
                    locality &&
                    city &&
                    locality.toLowerCase() !== city.toLowerCase()
                        ? `${locality}, ${city.replace(/^(Kabupaten|Kota)\s+/i, "")}`
                        : locality || city;

                if (placeName) {
                    applyLocation({
                        name: placeName,
                        city: city || placeName,
                        province,
                        lat,
                        lng,
                        sourceLabel: "Lokasi GPS aktif",
                    });
                    return;
                }
            }
        } catch {
            // Lanjut ke fallback Nominatim
        }

        // 2. Fallback via OpenStreetMap Nominatim
        try {
            const res = await fetch(
                `https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat=${lat}&lon=${lng}&accept-language=id`,
            );
            const data = await res.json();
            const addr = data.address || {};
            const city =
                addr.city ||
                addr.town ||
                addr.county ||
                addr.municipality ||
                "";
            const province = addr.state || "";
            const placeName =
                data.name ||
                [addr.road, addr.suburb || addr.village]
                    .filter(Boolean)
                    .join(", ") ||
                city;

            applyLocation({
                name: placeName,
                city,
                province,
                lat,
                lng,
                sourceLabel: "Lokasi GPS aktif",
            });
        } catch {
            applyLocation({
                name: `${lat.toFixed(5)}, ${lng.toFixed(5)}`,
                city: `${lat.toFixed(5)}, ${lng.toFixed(5)}`,
                province: "",
                lat,
                lng,
                sourceLabel: "Koordinat GPS aktif",
            });
        }
    };
}
