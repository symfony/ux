import { dirname, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';
import { defineConfig } from '@rsbuild/core';
import { pluginReact } from '@rsbuild/plugin-react';
import { pluginVue } from '@rsbuild/plugin-vue';
import Reprise from '@symfony/reprise/rsbuild';

export default defineConfig({
    source: {
        entry: {
            app: './assets/app.js',
        },
    },
    plugins: [
        pluginReact(),
        pluginVue(),
        Reprise({
            stimulus: './assets/controllers.json',
            integrity: {
                enabled: true,
            },
        }),
    ],
    resolve: {
        dedupe: ['react', 'react-dom'],
        alias: {
            'leaflet/dist/leaflet.min.css': 'leaflet/dist/leaflet.css',
        },
    },
});
