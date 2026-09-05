import { resolve } from 'node:path';
import { defineConfig } from 'vitest/config';
import configShared from '../../../vitest.config.base.mjs';

export default defineConfig({
    ...configShared,
    resolve: {
        ...configShared.resolve,
        alias: {
            ...configShared.resolve?.alias,
            // Optional peer: stub it so the live-upload bridge is testable
            // without installing LiveComponent.
            '@symfony/ux-live-component': resolve(import.meta.dirname, 'test/unit/stubs/live-component.ts'),
        },
    },
    test: {
        ...configShared.test,
        globals: true,
        pool: 'forks',
        maxWorkers: 1,
        fileParallelism: false,
        testTimeout: 5000,
        hookTimeout: 5000,
        setupFiles: ['test/setup.ts'],
    },
});
