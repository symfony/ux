import { dirname, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';
import { defineConfig } from 'vite';
import react from '@vitejs/plugin-react';
import vue from '@vitejs/plugin-vue';
import Reprise from '@symfony/reprise/vite';

export default defineConfig({
    build: {
        rollupOptions: {
            input: {
                app: './assets/app-reprise.ts',
            },
        },
    },
    plugins: [
        react(),
        vue(),
        Reprise({
            stimulus: './assets/controllers.json',
            integrity: {
                enabled: true,
            },
        }),
    ],
    resolve: {
        alias: {
            'leaflet/dist/leaflet.min.css': 'leaflet/dist/leaflet.css',
        },
    },
});
