import { mergeConfig, defineConfig } from 'vitest/config';
import configShared from '../../../vitest.config.base.mjs';

export default mergeConfig(
    configShared,
    defineConfig({
        test: {
            environment: 'jsdom',
            setupFiles: ['./test/vitest.setup.js'],
        },
    })
);
