import { defineConfig, mergeConfig } from 'vitest/config';
import configShared from '../../../../../../vitest.config.js'

export default mergeConfig(
    configShared,
    defineConfig({
        resolve: {
            alias: {
                '@symfony/ux-map/abstract-map-controller': __dirname + '/../../../../assets/src/abstract_map_controller.ts',
                'leaflet/dist/leaflet.min.css': 'leaflet/dist/leaflet.css',
            },
        },
        test: {
            // We need a browser(-like) environment to run the tests
            environment: 'happy-dom',
        },
    })
);
