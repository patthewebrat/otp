import { defineConfig, loadEnv } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';

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
        server: {
            https: false,
        },
    };
});
