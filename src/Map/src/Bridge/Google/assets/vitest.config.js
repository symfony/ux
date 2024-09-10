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
        define: {
            // Prevent the following error:
            //      ReferenceError: global is not defined
            //       ❯ ../../../../../../node_modules/pretty-format/build/plugins/AsymmetricMatcher.js ../../../../../../../../../../node_modules/.vite/deps/@testing-library_dom.js:139:19
            //       ❯ ../../../../../../node_modules/pretty-format/build/index.js ../../../../../../../../../../node_modules/.vite/deps/@testing-library_dom.js:805:7
            //       ❯ ../../../../../../../../../../node_modules/.vite/deps/@testing-library_dom.js:13445:36
            global: {}
        },
        test: {
            browser: {
                enabled: true,
                provider: 'playwright', // or 'webdriverio'
                name: 'chromium', // browser name is required
                headless: true,
            },
        },
    })
);
