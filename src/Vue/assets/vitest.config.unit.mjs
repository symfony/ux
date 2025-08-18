import vue from '@vitejs/plugin-vue';
import { mergeConfig } from 'vitest/config';
import configShared from '../../../vitest.config.unit.mjs';

export default mergeConfig(configShared, {
    plugins: [vue()],
});
