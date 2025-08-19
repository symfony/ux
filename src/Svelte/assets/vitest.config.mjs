import { svelte } from '@sveltejs/vite-plugin-svelte';
import { mergeConfig } from 'vitest/config';
import configShared from '../../../vitest.config.base.mjs';

export default mergeConfig(configShared, {
    plugins: [svelte()],
});
