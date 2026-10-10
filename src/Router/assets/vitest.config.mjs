import { mergeConfig } from 'vitest/config';
import configShared from '../../../vitest.config.base.mjs';

export default mergeConfig(configShared, {
    test: {
        typecheck: {
            enabled: true,
            include: ['test/types/**/*.test-d.ts'],
            tsconfig: './test/tsconfig.json',
        },
    },
});
