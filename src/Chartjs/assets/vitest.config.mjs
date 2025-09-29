import path from 'node:path';
import { mergeConfig } from 'vitest/config';
import configShared from '../../../vitest.config.base.mjs';

export default mergeConfig(configShared, {
    test: {
        setupFiles: [path.join(__dirname, 'test', 'setup.js')],
        deps: {
            optimizer: {
                web: {
                    include: ['vitest-canvas-mock'],
                },
            },
        },
    },
});
