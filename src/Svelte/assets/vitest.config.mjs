// vitest.config.ts
import { defineConfig, mergeConfig } from 'vitest/config';
import { svelte } from '@sveltejs/vite-plugin-svelte';
import configShared from '../../../vitest.config.js'

export default mergeConfig(
    configShared,
    defineConfig({
         plugins: [svelte()],
     })
);
