import { defineConfig } from 'vitest/config';
import path from 'path';

export default defineConfig({
    test: {
        globals: true,
        environment: 'jsdom',
        setupFiles: [path.join(__dirname, 'test', 'setup.js')],
        coverage: {
            reporter: ['text', 'html'],
        },
    }
});
