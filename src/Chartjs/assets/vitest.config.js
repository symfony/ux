import { defineConfig, mergeConfig } from 'vitest/config';
import configShared from '../../../vitest.config.js'
import path from 'path';

// defineConfig({
//         test: {

//         }
//      })
export default mergeConfig(
    configShared,
    {
        test: {
            setupFiles: [path.join(__dirname, 'test', 'setup.js')],
            deps: {
                optimizer: {
                    web: {
                        include: ['vitest-canvas-mock'],
                    },
                },
            },
        }
    }
);
