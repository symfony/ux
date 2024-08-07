import { defineConfig, mergeConfig } from 'vitest/config';
import configShared from '../../../../../../vitest.config.js'

export default mergeConfig(
    configShared,
    defineConfig({
        resolve: {
            alias: {
                '@symfony/ux-map/abstract-map-controller': __dirname + '/../../../../assets/src/abstract_map_controller.ts',
            },
        },
        test: {
            // We need a browser(-like) environment to run the tests
            environment: 'happy-dom',
        },
    })
);
