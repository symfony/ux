import { mergeConfig, defineConfig } from 'vitest/config';
import configShared from '../../../vitest.config.base.mjs';

export default mergeConfig(configShared, defineConfig({
    test: {
        environment: 'jsdom',
        include: ['test/**/*.test.ts'],
    },
}));
