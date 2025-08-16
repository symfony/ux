import { defineConfig } from 'vitest/config';
import path from 'node:path';

export default defineConfig({
    test: {
        include: [
            './test/unit/**/*.{test,spec}.(ts|tsx)',
            './test/**/*.unit.{test,spec}.(ts|tsx)',
        ],
        environment: 'jsdom',
        setupFiles: [path.join(import.meta.dirname, 'test', 'setup.js')],
    },
});
