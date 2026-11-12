import { defineConfig, loadEnv } from "vite";
import laravel from "laravel-vite-plugin";
import { bunny } from "laravel-vite-plugin/fonts";
import tailwindcss from "@tailwindcss/vite";

export default defineConfig(({ mode }) => {
    const env = loadEnv(mode, process.cwd(), "");

    // Default aman: bind hanya ke localhost. "0.0.0.0" adalah alamat BIND,
    // bukan alamat tujuan yang valid — jangan sampai masuk ke public/hot
    // (di Windows selalu gagal dengan ERR_ADDRESS_INVALID, dan Chrome modern
    // juga memblokir akses ke 0.0.0.0). Untuk LAN, set via .env.
    const devHost = env.VITE_DEV_HOST || "localhost";

    // Kalau user sengaja bind 0.0.0.0 untuk akses LAN, HMR/hot-file harus
    // memakai alamat nyata. Paksa fallback ke localhost agar public/hot
    // tidak pernah berisi 0.0.0.0 saat VITE_HMR_HOST tidak diisi.
    let hmrHost = env.VITE_HMR_HOST || undefined;
    if (devHost === "0.0.0.0" && !hmrHost) {
        console.warn(
            "[vite] VITE_DEV_HOST=0.0.0.0 tanpa VITE_HMR_HOST: public/hot akan " +
            "memakai localhost. Set VITE_HMR_HOST=<IP-LAN-mesin> jika perangkat " +
            "lain perlu memuat aset."
        );
        hmrHost = "localhost";
    }

    return {
        plugins: [
            laravel({
                input: ["resources/css/app.css", "resources/js/app.js"],
                refresh: true,
                fonts: [
                    bunny("Instrument Sans", {
                        weights: [400, 500, 600],
                    }),
                ],
            }),
            tailwindcss(),
        ],
        server: {
            watch: {
                ignored: ["**/storage/framework/views/**"],
            },
            host: devHost,
            hmr: hmrHost ? { host: hmrHost } : true,
        },
    };
});
