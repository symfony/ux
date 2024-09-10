// vitest.config.ts
import { defineConfig, mergeConfig } from 'vitest/config';
import vue from '@vitejs/plugin-vue';
import configShared from '../../../vitest.config.mjs'

export default mergeConfig(
    configShared,
    defineConfig({
         plugins: [vue()],
     })
);
