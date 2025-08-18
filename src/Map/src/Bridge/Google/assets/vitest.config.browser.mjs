import { mergeConfig } from 'vitest/config';
import configShared from '../../../../../../vitest.config.browser.mjs';

export default mergeConfig(configShared, {
    resolve: {
        alias: {
            '@symfony/ux-map': __dirname + '/../../../../assets/src/abstract_map_controller.ts',
        },
    },
});
