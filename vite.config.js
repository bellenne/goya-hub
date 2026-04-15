import { defineConfig, loadEnv } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';

const resolveHostname = (url) => {
    try {
        return new URL(url).hostname;
    } catch {
        return 'localhost';
    }
};

export default defineConfig(({ mode }) => {
    const env = loadEnv(mode, process.cwd(), '');
    const appUrl = env.APP_URL || 'http://localhost:8000';
    const devServerPort = Number(env.VITE_DEV_SERVER_PORT || 5173);
    const hmrPort = Number(env.VITE_DEV_SERVER_HMR_PORT || devServerPort);

    return {
        server: {
            host: env.VITE_DEV_SERVER_HOST || '0.0.0.0',
            port: devServerPort,
            strictPort: true,
            hmr: {
                host: env.VITE_DEV_SERVER_HMR_HOST || resolveHostname(appUrl),
                port: hmrPort,
                clientPort: hmrPort,
                protocol: env.VITE_DEV_SERVER_HMR_PROTOCOL || (appUrl.startsWith('https://') ? 'wss' : 'ws'),
            },
        },
        plugins: [
            laravel({
                input: 'resources/js/app.js',
                ssr: 'resources/js/ssr.js',
                refresh: true,
            }),
            vue({
                template: {
                    transformAssetUrls: {
                        base: null,
                        includeAbsolute: false,
                    },
                },
            }),
        ],
    };
});
