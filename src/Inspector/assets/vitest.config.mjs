import { mergeConfig } from 'vitest/config';
import configShared from '../../../vitest.config.base.mjs';

export default mergeConfig(configShared, {
    test: {
        include: ['./test/unit/**/*.test.js'],
        setupFiles: ['./test/setup.js'],
    },
});
