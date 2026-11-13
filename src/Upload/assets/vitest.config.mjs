import { resolve } from 'node:path';
import { mergeConfig } from 'vitest/config';
import configShared from '../../../vitest.config.base.mjs';

export default mergeConfig(configShared, {
    resolve: {
        alias: {
            // Optional peer, stubbed so the tests run without LiveComponent built
            '@symfony/ux-live-component': resolve(import.meta.dirname, 'test/unit/stubs/live-component.ts'),
        },
    },
    test: {
        setupFiles: ['test/setup.ts'],
    },
});
