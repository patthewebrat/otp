import { defineConfig, loadEnv } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';

// Inside ddev the dev server must listen on all interfaces, and the browser
// reaches it through ddev-router on https://<project>.ddev.site:5173.
const ddevHost = process.env.DDEV_HOSTNAME?.split(',')[0];

const server = ddevHost
    ? {
        https: false,
        host: '0.0.0.0',
        port: 5173,
        strictPort: true,
        cors: true,
        hmr: {
            host: ddevHost,
            protocol: 'wss',
            clientPort: 5173,
        },
    }
    : {
        https: false,
    };

export default defineConfig(({ mode }) => {
    const env = loadEnv(mode, process.cwd(), '');

    return {
        base: env.VITE_BASE || '/',
        plugins: [
            laravel({
                input: [
                    'resources/css/reset.css',
                    'resources/scss/app.scss',
                    'resources/js/app.js'
                ],
                refresh: true,
            }),
            vue(),
        ],
        server,
    };
});
