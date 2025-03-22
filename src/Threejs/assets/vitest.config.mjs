import { defineConfig, mergeConfig } from 'vitest/config';
import configShared from '../../../vitest.config.mjs'
import path from 'path';

export default mergeConfig(
    configShared,
    defineConfig({
        test: {
            environment: 'happy-dom', // Utilisez jsdom comme environnement principal
            setupFiles: [path.join(__dirname, 'test', 'setup.js')],
            deps: {
                optimizer: {
                    web: {
                        include: ['vitest-canvas-mock'],
                    },
                },
            },
        }
    })
);
