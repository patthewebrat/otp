import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import fs from 'fs';
import path from 'path';

export default defineConfig({
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
        https: {
            key: fs.readFileSync(path.resolve(__dirname, 'certs/localhost-key.pem')),
            cert: fs.readFileSync(path.resolve(__dirname, 'certs/localhost.pem')),
        },
        host: 'localhost',
        cors: true,
        hmr: {
            protocol: 'wss',
            host: 'localhost',
        },
    },
});