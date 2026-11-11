import { defineConfig, loadEnv } from "vite";
import laravel from "laravel-vite-plugin";
import { bunny } from "laravel-vite-plugin/fonts";
import tailwindcss from "@tailwindcss/vite";

export default defineConfig(({ mode }) => {
    const env = loadEnv(mode, process.cwd(), "");

    const devHost = env.VITE_DEV_HOST || "0.0.0.0";
    const hmrHost = env.VITE_HMR_HOST || undefined;

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
