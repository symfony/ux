import { defineConfig, mergeConfig } from 'vitest/config';
import configShared from '../../../vitest.config.mjs'
import path from 'path';

export default mergeConfig(
    configShared,
    defineConfig({
        test: {
            setupFiles: [path.join(__dirname, 'test', 'setup.js')],
        }
    })
);
