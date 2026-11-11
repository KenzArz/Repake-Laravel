/**
 * Deteksi kota user [ALDI] dan simpan ke users.kota via POST /location.
 *
 * Cara kerja:
 * 1. Hanya berjalan bila ada elemen `[data-location-detect]` di halaman
 *    (label kota di navbar adalah wilayah RAFIS — koordinasi).
 * 2. Minta izin geolocation sekali; bila gagal atau tanpa izin, fallback ke
 *    deteksi berdasarkan IP; bila itu juga gagal, diam saja (default kota
 *    tetap dipakai UI).
 * 3. Hasil dikirim ke LocationController (route location.store) dan label
 *    `[data-location-label]` diperbarui bila ada.
 */

const STORAGE_KEY = "repake_kota";

async function detectCity() {
  const cityFromGps = await cityFromGeolocation();
  if (cityFromGps) return cityFromGps;

  try {
    const res = await fetch("https://ipapi.co/json/");
    if (!res.ok) return null;
    const data = await res.json();
    return data && data.city ? String(data.city) : null;
  } catch {
    return null;
  }
}

function cityFromGeolocation() {
  return new Promise((resolve) => {
    if (!navigator.geolocation) return resolve(null);

    navigator.geolocation.getCurrentPosition(
      async (position) => {
        try {
          const { latitude, longitude } = position.coords;
          const url =
            "https://api.bigdatacloud.net/data/reverse-geocode-client" +
            `?latitude=${latitude}&longitude=${longitude}&localityLanguage=id`;
          const res = await fetch(url);
          if (!res.ok) return resolve(null);
          const data = await res.json();
          const city = data.locality || data.city || data.principalSubdivision;
          resolve(city ? String(city) : null);
        } catch {
          resolve(null);
        }
      },
      () => resolve(null),
      { timeout: 5000, maximumAge: 3600000 },
    );
  });
}

export function initLocationDetect() {
  const el = document.querySelector("[data-location-detect]");
  if (!el || document.body.dataset.authed !== "1" || localStorage.getItem(STORAGE_KEY)) return;

  detectCity().then(async (city) => {
    if (!city) return;
    localStorage.setItem(STORAGE_KEY, city);

    const label = document.querySelector("[data-location-label]");
    if (label) label.textContent = city;

    try {
      const csrfMeta = document.querySelector('meta[name="csrf-token"]');
      const res = await fetch(el.dataset.locationUrl, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          "X-CSRF-TOKEN": csrfMeta ? csrfMeta.content : "",
          Accept: "application/json",
        },
        body: JSON.stringify({ kota: city }),
      });
      if (!res.ok) localStorage.removeItem(STORAGE_KEY);
    } catch {
      localStorage.removeItem(STORAGE_KEY);
    }
  });
}
